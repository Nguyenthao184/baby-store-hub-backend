<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CuaHangSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('cuahang')->insert([
            [
                'tenCuaHang' => 'Cửa hàng A',
                'diaChi' => '123 Nguyễn Trãi, Hà Nội',
                'sdt' => '0123456789',
                'email' => 'cuahanga@example.com',
                'ngayTao' => '2024-01-01',
            ],
            [
                'tenCuaHang' => 'Cửa hàng B',
                'diaChi' => '456 Lê Lợi, Đà Nẵng',
                'sdt' => '0987654321',
                'email' => 'cuahangb@example.com',
                'ngayTao' => '2024-06-01',
            ],
        ]);
    }
}
