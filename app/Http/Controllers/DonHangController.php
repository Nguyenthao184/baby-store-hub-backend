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

            // Map FE -> giá trị lưu DB
            $map = [
                'cod'  => 'cod',
                'bank' => 'bank_transfer',
                'card' => 'credit_card',
            ];
            $phuongThucThanhToan = $map[$data['phuongThuc']];

            $tamTinh   = 0.0;
            $tongVAT   = 0.0;

            $giamVoucher = (float)($data['giamVoucher'] ?? 0);
            $giamDiem    = (float)($data['giamDiem'] ?? 0);

            // ✅ chỉ tính khi COD
            $phiCOD      = $data['phuongThuc'] === 'cod' ? (float)($data['phiCOD'] ?? 0) : 0.0;

            $chiTietSnapshots = [];

            foreach ($data['sanPhams'] as $item) {
                $gia     = (float)($item['giaBan'] ?? 0);
                $vatPct  = (float)($item['vat'] ?? 0);
                $giamRaw = (float)($item['giamGia'] ?? 0);
                $sl      = (int)  ($item['soLuong'] ?? 0);
                $noiBat  = (bool) ($item['noiBat'] ?? false);

                if ($giamRaw >= 0 && $giamRaw <= 1) {
                    $discountFactor = $noiBat ? 1.0 : max(0.0, 1.0 - $giamRaw);
                    $giaCuoi1sp = round($gia * (1 + $vatPct/100.0) * $discountFactor, 2);
                    $truocVAT1sp = round($gia * $discountFactor, 2);
                    $vat1sp      = round($giaCuoi1sp - $truocVAT1sp, 2);
                } else {
                    $giaSauGiam  = $noiBat ? $gia : max(0.0, $gia - $giamRaw);
                    $truocVAT1sp = round($giaSauGiam, 2);
                    $vat1sp      = round($truocVAT1sp * $vatPct/100.0, 2);
                    $giaCuoi1sp  = round($truocVAT1sp + $vat1sp, 2);
                }

                $thanhTien = round($giaCuoi1sp * $sl, 2);
                $tamTinh  += $thanhTien;
                $tongVAT  += $vat1sp * $sl;

                $chiTietSnapshots[] = [
                    'san_pham_id'  => $item['id'],
                    'ten_san_pham' => $item['tenSanPham'],
                    'gia'          => $gia,
                    'vat'          => $vatPct,
                    'giam_gia'     => $giamRaw,
                    'so_luong'     => $sl,
                    'thanh_tien'   => $thanhTien,
                ];
            }

            $phiVanChuyen  = 0.0; // offline
            $tongThanhToan = round($tamTinh - $giamVoucher - $giamDiem + $phiCOD, 2);

            $donHang = DonHang::create([
                'id'                         => (string) Str::uuid(),
                'ma_don_hang'                => 'DH-' . now()->format('YmdHis'),
                'khach_hang_id'              => $data['khachHang_id'],
                'ten_nguoi_nhan'             => $data['tenNguoiNhan'],
                'so_dien_thoai'              => $data['soDienThoai'],
                'tam_tinh'                   => $tamTinh,
                'giam_voucher'               => $giamVoucher,
                'giam_diem'                  => $giamDiem,
                'phi_van_chuyen'             => $phiVanChuyen,
                'tong_thanh_toan'            => $tongThanhToan,
                'trang_thai'                 => 'DA_THANH_TOAN',   // ✅ offline thu tiền xong
                'phuong_thuc_thanh_toan'     => $phuongThucThanhToan,
                'ngay_tao'                   => now(),
                'ngay_cap_nhat'              => now(),
            ]);

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
                ]);

                $sp = SanPham::where('id', $c['san_pham_id'])->lockForUpdate()->first();
                if (!$sp || (int)$sp->soLuongTon < (int)$c['so_luong']) {
                    DB::rollBack();
                    return response()->json(['error' => 'Không đủ tồn kho cho sản phẩm'], 400);
                }
                $sp->soLuongTon = (int)$sp->soLuongTon - (int)$c['so_luong'];
                $sp->save();
            }

            // Tạo số HĐ an toàn trong ngày
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
                'giam_voucher'            => $giamVoucher,
                'giam_diem'               => $giamDiem,
                'phi_van_chuyen'          => 0,
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
                'giamVoucher'         => $giamVoucher,
                'giamDiem'            => $giamDiem,
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