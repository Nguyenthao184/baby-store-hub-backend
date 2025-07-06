<?php

namespace Database\Seeders;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class KhachHangSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
                // Lấy id TaiKhoan của khách
        $taiKhoanId = DB::table('TaiKhoan')->where('email', 'khach@example.com')->value('id');

        DB::table('KhachHang')->insert([
            'hoTen' => 'Khách hàng mẫu',
            'sdt' => '0123456789',
            'email' => 'khach@example.com',
            'diaChi' => 'Số 1 Đường ABC',
            'ngaySinh' => '1990-01-01',
            'taiKhoan_id' => $taiKhoanId,
        ]);

    }
}
