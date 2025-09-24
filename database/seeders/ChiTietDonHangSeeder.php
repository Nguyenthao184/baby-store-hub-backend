<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\SanPham;
use App\Models\DonHang;
use Illuminate\Support\Str;

class ChiTietDonHangSeeder extends Seeder
{
    public function run(): void
    {
        $donHangs = DonHang::all();
        $sanPhams = SanPham::all();

        if ($donHangs->isEmpty() || $sanPhams->isEmpty()) {
            return;
        }

        // Xóa dữ liệu cũ trong bảng chitietdonhang
        DB::table('chitietdonhang')->delete();

        $records = [];

        foreach ($donHangs as $donHang) {
            // Lấy ngẫu nhiên 1..3 sản phẩm
            $countPick = min(max(1, rand(1, 3)), $sanPhams->count());
            $sanPhamsRandom = $sanPhams->random($countPick);
            if ($countPick === 1 && !$sanPhamsRandom instanceof \Illuminate\Support\Collection) {
                $sanPhamsRandom = collect([$sanPhamsRandom]);
            }

            foreach ($sanPhamsRandom as $sp) {
                $soLuong = rand(1, 5);
                $giaGoc  = (float) $sp->giaBan;
                $vat     = 8.00; // <<== VAT cố định 8%

                // Giá sau VAT
                $giaSauVat = round($giaGoc * (1 + $vat / 100), 2);

                // Giảm giá: tối đa 15% giá trị dòng
                $giaTriDong = $giaGoc * $soLuong;
                $giamGiaMax = round($giaTriDong * 0.15, 2);
                $giamGia    = round(rand(0, (int) ($giamGiaMax * 100)) / 100, 2);

                // Thành tiền sau VAT
                $thanhTienSauVat = max(0, round(($giaSauVat * $soLuong) - $giamGia, 2));

                $records[] = [
                    'id'           => (string) Str::uuid(),
                    'don_hang_id'  => $donHang->id,
                    'san_pham_id'  => $sp->id,
                    'ten_san_pham' => $sp->tenSanPham,
                    'gia'          => round($giaGoc, 2),   // đơn giá gốc
                    'vat'          => $vat,                // VAT cố định
                    'giam_gia'     => $giamGia,
                    'so_luong'     => $soLuong,
                    'thanh_tien'   => $thanhTienSauVat,    // thành tiền đã VAT
                ];
            }
        }

        DB::table('chitietdonhang')->insert($records);
    }
}
