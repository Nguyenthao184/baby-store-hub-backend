<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class VoucherSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('voucher')->insert([
            [
                'maVoucher' => 'SALE50',
                'giaTri' => 50000,
                'ngayBatDau' => now(),
                'ngayKetThuc' => now()->addDays(30),
                'soLanSuDung' => 100,
            ],
            [
                'maVoucher' => 'FREESHIP',
                'giaTri' => 20000,
                'ngayBatDau' => now(),
                'ngayKetThuc' => now()->addDays(15),
                'soLanSuDung' => 50,
            ],
        ]);
    }
}
