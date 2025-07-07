<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DonHangSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = now();

        // Kiểm tra nếu chưa có dữ liệu mới insert
        if (DB::table('DonHang')->count() === 0) {
            DB::table('DonHang')->insert([
                [
                    'id' => 1,
                    'maDonHang' => 'DH' . $now->format('YmdHis'),
                    'khachHang_id' => 1,         // đảm bảo đã có khách hàng id=1
                    'cuaHang_id' => 1,           // đảm bảo đã có cửa hàng id=1
                    'trangThai' => 'completed',
                    'ngayTao' => $now,
                    'ngayCapNhat' => $now,
                ],
                // Thêm nhiều đơn hàng khác nếu muốn
            ]);
        }
    }
}
