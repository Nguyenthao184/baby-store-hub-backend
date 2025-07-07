<?php

namespace Database\Seeders;

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

        // Kiểm tra nếu chưa có dữ liệu mới insert
        if (DB::table('chitietdonhang')->count() === 0) {
            DB::table('chitietdonhang')->insert([
                [
                    'donhang_id' => 1, // Đảm bảo DonHang ID=1 đã tồn tại
                    'sanpham_id' => '06b49659-c830-492f-a818-dc181a92328c', // Thay bằng id thật từ bảng sanpham
                    'soLuong' => 2,
                    'giaBan' => 50000,
                    'giamGia' => 0,
                    'tongTien' => 100000,
                ],
                [
                    'donhang_id' => 1,
                    'sanpham_id' => '3aa7f77c-0057-43f8-9f3f-d5d29d65ca38',
                    'soLuong' => 1,
                    'giaBan' => 80000,
                    'giamGia' => 0,
                    'tongTien' => 80000,
                ],
            ]);
        }
    }
}
