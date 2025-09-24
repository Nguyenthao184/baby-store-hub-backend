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
                'sdt' => '0905456789',
                'email' => 'khach@example.com',
                'diaChi' => '23 Lê Văn Đức, Phường Hòa Cường Nam, Quận Hải Châu, Thành phố Đà Nẵng',
                'ngaySinh' => '1990-01-01',
                'avatar' => 'avatars/kh-1.jpg',
                'taiKhoan_id' => DB::table('TaiKhoan')->where('email', 'khach@example.com')->value('id'),
            ],
            [
                'hoTen' => 'Trần Thị Bích',
                'sdt' => '0905456780',
                'email' => 'khach2@example.com',
                'diaChi' => '59 Nguyễn Văn Linh, Phường Hòa Cường Bắc, Quận Hải Châu, Thành phố Đà Nẵng',
                'ngaySinh' => '1991-02-01',
                'avatar' => 'avatars/kh-2.jpg',
                'taiKhoan_id' => DB::table('TaiKhoan')->where('email', 'khach2@example.com')->value('id'),
            ],
            [
                'hoTen' => 'Võ Thị Cẩm',
                'sdt' => '0905456781',
                'email' => 'khach3@example.com',
                'diaChi' => '123 Trần Phú, Phường Hải Châu I, Quận Hải Châu, Thành phố Đà Nẵng',
                'ngaySinh' => '1991-03-01',
                'avatar' => 'avatars/kh-3.jpg',
                'taiKhoan_id' => DB::table('TaiKhoan')->where('email', 'khach3@example.com')->value('id'),
            ],
        ];

        DB::table('KhachHang')->truncate();
        DB::table('KhachHang')->insert($khachHangs);
    }
}
