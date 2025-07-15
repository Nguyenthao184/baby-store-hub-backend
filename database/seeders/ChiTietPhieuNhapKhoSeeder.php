<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ChiTietPhieuNhapKhoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $phieuNhapIds = DB::table('phieu_nhap_kho')->pluck('id')->toArray();
        $sanPhamIds = DB::table('SanPham')->pluck('id')->toArray();

        foreach ($phieuNhapIds as $phieuNhapId) {
            $tongTien = 0;
            $sanPhamRandom = collect($sanPhamIds)->random(rand(2, 3));

            foreach ($sanPhamRandom as $sanPhamId) {
                $soLuongNhap = rand(5, 20);
                $giaNhap = rand(100000, 500000);
                $thueNhap = rand(0, 10); // %

                DB::table('chi_tiet_phieu_nhap_kho')->insert([
                    'phieu_nhap_id' => $phieuNhapId,
                    'san_pham_id' => $sanPhamId,
                    'so_luong_nhap' => $soLuongNhap,
                    'gia_nhap' => $giaNhap,
                    'thue_nhap' => $thueNhap,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $tongTien += $giaNhap * $soLuongNhap;
            }

            DB::table('phieu_nhap_kho')->where('id', $phieuNhapId)->update([
                'tong_tien_nhap' => $tongTien
            ]);
        }
    }
}
