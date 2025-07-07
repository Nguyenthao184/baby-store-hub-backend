<?php

namespace Database\Seeders;

use App\Models\SanPham;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ChiTietDonHangSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = now();
        $sanPham = SanPham::first();

        // Kiểm tra nếu chưa có dữ liệu mới insert
        if (DB::table('chitietdonhang')->count() === 0) {
            DB::table('chitietdonhang')->insert([
                [
                    'donhang_id' => 1, // Đảm bảo DonHang ID=1 đã tồn tại
                    'sanpham_id' => $sanPham->id, // Thay bằng id thật từ bảng sanpham
                    'soLuong' => 2,
                    'giaBan' => 50000,
                    'giamGia' => 0,
                    'tongTien' => 100000,
                ],
                [
                    'donhang_id' => 1,
                    'sanpham_id' => $sanPham->id,
                    'soLuong' => 1,
                    'giaBan' => 80000,
                    'giamGia' => 0,
                    'tongTien' => 80000,
                ],
            ]);
        }
    }
}
