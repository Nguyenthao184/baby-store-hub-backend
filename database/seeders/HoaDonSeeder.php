<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

class HoaDonSeeder extends Seeder
{
    public function run(): void
    {
        $donHangs = DB::table('DonHang')->take(6)->get(); // Lấy 6 đơn hàng đầu tiên

        $hoaDons = [];
        $now = Carbon::now();

        $phuongThucs = ['TienMat', 'ChuyenKhoan', 'The'];

        foreach ($donHangs as $i => $donHang) {
            $tongTienHang = rand(70000, 250000);
            $giamGia = [0, 4000, 10000, 15000][rand(0, 3)];
            $vat = 0.08 * ($tongTienHang - $giamGia);
            $tongThanhToan = $tongTienHang - $giamGia + $vat;

            $hoaDons[] = [
                'id' => (string) Str::uuid(),
                'maHoaDon' => 'HD' . str_pad(44 + $i, 6, '0', STR_PAD_LEFT), // HD000044 - HD000049
                'donHang_id' => $donHang->id,
                'ngayXuat' => $now->copy()->subDays($i),
                'tongTienHang' => $tongTienHang,
                'giamGiaSanPham' => $giamGia,
                'thueVAT' => round($vat, 2),
                'tongThanhToan' => round($tongThanhToan, 2),
                'phuongThucThanhToan' => $phuongThucs[$i % 3],
            ];
        }

        DB::table('HoaDon')->insert($hoaDons);
    }
}
