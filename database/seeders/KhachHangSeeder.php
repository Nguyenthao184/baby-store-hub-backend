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
        $emails = [
            'khach@example.com',
            'khach2@example.com',
            'khach3@example.com',
        ];
        $names = [
            'Nguyễn Văn An',
            'Trần Thị Bích',
            'Lê Hữu Phúc',
        ];


        $datas = [];

        foreach ($emails as $key => $email) {
            $taiKhoanId = DB::table('TaiKhoan')->where('email', $email)->value('id');

            if ($taiKhoanId) {
                $datas[] = [
                    'hoTen' => $names[$key],
                    'sdt' => '01234567' . sprintf('%02d', $key + 10),
                    'email' => $email,
                    'diaChi' => 'Số ' . ($key + 1) . ' Đường ABC',
                    'ngaySinh' => '1990-0' . ($key + 1) . '-01',
                    'taiKhoan_id' => $taiKhoanId,
                ];
            }
        }

        DB::table('KhachHang')->insert($datas);
    }
}
