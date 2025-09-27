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

    public function moveToShipping(Request $request, string $id)
    {
        $request->validate([
            'ghi_chu' => ['nullable','string','max:500'],
        ]);

        return DB::transaction(function () use ($id, $request) {
            // Khoá bản ghi để tránh race
            $don = DonHang::lockForUpdate()->find($id);
            if (!$don) {
                return response()->json(['message' => 'Không tìm thấy đơn hàng'], 404);
            }

            if ($don->trang_thai !== 'CHO_XU_LY') {
                return response()->json([
                    'message' => 'Chỉ chuyển trạng thái từ CHO_XU_LY sang DANG_GIAO_HANG',
                    'current' => $don->trang_thai,
                ], 422);
            }

            // Khuyến nghị: phải có mã vận đơn trước khi giao (bạn có thể bỏ check này nếu không cần)
            if (empty($don->ma_van_don)) {
                return response()->json([
                    'message' => 'Chưa có mã vận đơn. Vui lòng tạo vận đơn trước khi chuyển sang DANG_GIAO_HANG.',
                ], 422);
            }

            $don->update([
                'trang_thai'    => 'DANG_GIAO_HANG',
                'ngay_cap_nhat' => now(),
            ]);

            Log::info('Admin chuyển trạng thái đơn sang DANG_GIAO_HANG', [
                'don_hang_id' => $don->id,
                'by'          => auth()->id(),
                'ghi_chu'     => $request->input('ghi_chu'),
            ]);

            return response()->json([
                'message' => 'Đã chuyển trạng thái đơn sang DANG_GIAO_HANG',
                'don_hang' => [
                    'id'           => $don->id,
                    'ma_don_hang'  => $don->ma_don_hang,
                    'trang_thai'   => $don->trang_thai,
                    'ma_van_don'   => $don->ma_van_don,
                ],
            ]);
        });
    }
}