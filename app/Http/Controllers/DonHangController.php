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

            // Map FE -> DB
            $map = ['cod' => 'cod', 'bank' => 'bank_transfer', 'card' => 'credit_card'];
            $phuongThucThanhToan = $map[$data['phuongThuc']] ?? 'cod';

            $tamTinh   = 0.0;
            $tongVAT   = 0.0;          // VAT tính theo "VAT trước flash"
            $giamFS    = 0.0;          // giam_flash_sale
            $chiTietSnapshots = [];

            $giamVoucher = (float)($data['giamVoucher'] ?? 0);
            $giamDiem    = (float)($data['giamDiem'] ?? 0);

            // Offline: dùng phiCOD làm phí vận chuyển cho công thức chung (giống seeder)
            $phiVanChuyen = $data['phuongThuc'] === 'cod' ? (float)($data['phiCOD'] ?? 0) : 0.0;

            $hasFlashCol = \Illuminate\Support\Facades\Schema::hasColumn('chitietdonhang', 'flash_sale');

            foreach ($data['sanPhams'] as $item) {
                // Input tối thiểu: id, soLuong. Có thể override giaBan, VAT, flashSale, giamGia
                $sp      = SanPham::lockForUpdate()->find($item['id'] ?? null);
                if (!$sp) {
                    DB::rollBack();
                    return response()->json(['error' => 'Không tìm thấy sản phẩm'], 404);
                }

                $sl        = (int)($item['soLuong'] ?? 0);
                if ($sl <= 0) continue;

                // Kiểm kho
                if ((int)$sp->soLuongTon < $sl) {
                    DB::rollBack();
                    return response()->json(['error' => 'Không đủ tồn kho cho sản phẩm ' . ($sp->tenSanPham ?? $sp->id)], 400);
                }

                $giaGoc    = isset($item['giaBan']) ? (float)$item['giaBan'] : (float)$sp->giaBan;
                $vatPct    = isset($item['VAT']) ? (float)$item['VAT'] : (float)($sp->VAT ?? 8.0);
                $flash     = isset($item['flashSale']) ? (float)$item['flashSale'] : (float)($sp->flash_sale ?? 0.0);
                $flash     = max(0.0, min(0.9, $flash)); // clamp 0..0.9
                $giamRaw   = (float)($item['giamGia'] ?? 0.0);

                // 1) VAT trước: giá sau VAT
                $giaSauVat = round($giaGoc * (1 + $vatPct / 100), 2);

                // 2) Flash sale sau VAT
                $giaSauFlash = round($giaSauVat * (1 - $flash), 2);

                // 3) Giảm giá dòng: percent nếu 0..1, ngược lại VND
                if ($giamRaw > 0 && $giamRaw <= 1) {
                    $giamLine = round($giaSauFlash * $giamRaw, 2);
                } else {
                    $giamLine = round($giamRaw, 2);
                }

                // 4) Đơn giá sau giảm (không âm)
                $donGiaSauGiam = max(0, round($giaSauFlash - $giamLine, 2));

                // 5) Thành tiền dòng
                $thanhTien = round($donGiaSauGiam * $sl, 2);

                // Cộng dồn
                $tamTinh += $thanhTien;

                // VAT trước flash (VAT ẩn trong "giá sau VAT")
                $vat1sp = $giaSauVat * $vatPct / (100 + $vatPct); // VAT phần đơn giá (1 sp)
                $tongVAT += round($vat1sp * $sl, 2);

                // Giảm do flash: (giaSauVat - giaSauFlash) * sl
                $giamFS += round(($giaSauVat - $giaSauFlash) * $sl, 2);

                // Ghi snapshot để tạo chi tiết đơn
                $snap = [
                    'san_pham_id'  => $sp->id,
                    'ten_san_pham' => $item['tenSanPham'] ?? $sp->tenSanPham,
                    'gia'          => $giaSauFlash, // đơn giá sau VAT & flash (trước giảm)
                    'vat'          => $vatPct,
                    'giam_gia'     => $giamLine,   // lưu giá trị VND đã áp cho 1 sp
                    'so_luong'     => $sl,
                    'thanh_tien'   => $thanhTien,
                ];
                if ($hasFlashCol) {
                    $snap['flash_sale'] = $flash; // snapshot tỉ lệ 0..1
                }
                $chiTietSnapshots[] = $snap;
            }

            // Tổng kết
            $tamTinh         = round($tamTinh, 2);
            $tongVAT         = round($tongVAT, 2);
            $giamFS          = round($giamFS, 2);
            $tongThanhToan   = round($tamTinh - $giamVoucher - $giamDiem + $phiVanChuyen, 2);

            // Tạo DonHang
            $donHang = DonHang::create([
                'id'                         => (string) Str::uuid(),
                'ma_don_hang'                => 'DH-' . now()->format('YmdHis'),
                'khach_hang_id'              => $data['khachHang_id'],
                'ten_nguoi_nhan'             => $data['tenNguoiNhan'],
                'so_dien_thoai'              => $data['soDienThoai'],
                'tam_tinh'                   => $tamTinh,
                'giam_voucher'               => $giamVoucher,
                'giam_diem'                  => $giamDiem,
                'phi_van_chuyen'             => $phiVanChuyen,     // = phiCOD (offline) để khớp công thức
                'tong_thanh_toan'            => $tongThanhToan,
                'trang_thai'                 => 'DA_THANH_TOAN',
                'phuong_thuc_thanh_toan'     => $phuongThucThanhToan,
                'ngay_tao'                   => now(),
                'ngay_cap_nhat'              => now(),
            ]);

            // Tạo chi tiết + trừ kho
            foreach ($chiTietSnapshots as $c) {
                ChiTietDonHang::create([
                    'id'            => (string) Str::uuid(),
                    'don_hang_id'   => $donHang->id,
                    'san_pham_id'   => $c['san_pham_id'],
                    'ten_san_pham'  => $c['ten_san_pham'],
                    'gia'           => $c['gia'],
                    'vat'           => $c['vat'],
                    'giam_gia'      => $c['giam_gia'],
                    'so_luong'      => $c['so_luong'],
                    'thanh_tien'    => $c['thanh_tien'],
                    // nếu có cột flash_sale
                    ...(isset($c['flash_sale']) ? ['flash_sale' => $c['flash_sale']] : [])
                ]);

                $sp = SanPham::lockForUpdate()->find($c['san_pham_id']);
                if (!$sp || (int)$sp->soLuongTon < (int)$c['so_luong']) {
                    DB::rollBack();
                    return response()->json(['error' => 'Không đủ tồn kho cho sản phẩm'], 400);
                }
                $sp->soLuongTon = (int)$sp->soLuongTon - (int)$c['so_luong'];
                $sp->save();
            }

            // Tạo mã HĐ theo ngày
            $today = now()->toDateString();
            $stt   = HoaDon::whereDate('ngay_xuat', $today)->lockForUpdate()->count() + 1;
            $maHD  = 'HD-' . now()->format('Ymd') . '-' . str_pad((string)$stt, 6, '0', STR_PAD_LEFT);

            // Tạo Hóa đơn (có giam_flash_sale)
            $hoaDon = HoaDon::create([
                'id'                      => (string) Str::uuid(),
                'ma_hoa_don'              => $maHD,
                'don_hang_id'             => $donHang->id,
                'ngay_xuat'               => now(),
                'tong_tien_hang'          => $tamTinh,
                'tong_vat'                => $tongVAT,        // VAT trước flash
                'giam_flash_sale'         => $giamFS,         // ✅ mới
                'giam_voucher'            => $giamVoucher,
                'giam_diem'               => $giamDiem,
                'phi_van_chuyen'          => $phiVanChuyen,   // = phiCOD (offline)
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
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

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
                    'message' => 'Không thể tạo hóa đơn vì đơn hàng không có sản phẩm.'
                ], 422);
            }

            // Tính tổng tiền hàng (đã VAT, đã trừ flash)
            $tongTienHang = $don->chiTietDonHang->sum(function ($ct) {
                $gia = (float) $ct->gia;             // đã VAT, chưa flash
                $flash = (float) ($ct->flash_sale ?? 0);
                $qty = (int) $ct->so_luong;

                $giaSauFlash = $gia * (1 - $flash);  // giá đã VAT sau flash
                return round($giaSauFlash * $qty, 2);
            });

            // Tổng VAT thực tế
            $tongVAT = $don->chiTietDonHang->sum(function ($ct) {
                $gia = (float) $ct->gia;
                $vat = (float) $ct->vat;
                $qty = (int) $ct->so_luong;

                return round(($gia * $vat / (100 + $vat)) * $qty, 2);
            });

            // Tổng giảm flash sale
            $giamFlashSale = $don->chiTietDonHang->sum(function ($ct) {
                $gia = (float) $ct->gia;
                $flash = (float) ($ct->flash_sale ?? 0);
                $qty = (int) $ct->so_luong;

                return round(($gia * $flash) * $qty, 2);
            });

            // Tổng giảm giá khác
            $giamVoucher = (float) ($don->giam_voucher ?? 0);
            $giamDiem    = (float) ($don->giam_diem ?? 0);
            $phiVanChuyen = (float) ($don->phi_van_chuyen ?? 0);

            // Tổng thanh toán cuối
            $tongThanhToan = $tongTienHang - $giamVoucher - $giamDiem + $phiVanChuyen;

            // Tạo hoặc cập nhật hóa đơn
            $hoaDon = HoaDon::where('don_hang_id', $don->id)->first();
            if (!$hoaDon) {
                $maHoaDon = 'HD-' . now()->year . '-' . str_pad(rand(1, 999999), 6, '0', STR_PAD_LEFT);

                $hoaDon = HoaDon::create([
                    'id'                      => (string) Str::uuid(),
                    'ma_hoa_don'             => $maHoaDon,
                    'don_hang_id'            => $don->id,
                    'ngay_xuat'              => now(),
                    'tong_tien_hang'         => round($tongTienHang, 2),
                    'tong_vat'               => round($tongVAT, 2),
                    'giam_flash_sale'        => round($giamFlashSale, 2),
                    'giam_voucher'           => round($giamVoucher, 2),
                    'giam_diem'              => round($giamDiem, 2),
                    'phi_van_chuyen'         => round($phiVanChuyen, 2),
                    'tong_thanh_toan'        => round($tongThanhToan, 2),
                    'phuong_thuc_thanh_toan' => $don->phuong_thuc_thanh_toan,
                ]);
            } else {
                $hoaDon->update([
                    'tong_tien_hang'   => round($tongTienHang, 2),
                    'tong_vat'         => round($tongVAT, 2),
                    'giam_flash_sale'  => round($giamFlashSale, 2),
                    'tong_thanh_toan'  => round($tongThanhToan, 2),
                ]);
            }

            // Cập nhật trạng thái đơn hàng
            $don->update([
                'trang_thai'    => 'CHO_LAY_HANG',
                'ngay_cap_nhat' => now(),
                'ghi_chu'       => $request->input('ghi_chu', $don->ghi_chu),
                'tong_thanh_toan' => $tongThanhToan, // đồng bộ luôn tổng tiền
            ]);

            return response()->json([
                'message' => 'Đơn hàng đã chuyển sang CHO_LAY_HANG và hóa đơn đã được tạo.',
                'don_hang' => [
                    'id'                => $don->id,
                    'ma_don_hang'       => $don->ma_don_hang,
                    'khach_hang'        => $don->khachHang->hoTen ?? 'Khách lẻ',
                    'tong_thanh_toan'   => $tongThanhToan,
                    'trang_thai'        => $don->trang_thai,
                    'dia_chi'           => $don->dia_chi,
                    'ghi_chu'           => $don->ghi_chu,
                    'tong_giam_gia'     => round($giamVoucher + $giamDiem + $giamFlashSale, 2),
                    'phi_van_chuyen'    => $phiVanChuyen,
                    'don_vi_van_chuyen' => $don->don_vi_van_chuyen,
                    'ma_van_don'        => $don->ma_van_don,
                ],
                'hoa_don' => [
                    'ma_hoa_don'        => $hoaDon->ma_hoa_don,
                    'tong_tien_hang'    => (float)$hoaDon->tong_tien_hang,
                    'tong_vat'          => (float)$hoaDon->tong_vat,
                    'giam_flash_sale'   => (float)$hoaDon->giam_flash_sale,
                    'giam_voucher'      => (float)$hoaDon->giam_voucher,
                    'giam_diem'         => (float)$hoaDon->giam_diem,
                    'phi_van_chuyen'    => (float)$hoaDon->phi_van_chuyen,
                    'tong_thanh_toan'   => (float)$hoaDon->tong_thanh_toan,
                    'phuong_thuc'       => $hoaDon->phuong_thuc_thanh_toan,
                    'ngay_xuat'         => $hoaDon->ngay_xuat,
                ]
            ]);
        });
    }

    public function moveToShipping(Request $request, string $id)
    {
        return DB::transaction(function () use ($id, $request) {
            // Khóa bản ghi để tránh race condition khi nhiều thao tác cùng lúc
            $don = DonHang::lockForUpdate()
                ->with(['khachHang'])
                ->find($id);

            if (!$don) {
                return response()->json(['message' => 'Không tìm thấy đơn hàng'], 404);
            }

            // Kiểm tra trạng thái hiện tại
            if ($don->trang_thai !== 'CHO_LAY_HANG') {
                return response()->json([
                    'message' => 'Chỉ có thể chuyển trạng thái từ CHO_LAY_HANG sang DANG_GIAO_HANG',
                    'current' => $don->trang_thai,
                ], 422);
            }

            // Kiểm tra mã vận đơn trước khi giao hàng
            if (empty($don->ma_van_don)) {
                return response()->json([
                    'message' => 'Chưa có mã vận đơn. Vui lòng tạo vận đơn trước khi giao hàng.',
                ], 422);
            }

            // Cập nhật trạng thái đơn sang DANG_GIAO_HANG
            $don->update([
                'trang_thai'    => 'DANG_GIAO_HANG',
                'ngay_cap_nhat' => now(),
                'ghi_chu'       => $request->input('ghi_chu', $don->ghi_chu),
            ]);

            // Ghi log lại hành động (nếu muốn theo dõi lịch sử)
            Log::info('Đơn hàng đã chuyển sang DANG_GIAO_HANG', [
                'don_hang_id' => $don->id,
                'by'          => auth()->id(),
            ]);

            // Response JSON trả về
            return response()->json([
                'message' => 'Đơn hàng đã chuyển sang trạng thái DANG_GIAO_HANG.',
                'don_hang' => [
                    'id'                => $don->id,
                    'ma_don_hang'       => $don->ma_don_hang,
                    'khach_hang'        => $don->khachHang->hoTen ?? 'Khách lẻ',
                    'tong_thanh_toan'   => (float) $don->tong_thanh_toan,
                    'trang_thai'        => $don->trang_thai,
                    'dia_chi'           => $don->dia_chi,
                    'ghi_chu'           => $don->ghi_chu,
                    'phi_van_chuyen'    => (float) ($don->phi_van_chuyen ?? 0),
                    'don_vi_van_chuyen' => $don->don_vi_van_chuyen,
                    'ma_van_don'        => $don->ma_van_don,
                ]
            ]);
        });
    }

    public function dsDonHang(Request $request)
    {
        // ----- Parse bộ lọc thời gian -----
        $range = $request->input('range'); 
        $tz    = config('app.timezone', 'Asia/Ho_Chi_Minh');
        $now   = Carbon::now($tz);

        $start = null;
        $end   = null;

        switch ($range) {
            case 'hom_nay':
                $start = $now->copy()->startOfDay();
                $end   = $now->copy()->endOfDay();
                break;
            case 'hom_qua':
                $start = $now->copy()->subDay()->startOfDay();
                $end   = $now->copy()->subDay()->endOfDay();
                break;
            case 'tuan_nay':
                $start = $now->copy()->startOfWeek();
                $end   = $now->copy()->endOfWeek();
                break;
            case 'tuan_truoc':
                $start = $now->copy()->subWeek()->startOfWeek();
                $end   = $now->copy()->subWeek()->endOfWeek();
                break;
            case 'thang_nay':
                $start = $now->copy()->startOfMonth();
                $end   = $now->copy()->endOfMonth();
                break;
            case 'thang_truoc':
                $start = $now->copy()->subMonth()->startOfMonth();
                $end   = $now->copy()->subMonth()->endOfMonth();
                break;
        }

        // ----- Base query: chỉ đơn CHO_LAY_HANG -----
        $q = DonHang::with([
                'khachHang:id,hoTen,sdt',
                'chiTietDonHang' => function ($q) {
                    $q->select([
                        'id','don_hang_id','san_pham_id','ten_san_pham',
                        'gia','vat','so_luong','giam_gia','flash_sale','thanh_tien', // có thể thiếu cột nào đó -> getAttribute() bên dưới
                    ]);
                },
                'chiTietDonHang.sanPham:id,hinhAnh' // ảnh từ bảng SanPham
            ])
            ->where('trang_thai', 'CHO_LAY_HANG')
            ->orderByDesc('ngay_tao');

        if ($start && $end) {
            $q->whereBetween('ngay_tao', [$start, $end]);
        }

        // ----- Tìm kiếm nhanh theo mã đơn -----
        if ($search = trim((string)$request->input('q', ''))) {
            $q->where('ma_don_hang', 'like', "%{$search}%");
        }

        $orders = $q->get();

        $hasDonHangWeight = Schema::hasColumn('donhang', 'khoi_luong') || Schema::hasColumn('DonHang', 'khoi_luong');

        // Map dữ liệu trả về
        $data = $orders->map(function ($don) use ($hasDonHangWeight) {
            // Hóa đơn (lấy trực tiếp các tổng)
            $hd = $don->hoaDon; // có thể null nếu chưa phát hành hóa đơn
            $tongTienHang   = (float) optional($hd)->tong_tien_hang;
            $tongVAT        = (float) optional($hd)->tong_vat;
            $giamFlashSale  = (float) optional($hd)->giam_flash_sale;
            $giamVoucher    = (float) optional($hd)->giam_voucher;
            $giamDiem       = (float) optional($hd)->giam_diem;
            $phiVanChuyen   = (float) optional($hd)->phi_van_chuyen;
            $tongThanhToan  = (float) optional($hd)->tong_thanh_toan;

            // Danh sách sản phẩm: GIỮ NGUYÊN 'gia' và 'thanh_tien' từ chi tiết
            $items = $don->chiTietDonHang->map(function ($ct) {
                return [
                    'san_pham_id'  => $ct->san_pham_id,
                    'ten_san_pham' => $ct->ten_san_pham,
                    'hinh_anh'     => optional($ct->sanPham)->hinhAnh ?? null,
                    'so_luong'     => (int) $ct->so_luong,
                    'gia'          => (float) $ct->gia,          // GIỮ NGUYÊN
                    'thanh_tien'   => (float) $ct->thanh_tien,   // GIỮ NGUYÊN
                    'vat'          => (float) ($ct->vat ?? 0),
                    'giam_gia'     => (float) ($ct->giam_gia ?? 0),
                ];
            });

            // Khối lượng đơn (chỉ đọc trực tiếp nếu có cột trên bảng đơn)
            $khoiLuong = $hasDonHangWeight ? (float) ($don->khoi_luong ?? 0) : null;

            return [
                // Các trường yêu cầu hiển thị
                'ma_don_hang'       => $don->ma_don_hang,
                'ten_khach_hang'    => optional($don->khachHang)->hoTen ?? 'Khách lẻ',
                'tong_thanh_toan'   => $tongThanhToan,
                'trang_thai'        => $don->trang_thai, // CHO_LAY_HANG
                'dia_chi_giao_hang' => $don->dia_chi,
                'ghi_chu'           => $don->ghi_chu,
                'ma_van_don'        => $don->ma_van_don,
                'khoi_luong'        => $khoiLuong,       // null nếu không có cột
                'tong_tien'         => $tongTienHang,    // từ bảng hóa đơn
                // Tổng giảm giá = cộng 3 cột giảm (nếu muốn giữ nguyên từng cột, FE cộng cũng được)
                'tong_giam_gia'     => round($giamFlashSale + $giamVoucher + $giamDiem, 2),
                'phi_van_chuyen'    => $phiVanChuyen,    // từ bảng hóa đơn
                'san_pham'          => $items,

                // Thêm phần breakdown (nếu FE cần hiển thị chi tiết)
                'breakdown' => [
                    'tong_vat'        => $tongVAT,
                    'giam_flash_sale' => $giamFlashSale,
                    'giam_voucher'    => $giamVoucher,
                    'giam_diem'       => $giamDiem,
                ],

                // Thông tin tham chiếu hóa đơn (tuỳ ý dùng)
                'hoa_don' => $hd ? [
                    'ma_hoa_don'  => $hd->ma_hoa_don,
                    'ngay_xuat'   => $hd->ngay_xuat,
                    'phuong_thuc' => $hd->phuong_thuc_thanh_toan,
                ] : null,

                'ngay_tao'          => $don->ngay_tao,
            ];
        });

        return response()->json([
            'range' => $range,
            'count' => $data->count(),
            'data'  => $data,
        ], 200);
    }

    public function dsChoXuLy(Request $request)
    {
        // Eager-load các quan hệ cần thiết
        $orders = DonHang::with([
                'khachHang:id,hoTen,sdt',
                'chiTietDonHang' => function ($q) {
                    $q->select([
                        'id', 'don_hang_id', 'san_pham_id',
                        'ten_san_pham', 'so_luong', 'gia', 'thanh_tien'
                    ]);
                },
                'chiTietDonHang.sanPham:id,hinhAnh',
                'hoaDon:don_hang_id,tong_thanh_toan',
            ])
            ->where('trang_thai', 'CHO_XU_LY')
            ->orderByDesc('ngay_tao')
            ->get();

        $data = $orders->map(function ($don) {
            $tongThanhToan = $don->tong_thanh_toan ?? optional($don->hoaDon)->tong_thanh_toan ?? 0;

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
                'ma_don_hang'     => $don->ma_don_hang,
                'ma_van_don'      => $don->ma_van_don,
                'ngay_tao'        => $don->ngay_tao,
                'tong_thanh_toan' => (float) $tongThanhToan,
                'ten_khach_hang'  => optional($don->khachHang)->hoTen ?? 'Khách lẻ',
                'so_dien_thoai'   => $don->so_dien_thoai ?? optional($don->khachHang)->sdt,
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