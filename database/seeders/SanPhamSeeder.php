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
            'Chăm sóc - Mỹ phẩm' => [
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
                    'moTa' => 'Sản phẩm ' . $product[0] . ' là lựa chọn chất lượng cao được nhiều mẹ tin dùng. Đảm bảo an toàn, tiện lợi và phù hợp cho nhu cầu chăm sóc mẹ và bé hiện đại. Xuất xứ rõ ràng, đạt tiêu chuẩn an toàn, và phù hợp với nhiều độ tuổi hoặc mục đích sử dụng.',
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
                'Khối lượng' => '800g / hộp',
                'Hạn sử dụng' => '18 tháng kể từ ngày sản xuất',
                'Nơi sản xuất' => 'Singapore',
                'Nhiệt độ pha' => '37 - 40°C',
                'Hướng dẫn sử dụng' => 'Pha 4 muỗng với 180ml nước ấm ở 40°C, lắc đều, sử dụng trong vòng 2 giờ',
                'Thành phần chính' => ['DHA', 'ARA', 'Sắt', 'Kẽm', 'Canxi', 'Vitamin A, D, E'],
                'Bảo quản' => 'Bảo quản nơi khô ráo, tránh ánh nắng trực tiếp. Đậy nắp kín sau khi mở.',
                'Đặc tính' => ['Tăng đề kháng', 'Phát triển trí tuệ', 'Tăng chiều cao'],
                'Lưu ý' => 'Không dùng cho trẻ dị ứng với đạm sữa bò. Không dùng lò vi sóng để hâm.',
                'Lưu ý' => 'Không dùng cho trẻ dị ứng với đạm sữa bò. Không dùng lò vi sóng để hâm.'

            ];
        }

        // Nhóm Bỉm, Tã
        if (str_contains($tenSanPham, 'Bỉm') || str_contains($tenSanPham, 'Tã')) {
            return [
                'Size' => 'NB đến XXL',
                'Cân nặng' => 'Từ 3kg đến trên 17kg',
                "Số lượng miếng" => "40 - 72 miếng/gói",
                "Chất liệu" => ["Hạt polymer siêu thấm", "Vải không dệt", "Sợi tre tự nhiên"],
                "Tính năng" => ["Vạch báo đầy", "Chống tràn", "Thoáng khí 4 chiều"],
                "Hạn sử dụng" => "36 tháng kể từ NSX",
                "Xuất xứ" => "Việt Nam / Nhật Bản / Hàn Quốc",
                "Lưu ý" => "Thay bỉm 3-4 tiếng/lần để bảo vệ làn da bé."
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
                'Khối lượng' => '120g - 200g',
                'Hạn sử dụng' => '12 tháng',
                'Hướng dẫn sử dụng' => 'Mở bao bì, hâm nóng cách thuỷ hoặc trong lò vi sóng. Cho bé dùng ngay.',
                'Thành phần' => ['Gạo', 'Rau củ', 'Thịt gà', 'Vitamin nhóm B', 'Canxi'],
                'Đối tượng sử dụng' => 'Trẻ từ 6 tháng tuổi trở lên',
                'Xuất xứ' => 'Việt Nam',
                'Bảo quản' => 'Bảo quản nơi mát, tránh ánh sáng, sử dụng trong 24h sau khi mở bao bì.',
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
                'Dung tích / Khối lượng' => '100ml / 60 viên',
                'Hạn sử dụng' => '24 tháng',
                'Đối tượng sử dụng' => 'Trẻ từ 1 tuổi trở lên',
                'Thành phần chính' => ['Vitamin C', 'DHA', 'Kẽm', 'Probiotic', 'Lysine'],
                'Hướng dẫn sử dụng' => 'Uống trực tiếp bằng thìa hoặc pha loãng với nước, dùng vào buổi sáng.',
                'Bảo quản' => 'Để nơi khô ráo, tránh ánh nắng và nhiệt độ cao.',
                'Lưu ý' => 'Tham khảo ý kiến bác sĩ nếu bé đang dùng thuốc điều trị.',
            ];
        }

        // Nhóm Mỹ phẩm
        if (
            str_contains($tenSanPham, 'Kem') ||
            str_contains($tenSanPham, 'Sữa tắm') ||
            str_contains($tenSanPham, 'Dầu')
        ) {
            return [
                'Dung tích' => '200ml - 500ml',
                'Công dụng' => 'Dưỡng ẩm, chống hăm, làm dịu kích ứng, làm sạch nhẹ nhàng',
                'Thành phần' => ['Chiết xuất cúc La Mã', 'Vitamin E', 'Panthenol'],
                'Hướng dẫn sử dụng' => 'Thoa trực tiếp lên da sau khi tắm hoặc khi cần thiết.',
                'Hạn sử dụng' => '36 tháng',
                'Xuất xứ' => 'Pháp / Đức / Nhật Bản',
                'Lưu ý' => 'Tránh để dính vào mắt. Ngưng sử dụng nếu có dấu hiệu kích ứng.',
            ];
        }

        // Nhóm Đồ dùng - Gia dụng
        // if (
        //     str_contains($tenSanPham, 'Bình') ||
        //     str_contains($tenSanPham, 'Máy') ||
        //     str_contains($tenSanPham, 'Ghế') ||
        //     str_contains($tenSanPham, 'Nhiệt kế')
        // ) {
        //     return [
        //         'Chất liệu' => 'Nhựa PP, Silicon, Thép không gỉ',
        //         'Tính năng' => ['Chịu nhiệt cao', 'Kháng khuẩn', 'Dễ tháo lắp'],
        //         'Bảo hành' => '6 - 12 tháng',
        //         'Tiêu chuẩn' => 'BPA Free, CE Certified',
        //         'Xuất xứ' => 'Nhật Bản / Hàn Quốc',
        //         'Hướng dẫn vệ sinh' => 'Rửa bằng nước ấm, có thể tiệt trùng bằng hơi nước hoặc lò vi sóng.',
        //         'Lưu ý' => 'Kiểm tra tình trạng sản phẩm định kỳ để đảm bảo an toàn cho bé.',
        //     ];
        // }
        if (str_contains($tenSanPham, 'Bình')) {
            return [
                'Chất liệu' => 'Nhựa PP an toàn, không chứa BPA',
                'Dung tích' => '150ml - 250ml',
                'Tính năng' => ['Chống sặc', 'Van chống đầy hơi', 'Dễ vệ sinh'],
                'Xuất xứ' => 'Mỹ / Nhật Bản',
                'Bảo hành' => '6 tháng',
                'Lưu ý' => 'Tiệt trùng trước và sau khi sử dụng',
            ];
        }

        if (str_contains($tenSanPham, 'Máy')) {
            return [
                'Loại máy' => 'Tiệt trùng / Hâm sữa / Xay đồ ăn',
                'Chất liệu' => 'Thép không gỉ, nhựa ABS cao cấp',
                'Công suất' => '300W - 600W',
                'Tính năng' => ['Khử trùng bằng hơi nước', 'Tự động ngắt điện', 'Dễ tháo lắp'],
                'Xuất xứ' => 'Trung Quốc / Nhật Bản',
                'Bảo hành' => '12 tháng',
            ];
        }

        if (str_contains($tenSanPham, 'Ghế')) {
            return [
                'Loại ghế' => 'Ghế ăn dặm / Ghế bập bênh',
                'Chất liệu' => 'Khung thép sơn tĩnh điện, đệm da PU',
                'Tính năng' => ['Gập gọn', 'Điều chỉnh độ cao', 'Dây đai an toàn'],
                'Trọng lượng chịu tải' => 'Lên đến 20kg',
                'Xuất xứ' => 'Việt Nam / Thái Lan',
                'Lưu ý' => 'Luôn giám sát trẻ khi sử dụng.',
            ];
        }

        if (str_contains($tenSanPham, 'Nhiệt kế')) {
            return [
                'Loại' => 'Nhiệt kế điện tử / Nhiệt kế hồng ngoại',
                'Thời gian đo' => '1 - 3 giây',
                'Độ chính xác' => '± 0.2°C',
                'Tính năng' => ['Màn hình LCD', 'Báo sốt bằng màu', 'Lưu kết quả đo'],
                'Xuất xứ' => 'Hàn Quốc / Đức',
                'Bảo hành' => '12 tháng',
                'Lưu ý' => 'Không rơi vỡ, tránh tiếp xúc nước.',
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
                'Size' => 'NB - XL (0 - 3 tuổi)',
                'Chất liệu' => 'Cotton 100%, vải sợi tre',
                'Màu sắc' => 'Nhiều màu sắc pastel & trung tính',
                'Hướng dẫn giặt' => 'Giặt nhẹ bằng tay hoặc máy giặt chế độ dịu nhẹ, không dùng chất tẩy mạnh',
                'Lưu ý' => 'Ủi ở nhiệt độ thấp. Tránh phơi dưới ánh nắng gắt để giữ màu vải.',
            ];
        }

        // Nhóm Đồ chơi - Học tập
        if (
            str_contains($tenSanPham, 'Đồ chơi') ||
            str_contains($tenSanPham, 'Xe') ||
            str_contains($tenSanPham, 'Bảng')
        ) {
            return [
                'Độ tuổi sử dụng' => '6 tháng - 5 tuổi',
                'Chất liệu' => 'Nhựa ABS, Gỗ tự nhiên',
                'Tiêu chuẩn' => 'EN71, ASTM',
                'Lợi ích' => ['Phát triển tư duy logic', 'Tăng khả năng quan sát', 'Giúp phối hợp tay mắt'],
                'Hướng dẫn sử dụng' => 'Chơi cùng người lớn để hỗ trợ bé học hỏi và phát triển toàn diện.',
                'Lưu ý' => 'Tránh để các chi tiết nhỏ gần trẻ dưới 3 tuổi không có người giám sát.',
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
