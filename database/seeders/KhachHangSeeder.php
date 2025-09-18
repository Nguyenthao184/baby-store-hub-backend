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
                'diaChi' => json_encode([
                    ['dia_chi' => 'Số 1 Đường ABC, Đà Nẵng', 'mac_dinh' => true],
                    ['dia_chi' => 'Số 2 Đường XYZ, Đà Nẵng', 'mac_dinh' => false],
                ]),
                'ngaySinh' => '1990-01-01',
                'avatar' => 'avatars/kh-1.jpg',
                'taiKhoan_id' => DB::table('TaiKhoan')->where('email', 'khach@example.com')->value('id'),
            ],
            [
                'hoTen' => 'Trần Thị Bích',
                'sdt' => '0123456790',
                'email' => 'khach2@example.com',
                'diaChi' => json_encode([
                    ['dia_chi' => 'Số 3 Đường DEF, Hà Nội', 'mac_dinh' => true],
                ]),
                'ngaySinh' => '1991-02-01',
                'avatar' => 'avatars/kh-2.jpg',
                'taiKhoan_id' => DB::table('TaiKhoan')->where('email', 'khach2@example.com')->value('id'),
            ],
        ];

        DB::table('KhachHang')->truncate();
        DB::table('KhachHang')->insert($khachHangs);
    }
}
