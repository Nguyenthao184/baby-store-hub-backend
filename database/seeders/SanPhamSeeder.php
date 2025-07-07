<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SanPhamSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        // Lấy toàn bộ danh mục
        $danhMucList = DB::table('DanhMuc')->get()->keyBy('id')->values();

        // Danh sách sản phẩm với ảnh riêng
        $products = [
            // Thế giới sữa
            ['Sữa Enfa A+ 1', 'sua-enfa-a-1.png'],
            ['Sữa Friso Gold 2', 'sua-friso-gold-2.png'],
            ['Sữa Nan Optipro 3', 'sua-nan-optipro-3.png'],
            ['Sữa Abbott Grow 4', 'sua-abbott-grow-4.png'],

            // Bỉm, tã
            ['Bỉm Pampers Newborn', 'bim-pampers-newborn.png'],
            ['Bỉm Huggies Dry', 'bim-huggies-dry.png'],
            ['Bỉm Bobby Extra Soft', 'bim-bobby-extra-soft.png'],
            ['Tã Merries Nhật Bản', 'ta-merries-nhat-ban.png'],

            // Thực phẩm - Đồ uống
            ['Bột ăn dặm Nestle', 'bot-an-dam-nestle.png'],
            ['Cháo tươi SG Food', 'chao-tuoi-sg-food.png'],
            ['Nước trái cây Pigeon', 'nuoc-trai-cay-pigeon.png'],
            ['Súp dinh dưỡng Heinz', 'sup-dinh-duong-heinz.png'],

            // Sức khỏe & Vitamin
            ['Vitamin C ChildLife', 'vitamin-c-childlife.png'],
            ['Siro tăng đề kháng PediaSure', 'siro-tang-de-khang-pediasure.png'],
            ['Men vi sinh BioGaia', 'men-vi-sinh-biogaia.png'],
            ['DHA Bio Island', 'dha-bio-island.png'],

            // Chăm sóc mẹ và bé - Mỹ phẩm
            ['Kem chống hăm Bepanthen', 'kem-chong-ham-bepanthen.png'],
            ['Sữa tắm gội Arau Baby', 'sua-tam-goi-arau-baby.png'],
            ['Dầu dưỡng Bio Oil', 'dau-duong-bio-oil.png'],
            ['Kem dưỡng ẩm Cetaphil', 'kem-duong-am-cetaphil.png'],

            // Đồ dùng - Gia dụng
            ['Bình sữa Comotomo', 'binh-sua-comotomo.png'],
            ['Máy tiệt trùng bình sữa', 'may-tiet-trung-binh-sua.png'],
            ['Ghế ăn dặm Mastela', 'ghe-an-dam-mastela.png'],
            ['Nhiệt kế điện tử Omron', 'nhiet-ke-dien-tu-omron.png'],

            // Thời trang và phụ kiện
            ['Bộ quần áo Carter', 'bo-quan-ao-carter.png'],
            ['Mũ len cho bé', 'mu-len-cho-be.png'],
            ['Vớ sơ sinh', 'vo-so-sinh.png'],
            ['Yếm ăn chống thấm', 'yem-an-chong-tham.png'],

            // Đồ chơi, học tập
            ['Đồ chơi xúc xắc', 'do-choi-xuc-xac.png'],
            ['Xe tập đi cho bé', 'xe-tap-di-cho-be.png'],
            ['Bảng chữ cái nam châm', 'bang-chu-cai-nam-cham.png'],
            ['Đồ chơi xếp hình Lego', 'do-choi-xep-hinh-lego.png'],
        ];

        $sanPhamData = [];
        $index = 0;

        foreach ($danhMucList as $danhMuc) {
            for ($i = 0; $i < 4; $i++) {
                $product = $products[$index];
                $sanPhamData[] = [
                    'id' => (string) Str::uuid(),
                    'tenSanPham' => $product[0],
                    'maSKU' => strtoupper(Str::random(8)),
                    'VAT' => 8.00,
                    'giaBan' => random_int(100000, 1000000),
                     'soLuongTon' => random_int(10, 100),
                    'moTa' => 'Sản phẩm: ' . $product[0],
                    'danhMuc_id' => $danhMuc->id,
                    'kho_id' => $danhMuc->idKho,
                    'hinhAnh' => 'san_pham/' . $product[1],
                    'ngayTao' => $now,
                    'ngayCapNhat' => null,
                ];
                $index++;
            }
        }

        // Insert tất cả
        DB::table('SanPham')->insert($sanPhamData);

        // Gọi hàm cập nhật số lượng
        $this->updateQuantities();
    }

    /**
     * Cập nhật số lượng sản phẩm trong DanhMuc và Kho
     */
    protected function updateQuantities(): void
{
    // Tính tổng số lượng tồn cho mỗi danh mục
    $danhMucCounts = DB::table('SanPham')
        ->select('danhMuc_id', DB::raw('SUM(soLuongTon) as total'))
        ->groupBy('danhMuc_id')
        ->pluck('total', 'danhMuc_id');

    foreach ($danhMucCounts as $danhMucId => $total) {
        DB::table('DanhMuc')->where('id', $danhMucId)->update([
            'soLuongSanPham' => $total
        ]);
    }

    // Tính tổng số lượng tồn cho mỗi kho
    $khoCounts = DB::table('SanPham')
        ->select('kho_id', DB::raw('SUM(soLuongTon) as total'))
        ->groupBy('kho_id')
        ->pluck('total', 'kho_id');

    foreach ($khoCounts as $khoId => $total) {
        DB::table('Kho')->where('id', $khoId)->update([
            'soLuongSanPham' => $total
        ]);
    }
}

}
