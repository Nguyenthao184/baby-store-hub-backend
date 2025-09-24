<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Models\KhachHang;
use Carbon\Carbon;


class DonHangSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $khachHangs = KhachHang::all();

        if ($khachHangs->isEmpty()) {
            return;
        }

        DB::table('donhang')->delete();

        $records = [];

        foreach ($khachHangs as $khachHang) {
            // Lấy địa chỉ mặc định (JSON)
            $diaChi = $khachHang->diaChi;
            if (is_string($diaChi)) {
                $diaChi = json_decode($diaChi, true);
            }
            $diaChiMacDinh = $diaChi && is_array($diaChi) ? collect($diaChi)->firstWhere('mac_dinh', true) : null;

            // Random 1–3 đơn
            $soDonHang = rand(1, 3);

            for ($i = 1; $i <= $soDonHang; $i++) {
                $tamTinh = rand(100000, 500000);
                $giamVoucher = rand(0, 50000);
                $giamDiem = rand(0, 20000);
                $phiVanChuyenOnline = rand(20000, 50000); // dùng cho online
                $phuongThuc = ['cod', 'vnpay', 'momo'][rand(0, 2)];
                $isOffline = rand(0, 1) === 1;

                if ($isOffline) {
                    // ===== OFFLINE =====
                    $phiVanChuyen = 0; // <<== KHÔNG để null
                    $tongThanhToan = $tamTinh - $giamVoucher - $giamDiem + $phiVanChuyen;

                    $records[] = [
                        'id' => Str::uuid(),
                        'ma_don_hang' => 'DH-' . now()->year . '-' . str_pad(rand(1, 999999), 6, '0', STR_PAD_LEFT),
                        'khach_hang_id' => $khachHang->id,
                        'ten_nguoi_nhan' => $khachHang->hoTen,
                        'so_dien_thoai' => $khachHang->sdt,
                        'dia_chi' => null,                         // giữ null nếu cột cho phép
                        'ghi_chu' => 'Thanh toán offline',
                        'tam_tinh' => $tamTinh,
                        'giam_voucher' => $giamVoucher,
                        'giam_diem' => $giamDiem,
                        'phi_van_chuyen' => $phiVanChuyen,        // 0 thay vì null
                        'tong_thanh_toan' => $tongThanhToan,
                        'voucher_id' => null,
                        'don_vi_van_chuyen' => null,
                        'ma_van_don' => null,
                        'trang_thai' => 'DA_THANH_TOAN',
                        'phuong_thuc_thanh_toan' => $phuongThuc,  // cod/vnpay/momo
                        'ngay_tao' => now(),
                        'ngay_cap_nhat' => now(),
                    ];
                } else {
                    // ===== ONLINE =====
                    $phiVanChuyen = $phiVanChuyenOnline;
                    $tongThanhToan = $tamTinh - $giamVoucher - $giamDiem + $phiVanChuyen;
                    $trangThai = match ($phuongThuc) {
                        'cod' => 'CHO_XU_LY',
                        'vnpay', 'momo' => 'CHO_LAY_HANG',
                        default => 'CHO_XU_LY'
                    };

                    $records[] = [
                        'id' => Str::uuid(),
                        'ma_don_hang' => 'DH-' . now()->year . '-' . str_pad(rand(1, 999999), 6, '0', STR_PAD_LEFT),
                        'khach_hang_id' => $khachHang->id,
                        'ten_nguoi_nhan' => $khachHang->hoTen,
                        'so_dien_thoai' => $diaChiMacDinh['so_dien_thoai'] ?? $khachHang->sdt,
                        'dia_chi' => $diaChiMacDinh['dia_chi'] ?? 'Địa chỉ không xác định',
                        'ghi_chu' => rand(0, 1) ? 'Giao hàng nhanh' : null,
                        'tam_tinh' => $tamTinh,
                        'giam_voucher' => $giamVoucher,
                        'giam_diem' => $giamDiem,
                        'phi_van_chuyen' => $phiVanChuyen,        // số >0
                        'tong_thanh_toan' => $tongThanhToan,
                        'voucher_id' => rand(0, 1) ? Str::uuid() : null,
                        'don_vi_van_chuyen' => rand(0, 1) ? 'GHN' : 'GHTK',
                        'ma_van_don' => rand(0, 1) ? 'GHN-' . rand(1000, 9999) : null,
                        'trang_thai' => $trangThai,
                        'phuong_thuc_thanh_toan' => $phuongThuc,
                        'ngay_tao' => now(),
                        'ngay_cap_nhat' => now(),
                    ];
                }
            }
        }

        DB::table('donhang')->insert($records);
    }
}
