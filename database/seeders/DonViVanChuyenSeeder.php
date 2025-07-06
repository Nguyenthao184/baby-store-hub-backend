<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DonViVanChuyenSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('donvivanchuyen')->insert([
            [
                'tenDonVi' => 'Giao Hàng Nhanh',
                'sdt' => '0901234567',
                'diaChi' => '789 Điện Biên Phủ, TP.HCM',
            ],
            [
                'tenDonVi' => 'Viettel Post',
                'sdt' => '0912345678',
                'diaChi' => '12 Trần Phú, Hải Phòng',
            ],
        ]);
    }
}
