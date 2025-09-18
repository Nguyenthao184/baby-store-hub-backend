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
            // Lấy ngẫu nhiên từ 1 đến 3 sản phẩm cho mỗi đơn hàng
            $sanPhamsRandom = $sanPhams->random(rand(1, 3));

            foreach ($sanPhamsRandom as $sp) {
                $soLuong = rand(1, 5); // Số lượng ngẫu nhiên từ 1 đến 5
                $giaGoc = $sp->giaBan; // Giá gốc của sản phẩm
                $vat = floatval($sp->vat ?? 0); // VAT của sản phẩm
                $giamGia = rand(0, 5000); // Giảm giá ngẫu nhiên (nếu có)
                $giaBan = $giaGoc * (1 + $vat / 100); // Giá sau VAT
                $thanhTien = ($giaBan - $giamGia) * $soLuong; // Thành tiền

                $records[] = [
                    'id' => Str::uuid(),
                    'don_hang_id' => $donHang->id,
                    'san_pham_id' => $sp->id,
                    'ten_san_pham' => $sp->tenSanPham,
                    'gia' => $giaGoc,
                    'vat' => $vat,
                    'giam_gia' => $giamGia,
                    'so_luong' => $soLuong,
                    'thanh_tien' => $thanhTien,
                ];
            }
        }

        // Chèn dữ liệu vào bảng chitietdonhang
        DB::table('chitietdonhang')->insert($records);
    }
}