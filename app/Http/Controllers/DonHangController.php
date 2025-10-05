<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Models\DonHang;
use App\Models\HoaDon;
use App\Models\ChiTietDonHang;
use App\Models\SanPham;
use App\Models\KhachHang;
use App\Http\Requests\DonHang\ThanhToanDonHangRequest;
use Illuminate\Support\Facades\Log; 
use Carbon\Carbon;
use Illuminate\Support\Facades\Schema;

class DonHangController extends Controller
{
    public function thanhToan(ThanhToanDonHangRequest $request)
    {
        DB::beginTransaction();
        try {
            $data = $request->validated();

            // helper làm tròn về đồng (half up)
            $money = fn($v) => (int) round((float) $v, 0, PHP_ROUND_HALF_UP);

            // Map FE -> DB (tiền mặt = cash)
            $map  = ['cash' => 'cash', 'bank' => 'bank_transfer', 'card' => 'credit_card'];
            $phuongThucThanhToan = $map[$data['phuongThuc']] ?? 'cash';

            $tamTinh   = 0;
            $tongVAT   = 0;   // = Σ(giaBan * vat * qty)
            $giamFS    = 0;   // = Σ((gia_sau_vat - gia) * qty)
            $chiTietSnapshots = [];

            $giamVoucher   = $money($data['giamVoucher'] ?? 0);
            $giamDiem      = $money($data['giamDiem'] ?? 0);
            $phiVanChuyen  = 0; // POS: không phí VC/COD

            $hasFlashCol = \Illuminate\Support\Facades\Schema::hasColumn('chitietdonhang', 'flash_sale');

            foreach ($data['sanPhams'] as $item) {
                // cần: id, soLuong, gia_sau_vat, gia (lấy từ search)
                $sp = SanPham::lockForUpdate()->find($item['id'] ?? null);
                if (!$sp) {
                    DB::rollBack();
                    return response()->json(['error' => 'Không tìm thấy sản phẩm'], 404);
                }

                $sl = (int)($item['soLuong'] ?? 0);
                if ($sl <= 0) continue;

                // kiểm kho
                if ((int)$sp->soLuongTon < $sl) {
                    DB::rollBack();
                    return response()->json(['error' => 'Không đủ tồn kho cho sản phẩm ' . ($sp->tenSanPham ?? $sp->id)], 400);
                }

                // Giá/thuế từ DB (chuẩn) + fallback từ FE khi cần
                $giaBan = (float) $sp->giaBan;
                $vatRaw = $sp->VAT ?? 0.08;
                $vat    = (float) ($vatRaw > 1 ? $vatRaw / 100.0 : $vatRaw); // 0..1

                // Giá FE gửi từ search (ưu tiên dùng)
                $giaSauVatFE  = isset($item['gia_sau_vat']) ? (int)$item['gia_sau_vat'] : $money($giaBan * (1 + $vat));
                $giaFE        = isset($item['gia'])         ? (int)$item['gia']         : $money($giaSauVatFE * (1 - (float)($sp->flash_sale ?? 0)));

                // Giảm theo dòng nếu gửi (ít dùng ở POS) — % (<=1) hoặc VND
                $giamRaw  = (float)($item['giamGia'] ?? 0);
                $giamLine = $giamRaw <= 0 ? 0 : ($giamRaw <= 1 ? $money($giaFE * $giamRaw) : $money($giamRaw));

                $donGiaSauGiam = max(0, $giaFE - $giamLine);
                $thanhTien     = $money($donGiaSauGiam * $sl);

                // cộng dồn tổng
                $tamTinh += $thanhTien;
                $tongVAT += $money($giaBan * $vat * $sl);                   // theo rule: giá gốc * vat * qty
                $giamFS  += $money(($giaSauVatFE - $giaFE) * $sl);          // giảm do flash (sau VAT)

                // snapshot chi tiết (giá lưu = đơn giá đã VAT / 1 sp)
                $snap = [
                    'san_pham_id'  => $sp->id,
                    'ten_san_pham' => $item['tenSanPham'] ?? $sp->tenSanPham,
                    'gia'          => $giaSauVatFE,     // đã VAT / 1 sp
                    'vat'          => $vat,             // 0..1
                    'giam_gia'     => 0,                // voucher cấp đơn → để 0
                    'so_luong'     => $sl,
                    'thanh_tien'   => $thanhTien,
                ];
                if ($hasFlashCol) {
                    // lưu tỉ lệ flash nếu FE gửi (hoặc từ DB), không bắt buộc
                    $flashRaw = $item['flash_sale'] ?? ($sp->flash_sale ?? 0);
                    $flash    = (float) ($flashRaw > 1 ? $flashRaw / 100.0 : $flashRaw);
                    $snap['flash_sale'] = $flash;
                }
                $chiTietSnapshots[] = $snap;
            }

            $tongThanhToan = $money($tamTinh - $giamVoucher - $giamDiem + $phiVanChuyen);

            // Tạo đơn hàng
            $donHang = DonHang::create([
                'id'                         => (string) Str::uuid(),
                'ma_don_hang'                => 'DH-' . now()->format('YmdHis'),
                'khach_hang_id'              => $data['khachHang_id'] ?? null,
                'ten_nguoi_nhan'             => $data['tenNguoiNhan'] ?? null,
                'so_dien_thoai'              => $data['soDienThoai'] ?? null,
                'tam_tinh'                   => $tamTinh,
                'giam_voucher'               => $giamVoucher,
                'giam_diem'                  => $giamDiem,
                'phi_van_chuyen'             => $phiVanChuyen,   // 0
                'tong_thanh_toan'            => $tongThanhToan,
                'trang_thai'                 => 'DA_THANH_TOAN',
                'phuong_thuc_thanh_toan'     => $phuongThucThanhToan, // cash | bank_transfer | credit_card
                'ngay_tao'                   => now(),
                'ngay_cap_nhat'              => now(),
            ]);

            // Chi tiết + trừ kho
            foreach ($chiTietSnapshots as $c) {
                ChiTietDonHang::create([
                    'id'            => (string) Str::uuid(),
                    'don_hang_id'   => $donHang->id,
                    'san_pham_id'   => $c['san_pham_id'],
                    'ten_san_pham'  => $c['ten_san_pham'],
                    'gia'           => $c['gia'],            // đã VAT / 1 sp
                    'vat'           => $c['vat'],            // 0..1
                    'giam_gia'      => 0,                    // bắt buộc 0 để tránh NULL
                    'so_luong'      => $c['so_luong'],
                    'thanh_tien'    => $c['thanh_tien'],
                    ...(isset($c['flash_sale']) ? ['flash_sale' => $c['flash_sale']] : []),
                ]);

                $sp = SanPham::lockForUpdate()->find($c['san_pham_id']);
                if (!$sp || (int)$sp->soLuongTon < (int)$c['so_luong']) {
                    DB::rollBack();
                    return response()->json(['error' => 'Không đủ tồn kho cho sản phẩm'], 400);
                }
                $sp->soLuongTon = (int)$sp->soLuongTon - (int)$c['so_luong'];
                $sp->save();
            }

            // Hóa đơn
            $today = now()->toDateString();
            $stt   = HoaDon::whereDate('ngay_xuat', $today)->lockForUpdate()->count() + 1;
            $maHD  = 'HD-' . now()->format('Ymd') . '-' . str_pad((string)$stt, 6, '0', STR_PAD_LEFT);

            $hoaDon = HoaDon::create([
                'id'                      => (string) Str::uuid(),
                'ma_hoa_don'              => $maHD,
                'don_hang_id'             => $donHang->id,
                'ngay_xuat'               => now(),
                'tong_tien_hang'          => $tamTinh,
                'tong_vat'                => $tongVAT,
                'giam_flash_sale'         => $giamFS,
                'giam_voucher'            => $giamVoucher,
                'giam_diem'               => $giamDiem,
                'phi_van_chuyen'          => $phiVanChuyen,
                'tong_thanh_toan'         => $tongThanhToan,
                'phuong_thuc_thanh_toan'  => $phuongThucThanhToan,
            ]);

            DB::commit();

            return response()->json([
                'message'             => 'Thanh toán thành công',
                'hoaDonId'            => $hoaDon->id,
                'phuongThucThanhToan' => $phuongThucThanhToan,
                'tongTienHang'        => $tamTinh,
                'tongVAT'             => $tongVAT,
                'giamFlashSale'       => $giamFS,
                'giamVoucher'         => $giamVoucher,
                'giamDiem'            => $giamDiem,
                'phiVanChuyen'        => $phiVanChuyen,
                'tongThanhToan'       => $tongThanhToan,
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    // Chuyển trạng thái CHO_XU_LY -> CHO_LAY_HANG
    public function moveToReadyForPickup(Request $request, string $id)
    {
        return DB::transaction(function () use ($id, $request) {
            $don = DonHang::lockForUpdate()
                ->with(['chiTietDonHang', 'khachHang'])
                ->find($id);

            if (!$don) {
                return response()->json(['message' => 'Không tìm thấy đơn hàng'], 404);
            }

            if ($don->trang_thai !== 'CHO_XU_LY') {
                return response()->json([
                    'message' => 'Chỉ có thể chuyển trạng thái từ CHO_XU_LY sang CHO_LAY_HANG',
                    'current' => $don->trang_thai,
                ], 422);
            }

            if ($don->chiTietDonHang->isEmpty()) {
                return response()->json([
                    'message' => 'Đơn hàng không có sản phẩm.'
                ], 422);
            }

            // Tổng giảm flash sale từ chi tiết
            $giamFlashSale = $don->chiTietDonHang->sum(function ($ct) {
                $gia   = (float) $ct->gia;                 // giá đã VAT
                $flash = (float) ($ct->flash_sale ?? 0);   // tỉ lệ 0..1
                $qty   = (int) $ct->so_luong;
                return round(($gia * $flash) * $qty, 2);
            });

            // Tổng giảm khác + phí vận chuyển từ đơn hàng
            $giamVoucher   = (float) ($don->giam_voucher ?? 0);
            $giamDiem      = (float) ($don->giam_diem ?? 0);
            $phiVanChuyen  = (float) ($don->phi_van_chuyen ?? 0);

            // Cập nhật trạng thái đơn hàng
            $don->update([
                'trang_thai'    => 'CHO_LAY_HANG',
                'ngay_cap_nhat' => now(),
                'ghi_chu'       => $request->input('ghi_chu', $don->ghi_chu),
            ]);

            return response()->json([
                'message' => 'Đơn hàng đã chuyển sang CHO_LAY_HANG.',
                'don_hang' => [
                    'id'                => $don->id,
                    'ma_don_hang'       => $don->ma_don_hang,
                    'khach_hang'        => $don->ten_nguoi_nhan,
                    'so_dien_thoai'     => $don->so_dien_thoai,
                    'tong_thanh_toan'   => (float) $don->tong_thanh_toan,  // lấy trực tiếp từ bảng đơn
                    'trang_thai'        => $don->trang_thai,
                    'dia_chi'           => $don->dia_chi,
                    'ghi_chu'           => $don->ghi_chu,
                    'tong_giam_gia'     => round($giamVoucher + $giamDiem + $giamFlashSale, 2),
                    'phi_van_chuyen'    => $phiVanChuyen,
                    'don_vi_van_chuyen' => $don->don_vi_van_chuyen,
                    'ma_van_don'        => $don->ma_van_don,
                ],
            ]);
        });
    }

    // Chuyển trạng thái CHO_LAY_HANG -> DANG_GIAO_HANG
    public function moveToShipping(Request $request, string $id)
    {
        return DB::transaction(function () use ($id, $request) {

            // Helper làm tròn tiền về đồng (half up)
            $money = function (float|int $v): int {
                return (int) round((float) $v, 0, PHP_ROUND_HALF_UP);
            };

            $don = DonHang::lockForUpdate()
                ->with(['khachHang', 'chiTietDonHang'])
                ->find($id);

            if (!$don) {
                return response()->json(['message' => 'Không tìm thấy đơn hàng'], 404);
            }

            if ($don->trang_thai !== 'CHO_LAY_HANG') {
                return response()->json([
                    'message' => 'Chỉ có thể chuyển trạng thái từ CHO_LAY_HANG sang DANG_GIAO_HANG',
                    'current' => $don->trang_thai,
                ], 422);
            }

            if (empty($don->ma_van_don)) {
                return response()->json([
                    'message' => 'Chưa có mã vận đơn. Vui lòng tạo vận đơn trước khi giao hàng.',
                ], 422);
            }

            if ($don->chiTietDonHang->isEmpty()) {
                return response()->json([
                    'message' => 'Đơn hàng không có sản phẩm.'
                ], 422);
            }

            // --- Chỉ tính các khoản hiển thị; KHÔNG đụng tổng thanh toán ---
            // Tổng giảm flash (trên giá đã VAT): sum(gia * flash * qty) => LÀM TRÒN TỔNG
            $rawFlash = $don->chiTietDonHang->reduce(function ($sum, $ct) {
                $gia   = (float) $ct->gia;                 // đơn giá ĐÃ VAT / 1 sp
                $flash = (float) ($ct->flash_sale ?? 0);   // 0..1 hoặc %
                if ($flash > 1) $flash = $flash / 100;     // normalize
                $qty   = (int)   $ct->so_luong;
                return $sum + ($gia * $flash * $qty);
            }, 0.0);
            $giamFlashSale = $money($rawFlash);

            $giamVoucher   = (float) ($don->giam_voucher ?? 0);
            $giamDiem      = (float) ($don->giam_diem ?? 0);
            $phiVanChuyen  = (float) ($don->phi_van_chuyen ?? 0);
            $tongThanhToan = (float)  $don->tong_thanh_toan; // giữ nguyên từ đơn hàng

            // Suy ra tổng tiền hàng (đã VAT, đã trừ flash) từ đơn hàng
            $tongTienHang = round($tongThanhToan + $giamVoucher + $giamDiem - $phiVanChuyen, 2);

            // --- TỔNG VAT = GIÁ GỐC (SanPham.giaBan) * VAT * SỐ LƯỢNG => LÀM TRÒN TỔNG ---
            // Lấy map giá gốc cho tất cả sản phẩm trong đơn (tránh N+1)
            $spPrices = SanPham::whereIn('id', $don->chiTietDonHang->pluck('san_pham_id'))
                ->pluck('giaBan', 'id');

            $rawVat = $don->chiTietDonHang->reduce(function ($sum, $ct) use ($spPrices) {
                $giaGoc = (float) ($spPrices[$ct->san_pham_id] ?? 0); // giá bán ở bảng sản phẩm
                $vat    = (float) ($ct->vat ?? 0);                    // 0..1 hoặc %
                if ($vat > 1) $vat = $vat / 100;                      // normalize
                $qty    = (int)   $ct->so_luong;
                return $sum + ($giaGoc * $vat * $qty);
            }, 0.0);
            $tongVAT = $money($rawVat);

            // ---- TẠO / CẬP NHẬT HÓA ĐƠN ----
            $hoaDon = HoaDon::where('don_hang_id', $don->id)->first();
            if (!$hoaDon) {
                $maHoaDon = 'HD-' . now()->year . '-' . str_pad(random_int(1, 999999), 6, '0', STR_PAD_LEFT);
                $hoaDon = HoaDon::create([
                    'id'                      => (string) Str::uuid(),
                    'ma_hoa_don'              => $maHoaDon,
                    'don_hang_id'             => $don->id,
                    'ngay_xuat'               => now(),
                    'tong_tien_hang'          => $tongTienHang,              // có thể giữ 2 chữ số
                    'tong_vat'                => $tongVAT,                   // ✅ số nguyên (đồng)
                    'giam_flash_sale'         => $giamFlashSale,             // ✅ số nguyên (đồng)
                    'giam_voucher'            => $giamVoucher,               // giữ nguyên theo đơn hàng
                    'giam_diem'               => $giamDiem,                  // giữ nguyên theo đơn hàng
                    'phi_van_chuyen'          => $phiVanChuyen,
                    'tong_thanh_toan'         => $tongThanhToan,
                    'phuong_thuc_thanh_toan'  => $don->phuong_thuc_thanh_toan,
                ]);
            } else {
                $hoaDon->update([
                    'tong_tien_hang'  => $tongTienHang,
                    'tong_vat'        => $tongVAT,           // ✅ số nguyên
                    'giam_flash_sale' => $giamFlashSale,     // ✅ số nguyên
                    'giam_voucher'    => $giamVoucher,
                    'giam_diem'       => $giamDiem,
                    'phi_van_chuyen'  => $phiVanChuyen,
                    'tong_thanh_toan' => $tongThanhToan,
                ]);
            }

            // ---- TRỪ KHO (chỉ 1 lần nếu có cột da_tru_kho) ----
            $canMarkDeducted = Schema::hasColumn('donhang', 'da_tru_kho');
            $alreadyDeducted = $canMarkDeducted ? (bool)$don->da_tru_kho : false;

            if (!$alreadyDeducted) {
                // Kiểm tra tồn
                foreach ($don->chiTietDonHang as $ct) {
                    $sp = SanPham::lockForUpdate()->find($ct->san_pham_id);
                    if (!$sp) {
                        return response()->json(['message' => "Không tìm thấy sản phẩm {$ct->san_pham_id}"], 422);
                    }
                    if ((int)$sp->soLuongTon < (int)$ct->so_luong) {
                        return response()->json([
                            'message' => "Sản phẩm {$sp->tenSanPham} không đủ tồn (còn {$sp->soLuongTon}, cần {$ct->so_luong})."
                        ], 422);
                    }
                }
                // Trừ kho
                foreach ($don->chiTietDonHang as $ct) {
                    $sp = SanPham::lockForUpdate()->find($ct->san_pham_id);
                    $sp->soLuongTon = (int)$sp->soLuongTon - (int)$ct->so_luong;
                    $sp->save();
                }

                if ($canMarkDeducted) {
                    $don->da_tru_kho = true;
                }
            }

            // Cập nhật trạng thái đơn sang DANG_GIAO_HANG
            $don->update([
                'trang_thai'    => 'DANG_GIAO_HANG',
                'ngay_cap_nhat' => now(),
                'ghi_chu'       => $request->input('ghi_chu', $don->ghi_chu),
            ]);

            Log::info('Đơn hàng đã chuyển sang DANG_GIAO_HANG', [
                'don_hang_id' => $don->id,
                'by'          => auth()->id(),
            ]);

            return response()->json([
                'message' => 'Đơn hàng đã chuyển sang trạng thái DANG_GIAO_HANG.',
                'don_hang' => [
                    'id'                => $don->id,
                    'ma_don_hang'       => $don->ma_don_hang,
                    'khach_hang'        => $don->ten_nguoi_nhan,
                    'so_dien_thoai'     => $don->so_dien_thoai,
                    'tong_thanh_toan'   => (float) $don->tong_thanh_toan,
                    'trang_thai'        => $don->trang_thai,
                    'dia_chi'           => $don->dia_chi,
                    'ghi_chu'           => $don->ghi_chu,
                    'phi_van_chuyen'    => (float) ($don->phi_van_chuyen ?? 0),
                    'don_vi_van_chuyen' => $don->don_vi_van_chuyen,
                    'ma_van_don'        => $don->ma_van_don,
                ],
            ]);
        });
    }

    // Danh sách CHO_LAY_HANG 
    public function dsDonHang(Request $request)
    {
        // ----- Parse bộ lọc thời gian -----
        $range = $request->input('range'); 
        $tz    = config('app.timezone', 'Asia/Ho_Chi_Minh');
        $now   = Carbon::now($tz);

        $start = null;
        $end   = null;

        switch ($range) {
            case 'hom_nay':    $start = $now->copy()->startOfDay();             $end = $now->copy()->endOfDay(); break;
            case 'hom_qua':    $start = $now->copy()->subDay()->startOfDay();   $end = $now->copy()->subDay()->endOfDay(); break;
            case 'tuan_nay':   $start = $now->copy()->startOfWeek();            $end = $now->copy()->endOfWeek(); break;
            case 'tuan_truoc': $start = $now->copy()->subWeek()->startOfWeek(); $end = $now->copy()->subWeek()->endOfWeek(); break;
            case 'thang_nay':  $start = $now->copy()->startOfMonth();           $end = $now->copy()->endOfMonth(); break;
            case 'thang_truoc':$start = $now->copy()->subMonth()->startOfMonth();$end= $now->copy()->subMonth()->endOfMonth(); break;
        }

        $q = DonHang::with([
                'chiTietDonHang' => function ($q) {
                    $q->select([
                        'id','don_hang_id','san_pham_id','ten_san_pham',
                        'gia','vat','so_luong','giam_gia','flash_sale','thanh_tien',
                    ]);
                },
                'chiTietDonHang.sanPham:id,hinhAnh'
            ])
            ->where('trang_thai', 'CHO_LAY_HANG')
            ->orderByDesc('ngay_tao');

        if ($start && $end) {
            $q->whereBetween('ngay_tao', [$start, $end]);
        }
        if ($search = trim((string)$request->input('q', ''))) {
            $q->where('ma_don_hang', 'like', "%{$search}%");
        }

        $orders = $q->get();
        $hasDonHangWeight = Schema::hasColumn('donhang', 'khoi_luong') || Schema::hasColumn('DonHang', 'khoi_luong');

        $data = $orders->map(function ($don) use ($hasDonHangWeight) {
            // Lấy trực tiếp từ bảng đơn hàng
            $tongTienHang   = (float) ($don->tam_tinh ?? 0);
            $giamVoucher    = (float) ($don->giam_voucher ?? 0);
            $giamDiem       = (float) ($don->giam_diem ?? 0);
            $phiVanChuyen   = (float) ($don->phi_van_chuyen ?? 0);
            $tongThanhToan  = (float) ($don->tong_thanh_toan ?? 0);

            // Tính flash sale & VAT từ chi tiết (giữ để hiển thị breakdown)
            $giamFlashSale = $don->chiTietDonHang->sum(function ($ct) {
                return round(((float)$ct->gia * (float)($ct->flash_sale ?? 0)) * (int)$ct->so_luong, 2);
            });
            $tongVAT = $don->chiTietDonHang->sum(function ($ct) {
                $gia = (float)$ct->gia; $vat = (float)($ct->vat ?? 0); $qty = (int)$ct->so_luong;
                return round(($gia * $vat / (100 + $vat)) * $qty, 2);
            });

            $items = $don->chiTietDonHang->map(function ($ct) {
                return [
                    'san_pham_id'  => $ct->san_pham_id,
                    'ten_san_pham' => $ct->ten_san_pham,
                    'hinh_anh'     => optional($ct->sanPham)->hinhAnh ?? null,
                    'so_luong'     => (int) $ct->so_luong,
                    'gia'          => (float) $ct->gia,
                    'thanh_tien'   => (float) $ct->thanh_tien,
                    'vat'          => (float) ($ct->vat ?? 0),
                    'giam_gia'     => (float) ($ct->giam_gia ?? 0),
                ];
            });

            $khoiLuong = $hasDonHangWeight ? (float) ($don->khoi_luong ?? 0) : null;

            // ---- Phương thức thanh toán + quy tắc hiển thị ----
            $ptttRaw = $don->phuong_thuc_thanh_toan ?? $don->phuong_thuc ?? '';
            $pttt    = strtolower((string)$ptttRaw);
            $isOnlineGateway = in_array($pttt, ['vnpay','momo'], true);

            // *** TỔNG GIẢM GIÁ: chỉ từ voucher + điểm ***
            $onlyOrderLevelDiscount = round($giamVoucher + $giamDiem, 2);

            // Giá trị hiển thị sau quy tắc (giữ nguyên rule ẩn cho online nếu bạn đang dùng)
            $displayTongThanhToan = $isOnlineGateway ? 0.0 : $tongThanhToan;
            $displayPhiVanChuyen  = $isOnlineGateway ? 0.0 : $phiVanChuyen;
            $displayTongGiamGia   = $isOnlineGateway ? 0.0 : $onlyOrderLevelDiscount;

            $breakdown = $isOnlineGateway
                ? ['tong_vat' => 0.0, 'giam_flash_sale' => 0.0, 'giam_voucher' => 0.0, 'giam_diem' => 0.0]
                : ['tong_vat' => $tongVAT, 'giam_flash_sale' => $giamFlashSale, 'giam_voucher' => $giamVoucher, 'giam_diem' => $giamDiem];

            return [
                'id'                      => $don->id,
                'ma_don_hang'             => $don->ma_don_hang,
                'ten_khach_hang'          => $don->ten_nguoi_nhan,
                'so_dien_thoai'           => $don->so_dien_thoai,
                'tong_thanh_toan'         => $displayTongThanhToan,
                'trang_thai'              => $don->trang_thai,
                'dia_chi_giao_hang'       => $don->dia_chi,
                'ghi_chu'                 => $don->ghi_chu,
                'ma_van_don'              => $don->ma_van_don,
                'khoi_luong'              => $khoiLuong,
                'tong_tien'               => $tongTienHang,
                // 👇 chỉ tính từ voucher + điểm
                'tong_giam_gia'           => $displayTongGiamGia,
                'phi_van_chuyen'          => $displayPhiVanChuyen,
                'san_pham'                => $items,
                'breakdown'               => $breakdown,
                'hoa_don'                 => null,
                'ngay_tao'                => $don->ngay_tao,
                'phuong_thuc_thanh_toan'  => $ptttRaw,
            ];
        });

        return response()->json([
            'range' => $range,
            'count' => $data->count(),
            'data'  => $data,
        ], 200);
    }

    // Danh sách CHO_XU_LY 
    public function dsChoXuLy(Request $request)
    {
        $orders = DonHang::with([
                'chiTietDonHang' => function ($q) {
                    $q->select([
                        'id', 'don_hang_id', 'san_pham_id',
                        'ten_san_pham', 'so_luong', 'gia', 'thanh_tien'
                    ]);
                },
                'chiTietDonHang.sanPham:id,hinhAnh',
            ])
            ->where('trang_thai', 'CHO_XU_LY')
            ->orderByDesc('ngay_tao')
            ->get();

        $data = $orders->map(function ($don) {
            $items = $don->chiTietDonHang->map(function ($ct) {
                return [
                    'hinh_anh'   => optional($ct->sanPham)->hinhAnh ?? null,
                    'ten'        => $ct->ten_san_pham,
                    'so_luong'   => (int) $ct->so_luong,
                    'don_gia'    => (float) $ct->gia,
                    'thanh_tien' => (float) $ct->thanh_tien,
                ];
            });

            return [
                'id'              => $don->id,
                'ma_don_hang'     => $don->ma_don_hang,
                'ma_van_don'      => $don->ma_van_don,
                'ngay_tao'        => $don->ngay_tao,
                'tong_thanh_toan' => (float) ($don->tong_thanh_toan ?? 0),
                'ten_khach_hang'  => $don->ten_nguoi_nhan,
                'so_dien_thoai'   => $don->so_dien_thoai,
                'dia_chi'         => $don->dia_chi,
                'san_pham'        => $items,
            ];
        });

        return response()->json([
            'count' => $data->count(),
            'data'  => $data,
        ], 200);
    }

}