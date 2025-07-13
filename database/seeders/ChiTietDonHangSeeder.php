<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\SanPham;
use App\Models\DonHang;

class ChiTietDonHangSeeder extends Seeder
{
    public function run(): void
    {
        $donHangs = DonHang::all();
        $sanPhams = SanPham::all();

        if ($donHangs->isEmpty() || $sanPhams->isEmpty()) {
            return;
        }

        DB::table('chitietdonhang')->truncate();

        $records = [];

        foreach ($donHangs as $donHang) {
            $sanPhamsRandom = $sanPhams->random(rand(1, 3));

            foreach ($sanPhamsRandom as $sp) {
                $soLuong = rand(1, 5);
                $giaGoc = $sp->giaBan;
                $vat = floatval($sp->VAT ?? 0);
                $giaBan = $giaGoc + ($giaGoc * $vat / 100);
                $tongTien = $giaBan * $soLuong;

                $records[] = [
                    'donhang_id' => $donHang->id,
                    'sanpham_id' => $sp->id,
                    'soLuong' => $soLuong,
                    'giaBan' => $giaBan,
                    'giamGia' => 0,
                    'tongTien' => $tongTien,
                ];
            }
        }

        DB::table('chitietdonhang')->insert($records);
    }
}