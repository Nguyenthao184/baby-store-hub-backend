<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Models\DonHang;
use App\Models\HoaDon;
use App\Models\ChiTietDonHang;
use App\Models\SanPham;
use App\Http\Requests\DonHang\ThanhToanDonHangRequest;

class DonHangController extends Controller
{
    public function thanhToan(ThanhToanDonHangRequest $request)
    {
        DB::beginTransaction();
        try {
            $data = $request->validated();

            // Map phương thức thanh toán từ FE -> enum DB
            $phuongThucMap = [
                'cash' => 'cod',
                'bank' => 'bank_transfer',
                'card' => 'credit_card',
            ];
            $phuongThucThanhToan = $phuongThucMap[$request->phuongThuc] ?? 'cod';

            // ===== TÍNH THEO CÔNG THỨC MỚI (không phí vận chuyển) =====
            $tamTinh   = 0.0;    // tổng tiền hàng (đã gồm VAT và giảm theo từng item)
            $tongVAT   = 0.0;    // tổng VAT cộng dồn để lưu báo cáo

            // Nếu có voucher/điểm thì nhận từ request, offline thường không có
            $giamVoucher = (float)($request->giamVoucher ?? 0);
            $giamDiem    = (float)($request->giamDiem ?? 0);
            $phiCOD      = (float)($request->phiCOD ?? 0); // nếu không dùng COD, để = 0

            $chiTietSnapshots = []; // gom lại để ghi bảng chi tiết

            foreach ($request->sanPhams as $item) {
                $gia     = (float)($item['giaBan'] ?? 0);     // giá niêm yết / 1sp (chưa VAT)
                $vatPct  = (float)($item['vat'] ?? 0);        // % VAT
                $giamRaw = (float)($item['giamGia'] ?? 0);    // có thể là tỷ lệ (0..1) hoặc VND/sp
                $sl      = (int)  ($item['soLuong'] ?? 0);
                $noiBat  = (bool) ($item['noiBat'] ?? false); // nếu có cờ "nổi bật" => không giảm

                // Tính giá cuối 1sp theo công thức:
                // - Nếu giamRaw trong [0,1] => giảm THEO TỶ LỆ sau VAT: gia * (1 + VAT) * (noiBat?1:(1 - giamRaw))
                // - Nếu giamRaw > 1 => giảm THEO SỐ TIỀN /sp (fallback)
                if ($giamRaw >= 0 && $giamRaw <= 1) {
                    $discountFactor = $noiBat ? 1.0 : max(0.0, 1.0 - $giamRaw);
                    $giaCuoi1sp = round($gia * (1 + $vatPct/100.0) * $discountFactor, 2);

                    // tách phần trước VAT & VAT (để cộng dồn)
                    $truocVAT1sp = round($gia * $discountFactor, 2);
                    $vat1sp      = round($giaCuoi1sp - $truocVAT1sp, 2);
                } else {
                    // giảm theo số tiền
                    $giaSauGiam  = $noiBat ? $gia : max(0.0, $gia - $giamRaw);
                    $truocVAT1sp = round($giaSauGiam, 2);
                    $vat1sp      = round($truocVAT1sp * $vatPct/100.0, 2);
                    $giaCuoi1sp  = round($truocVAT1sp + $vat1sp, 2);
                }

                $thanhTien = round($giaCuoi1sp * $sl, 2);

                $tamTinh += $thanhTien;
                $tongVAT += $vat1sp * $sl;

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

            // Không tính phí vận chuyển trong offline
            $phiVanChuyen  = 0.0;
            $tongThanhToan = round($tamTinh - $giamVoucher - $giamDiem + $phiCOD, 2);

            // ===== TẠO ĐƠN HÀNG =====
            $donHang = DonHang::create([
                'id'                         => (string) Str::uuid(),
                'ma_don_hang'                => 'DH-' . now()->format('YmdHis'),
                'khach_hang_id'              => $request->khachHang_id,
                'ten_nguoi_nhan'             => $request->tenNguoiNhan,
                'so_dien_thoai'              => $request->soDienThoai,
                'tam_tinh'                   => $tamTinh,
                'giam_voucher'               => $giamVoucher,
                'giam_diem'                  => $giamDiem,         // nếu không có cột này thì bỏ
                'phi_van_chuyen'             => $phiVanChuyen,     // = 0 cho offline
                'tong_thanh_toan'            => $tongThanhToan,
                'trang_thai'                 => 'completed',       // offline: thanh toán xong
                'phuong_thuc_thanh_toan'     => $phuongThucThanhToan,
                'ngay_tao'                   => now(),
                'ngay_cap_nhat'              => now(),
            ]);

            // ===== GHI CHI TIẾT + TRỪ KHO =====
            foreach ($chiTietSnapshots as $c) {
                ChiTietDonHang::create([
                    'id'            => (string) Str::uuid(),
                    'don_hang_id'   => $donHang->id,
                    'san_pham_id'   => $c['san_pham_id'],
                    'ten_san_pham'  => $c['ten_san_pham'],
                    'gia'           => $c['gia'],       // giá trước VAT
                    'vat'           => $c['vat'],
                    'giam_gia'      => $c['giam_gia'],  // tỷ lệ hoặc VND/sp như trên
                    'so_luong'      => $c['so_luong'],
                    'thanh_tien'    => $c['thanh_tien'] // đã gồm VAT & giảm cho item
                ]);

                // Trừ kho (lock để tránh race)
                $sp = SanPham::where('id', $c['san_pham_id'])->lockForUpdate()->first();
                if (!$sp) {
                    DB::rollBack();
                    return response()->json(['error' => 'Không tìm thấy sản phẩm.'], 404);
                }
                if ((int)$sp->soLuongTon < (int)$c['so_luong']) {
                    DB::rollBack();
                    return response()->json([
                        'error' => 'Không đủ hàng tồn kho cho sản phẩm ' . ($sp->tenSanPham ?? $sp->id)
                    ], 400);
                }
                $sp->soLuongTon = (int)$sp->soLuongTon - (int)$c['so_luong'];
                $sp->save();
            }

            // ===== TẠO HÓA ĐƠN =====
            // an toàn hơn: tăng số trong ngày (tránh đụng nhau)
            $today = now()->toDateString();
            $stt = HoaDon::whereDate('ngay_xuat', $today)->lockForUpdate()->count() + 1;
            $maHoaDon = 'HD-' . now()->format('Ymd') . '-' . str_pad((string)$stt, 6, '0', STR_PAD_LEFT);

            $hoaDon = HoaDon::create([
                'id'                      => (string) Str::uuid(),
                'ma_hoa_don'              => $maHoaDon,
                'don_hang_id'             => $donHang->id,
                'ngay_xuat'               => now(),
                'tong_tien_hang'          => $tamTinh,          // đúng định nghĩa: sau VAT & giảm
                'tong_vat'                => $tongVAT,          // để báo cáo (nếu không cần có thể để 0)
                'giam_voucher'            => $giamVoucher,
                'giam_diem'               => $giamDiem,         // nếu không có cột thì bỏ
                'phi_van_chuyen'          => 0,                 // offline: 0
                'tong_thanh_toan'         => $tongThanhToan,
                'phuong_thuc_thanh_toan'  => $phuongThucThanhToan,
            ]);

            DB::commit();

            return response()->json([
                'message'                 => 'Thanh toán thành công',
                'hoaDonId'                => $hoaDon->id,
                'phuongThucThanhToan'     => $phuongThucThanhToan,
                'tongTienHang'            => $tamTinh,
                'tongVAT'                 => $tongVAT,
                'giamVoucher'             => $giamVoucher,
                'giamDiem'                => $giamDiem,
                'tongThanhToan'           => $tongThanhToan,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}