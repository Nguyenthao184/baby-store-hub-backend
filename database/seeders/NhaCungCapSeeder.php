<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class NhaCungCapSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = now();

        $data = [
            [
                'tenNhaCungCap' => 'Công Ty Sữa ABC',
                'maSoThue' => '0101234567',
                'sdt' => '0241234567',
                'email' => 'contact@abc.com',
                'diaChi' => 'Số 123 Đường Láng, Hà Nội',
                'trangThai' => true,
                'ngayTao' => $now,
                'ngayCapNhat' => null,
            ],
            [
                'tenNhaCungCap' => 'Công Ty Bỉm XYZ',
                'maSoThue' => '0209876543',
                'sdt' => '0287654321',
                'email' => 'info@xyz.com',
                'diaChi' => '456 Nguyễn Văn Cừ, TP.HCM',
                'trangThai' => true,
                'ngayTao' => $now,
                'ngayCapNhat' => null,
            ],
            [
                'tenNhaCungCap' => 'Công Ty Đồ Dùng Trẻ Em DEF',
                'maSoThue' => '0305556667',
                'sdt' => '0233777555',
                'email' => 'sales@def.com',
                'diaChi' => '789 Nguyễn Tất Thành, Đà Nẵng',
                'trangThai' => true,
                'ngayTao' => $now,
                'ngayCapNhat' => null,
            ],
        ];

        DB::table('NhaCungCap')->insert($data);
    }
}
