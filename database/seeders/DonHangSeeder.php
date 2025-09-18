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
        // Lấy danh sách khách hàng từ database
        $khachHangs = KhachHang::all();

        if ($khachHangs->isEmpty()) {
            return;
        }

        // Xóa dữ liệu cũ trong bảng don_hang
        DB::table('donhang')->delete();

        $records = [];

        foreach ($khachHangs as $khachHang) {
            // Lấy địa chỉ mặc định từ cột JSON (nếu sử dụng JSON)
            $diaChi = $khachHang->diaChi;
            if (is_string($diaChi)) {
                $diaChi = json_decode($diaChi, true);
            }
            $diaChiMacDinh = $diaChi && is_array($diaChi) ? collect($diaChi)->firstWhere('mac_dinh', true) : null;

            // Tạo ngẫu nhiên từ 1 đến 3 đơn hàng cho mỗi khách hàng
            $soDonHang = rand(1, 3);

            for ($i = 1; $i <= $soDonHang; $i++) {
                $tamTinh = rand(100000, 500000); // Tổng tiền hàng trước ưu đãi
                $giamVoucher = rand(0, 50000); // Giảm giá voucher
                $giamDiem = rand(0, 20000); // Giảm giá từ điểm tích lũy
                $phiVanChuyen = rand(20000, 50000); // Phí vận chuyển
                $tongThanhToan = $tamTinh - $giamVoucher - $giamDiem + $phiVanChuyen;

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
                    'phi_van_chuyen' => $phiVanChuyen,
                    'tong_thanh_toan' => $tongThanhToan,
                    'voucher_id' => rand(0, 1) ? Str::uuid() : null,
                    'don_vi_van_chuyen' => rand(0, 1) ? 'Giao hàng nhanh' : 'Giao hàng tiết kiệm',
                    'ma_van_don' => rand(0, 1) ? 'GHN-' . rand(1000, 9999) : null,
                    'trang_thai' => 'awaiting_payment',
                    'phuong_thuc_thanh_toan' => rand(0, 1) ? 'cod' : 'momo',
                    'ngay_tao' => now(),
                    'ngay_cap_nhat' => now(),
                ];
            }
        }

        // Chèn dữ liệu vào bảng don_hang
        DB::table('donhang')->insert($records);
    }
}