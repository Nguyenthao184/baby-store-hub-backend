<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DanhMucSeeder extends Seeder
{
    public function run(): void
    {
        // Lấy danh sách kho (nếu cần gán)
        $khoIds = DB::table('Kho')->pluck('id')->toArray();

        DB::table('DanhMuc')->insert([
            [
                'id' => (string) Str::uuid(),
                'tenDanhMuc' => 'Thế giới sữa',
                'moTa' => 'Sữa các loại dành cho bé',
                'soLuongSanPham' => 0,
                'hinhAnh' => 'danh_muc/the-gioi-sua.png',
                'nhaCungCap' => null,
                'idKho' => $khoIds[0] ?? null,
            ],
            [
                'id' => (string) Str::uuid(),
                'tenDanhMuc' => 'Bỉm, tã',
                'moTa' => 'Tã, bỉm chất lượng cao',
                'soLuongSanPham' => 0,
                'hinhAnh' => 'danh_muc/bim-ta.png',
                'nhaCungCap' => null,
                'idKho' => $khoIds[1] ?? null,
            ],
            [
                'id' => (string) Str::uuid(),
                'tenDanhMuc' => 'Thực phẩm - Đồ uống',
                'moTa' => 'Thực phẩm dinh dưỡng, đồ uống',
                'soLuongSanPham' => 0,
                'hinhAnh' => 'danh_muc/thuc-pham.png',
                'nhaCungCap' => null,
                'idKho' => $khoIds[2] ?? null,
            ],
            [
                'id' => (string) Str::uuid(),
                'tenDanhMuc' => 'Sức khoẻ & Vitamin',
                'moTa' => 'Sản phẩm tăng sức đề kháng',
                'soLuongSanPham' => 0,
                'hinhAnh' => 'danh_muc/suc-khoe.png',
                'nhaCungCap' => null,
                'idKho' => $khoIds[0] ?? null,
            ],
            [
                'id' => (string) Str::uuid(),
                'tenDanhMuc' => 'Chăm sóc mẹ và bé - Mỹ phẩm',
                'moTa' => 'Sản phẩm làm đẹp và chăm sóc',
                'soLuongSanPham' => 0,
                'hinhAnh' => 'danh_muc/cham-soc.png',
                'nhaCungCap' => null,
                'idKho' => $khoIds[1] ?? null,
            ],
            [
                'id' => (string) Str::uuid(),
                'tenDanhMuc' => 'Đồ dùng - Gia dụng',
                'moTa' => 'Đồ dùng hàng ngày cho bé',
                'soLuongSanPham' => 0,
                'hinhAnh' => 'danh_muc/do-dung.png',
                'nhaCungCap' => null,
                'idKho' => $khoIds[2] ?? null,
            ],
            [
                'id' => (string) Str::uuid(),
                'tenDanhMuc' => 'Thời trang và phụ kiện',
                'moTa' => 'Quần áo, phụ kiện thời trang',
                'soLuongSanPham' => 0,
                'hinhAnh' => 'danh_muc/thoi-trang.png',
                'nhaCungCap' => null,
                'idKho' => $khoIds[0] ?? null,
            ],
            [
                'id' => (string) Str::uuid(),
                'tenDanhMuc' => 'Đồ chơi, học tập',
                'moTa' => 'Đồ chơi và dụng cụ học tập',
                'soLuongSanPham' => 0,
                'hinhAnh' => 'danh_muc/do-choi.png',
                'nhaCungCap' => null,
                'idKho' => $khoIds[1] ?? null,
            ],
            
        ]);
    }
}
