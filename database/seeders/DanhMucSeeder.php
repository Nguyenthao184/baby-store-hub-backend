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
        //$khoIds = DB::table('Kho')->pluck('id')->toArray();

        $categories = [
            [
                'ten' => 'Thế giới sữa',
                'moTa' => 'Sữa các loại dành cho bé',
                'hinhAnh' => 'danh_muc/the-gioi-sua.png',
                //'kho' => $khoIds[0] ?? null,
            ],
            [
                'ten' => 'Bỉm, tã',
                'moTa' => 'Tã, bỉm chất lượng cao',
                'hinhAnh' => 'danh_muc/bim-ta.png',
                //'kho' => $khoIds[1] ?? null,
            ],
            [
                'ten' => 'Thực phẩm - Đồ uống',
                'moTa' => 'Thực phẩm dinh dưỡng, đồ uống',
                'hinhAnh' => 'danh_muc/thuc-pham.png',
                //'kho' => $khoIds[2] ?? null,
            ],
            [
                'ten' => 'Sức khoẻ & Vitamin',
                'moTa' => 'Sản phẩm tăng sức đề kháng',
                'hinhAnh' => 'danh_muc/suc-khoe.png',
                //'kho' => $khoIds[0] ?? null,
            ],
            [
                'ten' => 'Chăm sóc mẹ và bé - Mỹ phẩm',
                'moTa' => 'Sản phẩm làm đẹp và chăm sóc',
                'hinhAnh' => 'danh_muc/cham-soc.png',
                //'kho' => $khoIds[1] ?? null,
            ],
            [
                'ten' => 'Đồ dùng - Gia dụng',
                'moTa' => 'Đồ dùng hàng ngày cho bé',
                'hinhAnh' => 'danh_muc/do-dung.png',
                //'kho' => $khoIds[2] ?? null,
            ],
            [
                'ten' => 'Thời trang và phụ kiện',
                'moTa' => 'Quần áo, phụ kiện thời trang',
                'hinhAnh' => 'danh_muc/thoi-trang.png',
                //'kho' => $khoIds[0] ?? null,
            ],
            [
                'ten' => 'Đồ chơi, học tập',
                'moTa' => 'Đồ chơi và dụng cụ học tập',
                'hinhAnh' => 'danh_muc/do-choi.png',
                //'kho' => $khoIds[1] ?? null,
            ],
        ];

        $data = [];
        $counter = 1;

        foreach ($categories as $item) {
            $data[] = [
                'id' => (string) Str::uuid(),
                'maDanhMuc' => 'DM' . str_pad($counter, 4, '0', STR_PAD_LEFT),
                'tenDanhMuc' => $item['ten'],
                'moTa' => $item['moTa'],
                'soLuongSanPham' => 0,
                'hinhAnh' => $item['hinhAnh'],
                'nhaCungCap_id' => null,
                //'idKho' => $item['kho'],
            ];
            $counter++;
        }

        DB::table('DanhMuc')->insert($data);
    }
}
