<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;
use App\Models\DonHang;
use App\Models\ChiTietDonHang;

class HoaDonSeeder extends Seeder
{
    public function run(): void
    {
        // Lấy danh sách đơn hàng mới nhất
        $donHangs = DonHang::orderByDesc('ngay_tao')->take(10)->get();
        $phuongThucs = ['cod', 'momo', 'vnpay']; // Các phương thức thanh toán
        $now = Carbon::now();
        $hoaDons = [];
        $startIndex = 1;

        // Xóa dữ liệu cũ trong bảng hoadon
        DB::table('hoadon')->delete();

        foreach ($donHangs as $i => $donHang) {
            // Lấy chi tiết đơn hàng liên quan
            $chiTiets = ChiTietDonHang::where('don_hang_id', $donHang->id)->get();

            if ($chiTiets->isEmpty()) continue;

            // Tính toán tổng tiền hàng và VAT
            $tongTienHang = $chiTiets->sum(function ($chiTiet) {
                return $chiTiet->thanh_tien;
            });
            $tongVAT = $chiTiets->sum(function ($chiTiet) {
                return $chiTiet->gia * $chiTiet->so_luong * (floatval($chiTiet->vat ?? 0) / 100);
            });

            // Lấy các giá trị từ đơn hàng
            $giamVoucher = $donHang->giam_voucher;
            $giamDiem = $donHang->giam_diem;
            $phiVanChuyen = $donHang->phi_van_chuyen;

            // Tính tổng thanh toán
            $tongThanhToan = $tongTienHang + $tongVAT - $giamVoucher - $giamDiem + $phiVanChuyen;

            // Tạo mã hóa đơn
            $maHoaDon = 'HD-' . now()->year . '-' . str_pad($startIndex++, 6, '0', STR_PAD_LEFT);

            $hoaDons[] = [
                'id' => (string) Str::uuid(),
                'ma_hoa_don' => $maHoaDon,
                'don_hang_id' => $donHang->id,
                'ngay_xuat' => $now->copy()->subDays(rand(0, 10)),
                'tong_tien_hang' => round($tongTienHang, 2),
                'tong_vat' => round($tongVAT, 2),
                'giam_voucher' => round($giamVoucher, 2),
                'giam_diem' => round($giamDiem, 2),
                'phi_van_chuyen' => round($phiVanChuyen, 2),
                'tong_thanh_toan' => round($tongThanhToan, 2),
                'phuong_thuc_thanh_toan' => $phuongThucs[$i % 3],
            ];
        }

        // Chèn dữ liệu vào bảng hoadon
        DB::table('hoadon')->insert($hoaDons);
    }
}