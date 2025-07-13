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
        $donHangs = DonHang::orderByDesc('ngayTao')->take(10)->get();
        $phuongThucs = ['TienMat', 'ChuyenKhoan', 'The'];
        $now = Carbon::now();
        $hoaDons = [];

        foreach ($donHangs as $i => $donHang) {
            $chiTiets = ChiTietDonHang::where('donhang_id', $donHang->id)->get();

            if ($chiTiets->isEmpty()) continue;

            $tongTienHang = $chiTiets->sum('tongTien');
            $giamGia = [0, 5000, 10000, 20000][rand(0, 3)];
            $vat = 0.08 * ($tongTienHang - $giamGia);
            $tongThanhToan = $tongTienHang - $giamGia + $vat;

            $hoaDons[] = [
                'id' => (string) Str::uuid(),
                'maHoaDon' => 'HD' . str_pad(rand(50, 9999), 6, '0', STR_PAD_LEFT),
                'donHang_id' => $donHang->id,
                'ngayXuat' => $now->copy()->subDays(rand(0, 10)),
                'tongTienHang' => $tongTienHang,
                'giamGiaSanPham' => $giamGia,
                'thueVAT' => round($vat, 2),
                'tongThanhToan' => round($tongThanhToan, 2),
                'phuongThucThanhToan' => $phuongThucs[$i % 3],
            ];
        }

        DB::table('HoaDon')->truncate(); // Xóa cũ nếu cần
        DB::table('HoaDon')->insert($hoaDons);
    }
}