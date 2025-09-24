<?php

namespace Database\Seeders;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Seeder;

class KhachHangSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $khachHangs = [
            [
                'hoTen' => 'Nguyễn Văn An',
                'sdt' => '0123456789',
                'email' => 'khach@example.com',
                'diaChi' => 'Số 1 Đường ABC, Đà Nẵng',
                'ngaySinh' => '1990-01-01',
                'avatar' => 'avatars/kh-1.jpg',
                'taiKhoan_id' => DB::table('TaiKhoan')->where('email', 'khach@example.com')->value('id'),
            ],
            [
                'hoTen' => 'Trần Thị Bích',
                'sdt' => '0123456790',
                'email' => 'khach2@example.com',
                'diaChi' => 'Số 3 Đường DEF, Hà Nội',
                'ngaySinh' => '1991-02-01',
                'avatar' => 'avatars/kh-2.jpg',
                'taiKhoan_id' => DB::table('TaiKhoan')->where('email', 'khach2@example.com')->value('id'),
            ],
        ];

        DB::table('KhachHang')->truncate();
        DB::table('KhachHang')->insert($khachHangs);
    }
}
