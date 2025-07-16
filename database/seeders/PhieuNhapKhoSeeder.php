<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PhieuNhapKhoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        for ($i = 1; $i <= 3; $i++) {
            DB::table('phieu_nhap_kho')->insert([
                'so_phieu' => 'PNK' . str_pad($i, 4, '0', STR_PAD_LEFT),
                'ngay_nhap' => Carbon::now()->subDays(5 - $i),
                'nha_cung_cap_id' => rand(1, 3),
                'tong_tien_nhap' => 0, // sẽ cập nhật lại sau
                'ghi_chu' => 'Phiếu nhập số ' . $i,
                'trang_thai' => collect(['phieu_tam', 'da_nhap', 'da_huy'])->random(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
