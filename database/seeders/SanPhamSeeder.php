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
        $danhMucList = DB::table('DanhMuc')->get()->keyBy('tenDanhMuc');

        // Mapping sản phẩm theo danh mục
        $productsByCategory = [
            'Thế giới sữa' => [
                ['Sữa Enfa A+ 1', 'sua-enfa-a-1.png'],
                ['Sữa Friso Gold 2', 'sua-friso-gold-2.png'],
                ['Sữa Nan Optipro 3', 'sua-nan-optipro-3.png'],
                ['Sữa Abbott Grow 4', 'sua-abbott-grow-4.png'],
            ],
            'Bỉm, tã' => [
                ['Bỉm Pampers Newborn', 'bim-pampers-newborn.png'],
                ['Bỉm Huggies Dry', 'bim-huggies-dry.png'],
                ['Bỉm Bobby Extra Soft', 'bim-bobby-extra-soft.png'],
                ['Tã Merries Nhật Bản', 'ta-merries-nhat-ban.png'],
            ],
            'Thực phẩm - Đồ uống' => [
                ['Bột ăn dặm Nestle', 'bot-an-dam-nestle.png'],
                ['Cháo tươi SG Food', 'chao-tuoi-sg-food.png'],
                ['Nước trái cây Pigeon', 'nuoc-trai-cay-pigeon.png'],
                ['Súp dinh dưỡng Heinz', 'sup-dinh-duong-heinz.png'],
            ],
            'Sức khoẻ & Vitamin' => [
                ['Vitamin C ChildLife', 'vitamin-c-childlife.png'],
                ['Siro tăng đề kháng PediaSure', 'siro-tang-de-khang-pediasure.png'],
                ['Men vi sinh BioGaia', 'men-vi-sinh-biogaia.png'],
                ['DHA Bio Island', 'dha-bio-island.png'],
            ],
            'Chăm sóc mẹ và bé - Mỹ phẩm' => [
                ['Kem chống hăm Bepanthen', 'kem-chong-ham-bepanthen.png'],
                ['Sữa tắm gội Arau Baby', 'sua-tam-goi-arau-baby.png'],
                ['Dầu dưỡng Bio Oil', 'dau-duong-bio-oil.png'],
                ['Kem dưỡng ẩm Cetaphil', 'kem-duong-am-cetaphil.png'],
            ],
            'Đồ dùng - Gia dụng' => [
                ['Bình sữa Comotomo', 'binh-sua-comotomo.png'],
                ['Máy tiệt trùng bình sữa', 'may-tiet-trung-binh-sua.png'],
                ['Ghế ăn dặm Mastela', 'ghe-an-dam-mastela.png'],
                ['Nhiệt kế điện tử Omron', 'nhiet-ke-dien-tu-omron.png'],
            ],
            'Thời trang và phụ kiện' => [
                ['Bộ quần áo Carter', 'bo-quan-ao-carter.png'],
                ['Mũ len cho bé', 'mu-len-cho-be.png'],
                ['Vớ sơ sinh', 'vo-so-sinh.png'],
                ['Yếm ăn chống thấm', 'yem-an-chong-tham.png'],
            ],
            'Đồ chơi, học tập' => [
                ['Đồ chơi xúc xắc', 'do-choi-xuc-xac.png'],
                ['Xe tập đi cho bé', 'xe-tap-di-cho-be.png'],
                ['Bảng chữ cái nam châm', 'bang-chu-cai-nam-cham.png'],
                ['Đồ chơi xếp hình Lego', 'do-choi-xep-hinh-lego.png'],
            ],
        ];

        $sanPhamData = [];
        $counter = 1;

        foreach ($productsByCategory as $categoryName => $products) {
            $danhMuc = $danhMucList[$categoryName] ?? null;
            if (!$danhMuc) {
                continue;
            }

            // Tổng tồn kho mong muốn cho mỗi danh mục
            $totalTonKho = 4;
            $numProducts = count($products);
            $defaultQty = intdiv($totalTonKho, $numProducts);
            $remainder = $totalTonKho - ($defaultQty * $numProducts);

            // Khởi tạo mảng tồn kho chia đều
            $quantities = array_fill(0, $numProducts, $defaultQty);
            for ($r = 0; $r < $remainder; $r++) {
                $randIndex = rand(0, $numProducts - 1);
                $quantities[$randIndex]++;
            }

            // foreach ($products as $i => $product) {
            //     $maSanPham = 'SP' . str_pad($counter, 4, '0', STR_PAD_LEFT);

            //     $sanPhamData[] = [
            //         'id' => (string) Str::uuid(),
            //         'maSanPham' => $maSanPham,
            //         'tenSanPham' => $product[0],
            //         'maSKU' => strtoupper(Str::random(8)),
            //         'VAT' => 8.00,
            //         'giaBan' => random_int(100000, 1000000),
            //         'soLuongTon' => $quantities[$i],
            //         'moTa' => 'Sản phẩm: ' . $product[0],
            //         'danhMuc_id' => $danhMuc->id,
            //         'hinhAnh' => 'san_pham/' . $product[1],
            //         'thongSoKyThuat' => $this->generateThongSo($product[0]),
            //         'ngayTao' => $now,
            //         'ngayCapNhat' => null,
            //     ];
            //     $counter++;
            // }
            foreach ($products as $i => $product) {
                $maSanPham = 'SP' . str_pad($counter, 4, '0', STR_PAD_LEFT);

                $sanPhamData[] = [
                    'id' => (string) Str::uuid(),
                    'maSanPham' => $maSanPham,
                    'tenSanPham' => $product[0],
                    'maSKU' => strtoupper(Str::random(8)),
                    'VAT' => 8.00,
                    'giaBan' => random_int(100000, 1000000),
                    'soLuongTon' => random_int(20, 100), // random tồn kho lớn
                    'moTa' => 'Sản phẩm: ' . $product[0],
                    'danhMuc_id' => $danhMuc->id,
                    'hinhAnh' => 'san_pham/' . $product[1],
                    'thongSoKyThuat' => $this->generateThongSo($product[0]),
                    'ngayTao' => $now,
                    'ngayCapNhat' => null,
                    'is_noi_bat' => rand(0, 1),
                ];
                $counter++;
            }
        }
        // Insert tất cả
        foreach ($sanPhamData as $data) {
            \App\Models\SanPham::create($data);
        }

        // Cập nhật số lượng sản phẩm mỗi danh mục
        $this->updateQuantities();
    }


    protected function generateThongSo(string $tenSanPham): array
    {
        // Nhóm Sữa
        if (str_contains($tenSanPham, 'Sữa')) {
            return [
                'Độ tuổi' => '2 - 6 tuổi',
                'Khối lượng' => '800g',
                'Hạn sử dụng' => '18 tháng',
                'Nơi sản xuất' => 'Singapore',
                'Nhiệt độ pha' => '37 - 40°C',
                'Đặc tính' => ['Tăng đề kháng', 'Phát triển chiều cao'],
            ];
        }

        // Nhóm Bỉm, Tã
        if (str_contains($tenSanPham, 'Bỉm') || str_contains($tenSanPham, 'Tã')) {
            return [
                'Size' => 'XL',
                'Cân nặng' => '12 - 17 kg',
                'Hạn sử dụng' => '36 tháng',
                'Chất liệu' => ['Hạt Polymer', 'Sợi tre'],
                'Mùi hương' => 'Không mùi',
                'Tiện ích' => ['Vạch báo đầy', 'Băng dính cuộn'],
            ];
        }

        // Nhóm Thực phẩm - Đồ uống
        if (
            str_contains($tenSanPham, 'Bột') ||
            str_contains($tenSanPham, 'Cháo') ||
            str_contains($tenSanPham, 'Nước') ||
            str_contains($tenSanPham, 'Súp')
        ) {
            return [
                'Khối lượng' => '200g',
                'Hạn sử dụng' => '12 tháng',
                'Hướng dẫn bảo quản' => 'Nơi khô ráo, thoáng mát',
                'Thành phần' => ['Ngũ cốc', 'Vitamin'],
            ];
        }

        // Nhóm Sức khỏe & Vitamin
        if (
            str_contains($tenSanPham, 'Vitamin') ||
            str_contains($tenSanPham, 'Siro') ||
            str_contains($tenSanPham, 'Men') ||
            str_contains($tenSanPham, 'DHA')
        ) {
            return [
                'Dung tích' => '100ml',
                'Hạn sử dụng' => '24 tháng',
                'Đối tượng sử dụng' => 'Trẻ từ 1 tuổi',
                'Hướng dẫn bảo quản' => 'Nơi mát, tránh ánh nắng',
            ];
        }

        // Nhóm Mỹ phẩm
        if (
            str_contains($tenSanPham, 'Kem') ||
            str_contains($tenSanPham, 'Sữa tắm') ||
            str_contains($tenSanPham, 'Dầu')
        ) {
            return [
                'Dung tích' => '200ml',
                'Hạn sử dụng' => '36 tháng',
                'Thành phần' => ['Chiết xuất thiên nhiên'],
                'Công dụng' => 'Dưỡng ẩm & bảo vệ da',
            ];
        }

        // Nhóm Đồ dùng - Gia dụng
        if (
            str_contains($tenSanPham, 'Bình') ||
            str_contains($tenSanPham, 'Máy') ||
            str_contains($tenSanPham, 'Ghế') ||
            str_contains($tenSanPham, 'Nhiệt kế')
        ) {
            return [
                'Chất liệu' => 'Nhựa cao cấp',
                'Xuất xứ' => 'Nhật Bản',
                'Bảo hành' => '12 tháng',
            ];
        }

        // Nhóm Thời trang - Phụ kiện
        if (
            str_contains($tenSanPham, 'Bộ quần áo') ||
            str_contains($tenSanPham, 'Mũ') ||
            str_contains($tenSanPham, 'Vớ') ||
            str_contains($tenSanPham, 'Yếm')
        ) {
            return [
                'Size' => 'Free Size',
                'Chất liệu' => 'Cotton 100%',
                'Màu sắc' => 'Nhiều màu',
            ];
        }

        // Nhóm Đồ chơi - Học tập
        if (
            str_contains($tenSanPham, 'Đồ chơi') ||
            str_contains($tenSanPham, 'Xe') ||
            str_contains($tenSanPham, 'Bảng')
        ) {
            return [
                'Độ tuổi phù hợp' => '6 tháng - 5 tuổi',
                'Chất liệu' => 'Nhựa an toàn',
                'Xuất xứ' => 'Việt Nam',
            ];
        }

        // Mặc định
        return [
            'Thông số' => 'Đang cập nhật',
        ];
    }


    protected function updateQuantities(): void
    {
        $danhMucCounts = DB::table('SanPham')
            ->select('danhMuc_id', DB::raw('COUNT(*) as total'))
            ->groupBy('danhMuc_id')
            ->pluck('total', 'danhMuc_id');

        foreach ($danhMucCounts as $danhMucId => $total) {
            DB::table('DanhMuc')->where('id', $danhMucId)->update([
                'soLuongSanPham' => $total
            ]);
        }
    }
}
