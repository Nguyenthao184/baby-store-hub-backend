<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class KhoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = now();

        $khoData = [
            [
                'id' => Str::uuid()->toString(),
                'tenKho' => 'Kho Hà Nội',
                'diaChi' => 'Số 123, Đường Láng, Quận Đống Đa, Hà Nội',
                'moTa' => 'Kho chính tại Hà Nội, phục vụ khu vực miền Bắc',
                'trangThai' => true,
                'soLuongSanPham' => 0,
                'nguoiQuanLy' => 'Nguyễn Văn An',
                'dienTich' => 500.00,
                'ngayTao' => $now,
                'ngayCapNhat' => null,
            ],
            [
                'id' => Str::uuid()->toString(),
                'tenKho' => 'Kho Sài Gòn',
                'diaChi' => 'Số 456, Đường Nguyễn Văn Cừ, Quận 5, TP. Hồ Chí Minh',
                'moTa' => 'Kho chính tại Sài Gòn, phục vụ khu vực miền Nam',
                'trangThai' => true,
                'soLuongSanPham' => 0,
                'nguoiQuanLy' => 'Trần Thị Bình',
                'dienTich' => 750.00,
                'ngayTao' => $now,
                'ngayCapNhat' => null,
            ],
            [
                'id' => Str::uuid()->toString(),
                'tenKho' => 'Kho Đà Nẵng',
                'diaChi' => 'Số 789, Đường Nguyễn Tất Thành, Quận Hải Châu, Đà Nẵng',
                'moTa' => 'Kho chính tại Đà Nẵng, phục vụ khu vực miền Trung',
                'trangThai' => true,
                'soLuongSanPham' => 0,
                'nguoiQuanLy' => 'Lê Văn Cường',
                'dienTich' => 400.00,
                'ngayTao' => $now,
                'ngayCapNhat' => null,
            ],
        ];

        DB::table('Kho')->insert($khoData);
    }
}
