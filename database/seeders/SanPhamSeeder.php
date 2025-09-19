<?php

namespace Database\Seeders;

use App\Models\SanPham;
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
                ['Sữa Abbott Grow', 'sua-abbott-grow.png'],
                ['Sữa Similac Neosure', 'sua-similac-neosure.png'],
                ['Sữa Similac Neosure ', 'sua-similac-total-comfort.png'],
                ['Sữa Enfamil Enspire Infant Formula', 'sua-enfamil-enspire.png'],
                ['Sữa Enfamil NeuroPro Infant Formula ', 'sua-enfamil-neuropro.png'],
                ['Sữa Abbott Grow 1 ', 'sua-abbott-grow-1.jpg'],

            ],
            'Bỉm, tã' => [
                ['Bỉm Pampers Newborn', 'bim-pampers-newborn.png'],
                ['Bỉm Huggies Dry', 'bim-huggies-dry.png'],
                ['Bỉm Bobby Extra Soft', 'bim-bobby-extra-soft.png'],
                ['Tã Merries Nhật Bản', 'ta-merries-nhat-ban.png'],
                ['Tã quần Huggies Skincare', 'ta-quan-huggies.png'],
                ['Bỉm tã quần Bobby', 'ta-quan-bobby.png'],
                ['Bỉm tã dán Moony', 'ta-dan-moony.png'],
                ['Bỉm tã quần thiên nhiên Molfix Jumbo', 'bim-ta-quan-thien-nhien.png'],
                ['Bỉm tã dán Huggies Platinum Nature Made', 'bim-ta-dan-huggies-platinum.png'],
                ['Bỉm tã quần Moony bé trai', 'ta-quan-moony-be-trai.png'],

            ],
            'Thực phẩm - Đồ uống' => [
                ['Bột ăn dặm Nestle', 'bot-an-dam-nestle.png'],
                ['Cháo tươi SG Food', 'chao-tuoi-sg-food.png'],
                ['Nước trái cây Pigeon', 'nuoc-trai-cay-pigeon.png'],
                ['Súp dinh dưỡng Heinz', 'sup-dinh-duong-heinz.png'],
                ['Xúc Xích Tiệt Trùng Goldkids Cua & Phô Mai', 'xuc-xich-cua-pho-mai.png'],
                ['Thực phẩm bổ sung phô mai Con Bò Cười vuông Belcube vị truyền thống', 'pho-mai-con-bo-cuoi-vuong-le.png'],
                ['Dinh dưỡng 100% trái cây nghiền hữu cơ HiPPiS Organic (Kiwi, Lê, Chuối)', 'trai-cay-nghien-huu-co.png'],
                ['Phô Mai hoa quả Kids Mix Vị Dâu Mâm Xôi 50g - Lốc 4', 'pho-mai-hoa-qua-kids-mix-vi-dau-mam-xoi.jpg'],
                ['Váng sữa Hoff - Vani (Lốc 4 hủ)', 'vang-sua-hff-vani.jpg'],
                ['Mì ăn dặm Hakubaku - Mì Somen Baby (từ 5 tháng tuổi)', 'qt-morinaga-mi-an-dam.jpg'],

            ],
            'Sức khoẻ & Vitamin' => [
                ['Vitamin C ChildLife', 'vitamin-c-childlife.png'],
                ['Siro tăng đề kháng PediaSure', 'siro-tang-de-khang-pediasure.png'],
                ['Men vi sinh BioGaia', 'men-vi-sinh-biogaia.png'],
                ['DHA Bio Island', 'dha-bio-island.png'],
                ['Thực phẩm bổ sung thạch hồng sâm trẻ em NFood', 'thuc-pham-bo-sung-thach-hong-sam-tre-em-nfood.png'],
                ['Thực phẩm bảo vệ sức khỏe Fitobimbi Sonno', 'vitamin-fitobimbi-sonno.jpg'],
                ['Men vi sinh Synteract Baby Drops Oil ', 'men-vi-sinh-synteract-baby-drops-oil.png'],
                ['Thực phẩm bảo vệ sức khỏe Fitobimbi Sonno', 'fitobimbi-sonno.jpg'],
                ['Ferrolip baby', 'ferrolip-baby.png'],
                ['Thực phẩm bổ sung thạch Calci trẻ em NFood hương đào', 'thuc-pham-bo-sung-thach-calci-tre-em-nfood-huong-dao.png'],
            ],
            'Chăm sóc - Mỹ phẩm' => [
                ['Kem chống hăm Bepanthen', 'kem-chong-ham-bepanthen.png'],
                ['Sữa tắm gội Arau Baby', 'sua-tam-goi-arau-baby.png'],
                ['Dầu dưỡng Bio Oil', 'dau-duong-bio-oil.png'],
                ['Kem dưỡng ẩm Cetaphil', 'kem-duong-am-cetaphil.png'],
                ['Sữa tắm gội toàn thân Johnson Baby 200ml', 'sua-tam-goi-toan-than-johnson-baby.jpg'],
                ['Sữa tắm gội Lactacyd Baby Extra Milky 500ml ', 'sua-tam-goi-ngua-rom-say-cho-be-lactacyd-milky.jpg'],
                ['Tắm gội dịu nhẹ Pigeon Jojoba 200ml (không paraben)', 'tam-goi-diu-nhe-pigeon-jojoba-200ml-khong-paraben.png'],
                ['Phấn phủ làm dịu da Goongbe Pri-mmune 25g', 'phan-phu-lam-diu-da-goongbe-pri-mmune.png'],
                ['Kem làm dịu hăm tã Goongbe Pri-mmune 80ml', 'kem-lam-diu-ham-ta-goongbe-pri-mmune.png'],
                ['Kem chống hăm, chống nẻ trẻ em Crevil 125ml', 'kem-chong-ham-chong-ne-tre-em-crevil.png'],

            ],
            'Đồ dùng - Gia dụng' => [
                ['Bình sữa Comotomo', 'binh-sua-comotomo.png'],
                ['Máy tiệt trùng bình sữa', 'may-tiet-trung-binh-sua.png'],
                ['Ghế ăn dặm Mastela', 'ghe-an-dam-mastela.png'],
                ['Nhiệt kế điện tử Omron', 'nhiet-ke-dien-tu-omron.png'],
                ['Ty ngậm Mam start 0-2m (girls)', 'ty-ngam-mam-start-0-2m-girls.jpg'],
                ['Nhiệt kế hồng ngoại đo trán Microlife FR1MF1', 'nhiet-ke-hong-ngoai-do-tran-microlife-fr1mf.png'],
                ['Máy hút sữa điện đơn Spectra M1', 'may-hut-sua-dien-don-spectra-m1.jpg'],
                ['Khăn tắm cotton ConCung Good BM9T màu trắng', 'khan-tam-cotton-concung-good-bm9t-mau-trang.jpg'],
                ['Xe đẩy hai chiều cao cấp Cool Baby màu xám', 'xe-day-2-chieu-cao-cap-cool-baby-c008h-xam.jpg'],
                ['Bình tập uống chống tràn MAM Starter Cup 150ml màu hồng', 'binh-tap-uong-mam-starter-cup-150ml-girls.jpg'],
            ],

            'Thời trang và phụ kiện' => [
                ['Bộ quần áo Carter', 'bo-quan-ao-carter.png'],
                ['Mũ len cho bé', 'mu-len-cho-be.png'],
                ['Vớ sơ sinh', 'vo-so-sinh.png'],
                ['Yếm ăn chống thấm', 'yem-an-chong-tham.png'],
                ['Hộp vuông thun lớn cột tóc cho bé Animo A2204_MN024', 'hop-vuong-thun-lon-cot-toc-cho-be-animo-nhieu-mau.jpg'],
                ['Đầm vải bé gái Animo TX822003 (6M-6Y,Hồng)', 'dam-vai-be-gai-animo-tx822003-6-9m-hong.jpg'],
                ['Bodysuit đùi Animo Easy KV0924067 (0-12M,Nhiều màu)', 'bodysuit-dui-animo-easy-nhieu-mau.jpg'],
                ['Bodysuit tính năng tam giác, vải modal BST Thiên Nga Animo BMC822080 (0-12M,Vàng)', 'bodysuit-tinh-nang-tam-giac-vai-modal-bst-thien-nga-animo-bmc822080-0-12m-vang.jpg'],
                ['Bodysuit tính năng tam giác, vải lưới Animo I0322026 (0-12M,Beige,Giao mẫu ngẫu nhiên)', 'bodysuit-tinh-nang-tam-giac-vai-luoi-animo-beige.png'],
            ],

            'Đồ chơi, học tập' => [
                ['Đồ chơi xúc xắc', 'do-choi-xuc-xac.png'],
                ['Xe tập đi cho bé', 'xe-tap-di-cho-be.png'],
                ['Bảng chữ cái nam châm', 'bang-chu-cai-nam-cham.png'],
                ['Đồ chơi xếp hình Lego', 'do-choi-xep-hinh-lego.png'],
                ['Vali kéo đi biển hình con vịt 8pcs', 'vali-keo-di-bien-hinh-con-vit.jpg'],
                ['Đồ chơi bé trổ tài đầu bếp Polesie', 'do-choi-be-tro-tai-dau-bep-polesie.jpg'],
                ['Lưới thảy vòng vịt bánh xe HT078 (TM)', 'luoi-thay-vong-vit-banh-xe.jpg'],
                ['Xe Tập Đi Cho Bé Autoru AUBW02 (màu ghế ngồi ngẫu nhiên)', 'xe-tap-di-cho-be-autoru-aubw02.jpg'],


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

            // Giá ngẫu nhiên rồi làm tròn về bậc 1.000đ (hoặc 500đ tuỳ bạn)
            $gia = random_int(100_000, 1_000_000);
            // tròn 1.000đ:
            $gia = (int) (round($gia / 1000) * 1000);
            // nếu muốn tròn 500đ: $gia = (int) (round($gia / 500) * 500);
            foreach ($products as $i => $product) {
                $maSanPham = 'SP' . str_pad($counter, 4, '0', STR_PAD_LEFT);
                $isFeatured = (bool) rand(0, 1);

                $sanPhamData[] = [
                    'id' => (string) Str::uuid(),
                    'maSanPham' => $maSanPham,
                    'tenSanPham' => $product[0],
                    'maSKU' => strtoupper(Str::random(8)),
                    'VAT' => 8.00,
                    'giaBan' => $gia,
                    'soLuongTon' => random_int(20, 100), // random tồn kho lớn
                    'moTa' => 'Sản phẩm ' . $product[0] . ' là lựa chọn chất lượng cao được nhiều mẹ tin dùng. Đảm bảo an toàn, tiện lợi và phù hợp cho nhu cầu chăm sóc mẹ và bé hiện đại. Xuất xứ rõ ràng, đạt tiêu chuẩn an toàn, và phù hợp với nhiều độ tuổi hoặc mục đích sử dụng.',
                    'danhMuc_id' => $danhMuc->id,
                    'hinhAnh' => 'san_pham/' . $product[1],
                    'thongSoKyThuat' => $this->generateThongSo($product[0], $categoryName),
                    'ngayTao' => $now,
                    'ngayCapNhat' => null,
                    'is_noi_bat'      => $isFeatured ? 1 : 0,
                    'flash_sale'   => !$isFeatured ? (random_int(5, 20) / 100) : 0,
                ];
                $counter++;
            }
        }
        // Insert tất cả
        foreach ($sanPhamData as $data) {
            SanPham::create($data);
        }

        // Cập nhật số lượng sản phẩm mỗi danh mục
        $this->updateQuantities();
    }


    protected function generateThongSo(string $tenSanPham, string $danhMuc): array
    {
        $has = fn(string $kw) => mb_stripos($tenSanPham, $kw) !== false;

        // ==== MAP THEO DANH MỤC (mặc định) ====
        $map = [
            'Thế giới sữa' => [
                'Độ tuổi'           => '0 - 6 tuổi',
                'Khối lượng'        => '200g - 900g',
                'Hạn sử dụng'       => '12 - 24 tháng',
                'Nơi sản xuất'      => 'VN / Nhật / Singapore',
                'Nhiệt độ pha'      => '37 - 40°C',
                'Hướng dẫn sử dụng' => 'Pha theo hướng dẫn bao bì, dùng trong 2 giờ',
                'Thành phần chính'  => ['DHA', 'ARA', 'Sắt', 'Kẽm', 'Canxi', 'Vitamin A,D,E'],
                'Bảo quản'          => 'Khô ráo, tránh nắng. Đậy kín sau khi mở',
                'Đặc tính'          => ['Tăng đề kháng', 'Phát triển trí tuệ', 'Tăng chiều cao'],
                'Lưu ý'             => 'Không dùng cho trẻ dị ứng đạm sữa bò',
            ],
            'Bỉm, tã' => [
                'Size'              => 'NB - XXL',
                'Cân nặng'          => '3kg → >17kg',
                'Số lượng miếng'    => '40 - 72 miếng/gói',
                'Chất liệu'         => ['Hạt siêu thấm', 'Vải không dệt', 'Sợi tre'],
                'Tính năng'         => ['Vạch báo đầy', 'Chống tràn', 'Thoáng khí 4 chiều'],
                'Hạn sử dụng'       => '36 tháng kể từ NSX',
                'Xuất xứ'           => 'VN / Nhật / Hàn',
                'Lưu ý'             => 'Thay 3-4 tiếng/lần để bảo vệ da bé',
            ],
            'Thực phẩm - Đồ uống' => [
                'Khối lượng'        => '50g - 500g',
                'Hạn sử dụng'       => '6 - 18 tháng',
                'Thành phần'        => ['Ngũ cốc', 'Rau củ', 'Sữa/Phô mai', 'Đạm động vật'],
                'Dinh dưỡng'        => ['~200 kcal/100g', 'Đạm ~10%', 'Chất xơ ~5%'],
                'Hướng dẫn sử dụng' => 'Dùng trực tiếp hoặc hâm 2–3 phút',
                'Đối tượng'         => 'Trẻ từ 6 tháng tuổi trở lên',
                'Bảo quản'          => 'Ngăn mát hoặc nơi thoáng; dùng trong 24h sau mở',
                'Chứng nhận'        => 'ISO 22000 / HACCP',
                'Lưu ý'             => 'Tránh dùng nếu dị ứng sữa/đạm bò',
            ],
            'Sức khoẻ & Vitamin' => [
                'Dung tích/Quy cách' => 'Chai 100ml / Hộp 30 viên',
                'Hạn sử dụng'       => '24 - 36 tháng',
                'Thành phần chính'  => ['Vitamin A,C,D,E', 'Kẽm', 'DHA', 'Probiotic'],
                'Công dụng'         => ['Tăng đề kháng', 'Hỗ trợ tiêu hoá', 'Phát triển trí não'],
                'Đối tượng sử dụng' => 'Trẻ >1 tuổi / người lớn / PN mang thai',
                'Cách dùng'         => 'Uống sau ăn, 1-2 lần/ngày',
                'Bảo quản'          => 'Khô ráo, tránh nắng, <30°C',
                'Chứng nhận'        => 'GMP-WHO / FDA',
                'Chống chỉ định'    => 'Mẫn cảm với thành phần sản phẩm',
            ],
            'Chăm sóc - Mỹ phẩm' => [
                'Dung tích'         => '100ml - 500ml',
                'Công dụng'         => ['Dưỡng ẩm', 'Giảm hăm', 'Làm dịu kích ứng', 'Làm sạch nhẹ'],
                'Thành phần'        => ['Cúc La Mã', 'Vitamin E', 'Panthenol', 'Glycerin'],
                'Kết cấu'           => 'Kem mềm/gel nhẹ, thấm nhanh',
                'Mùi hương'         => 'Không hương liệu / dịu nhẹ',
                'Cách dùng'         => 'Thoa sau tắm hoặc khi da khô ráp',
                'Bảo quản'          => 'Tránh nắng trực tiếp, đậy kín sau dùng',
                'Hạn sử dụng'       => '36 tháng',
                'Xuất xứ'           => 'Pháp / Đức / Nhật',
                'Chứng nhận'        => 'Dermatologically Tested',
            ],
            'Đồ dùng - Gia dụng' => [
                'Chất liệu'         => ['Nhựa PP', 'Silicon y tế', 'Thép không gỉ', 'Vải polyester'],
                'Dung tích/Công suất' => '150–250ml (bình) / 300–600W (máy)',
                'Tính năng'         => ['BPA Free', 'Chống sặc', 'Tiệt trùng hơi nước', 'Khoá an toàn'],
                'Độ tuổi phù hợp'   => '0 - 3 tuổi',
                'Kích thước'        => 'Tuỳ sản phẩm (khăn 70x100 cm / xe đẩy gập gọn)',
                'Bảo hành'          => '6 - 12 tháng',
                'Tiêu chuẩn'        => ['CE', 'ISO 9001'],
                'Xuất xứ'           => 'Nhật / Hàn / VN',
                'Lưu ý'             => 'Tiệt trùng định kỳ, kiểm tra tình trạng trước khi dùng',
            ],
            'Thời trang và phụ kiện' => [
                'Size'              => 'NB – 6Y',
                'Chất liệu'         => ['Cotton 100%', 'Modal', 'Sợi tre'],
                'Màu sắc'           => ['Pastel', 'Trung tính', 'In hình'],
                'Kiểu dáng'         => ['Bodysuit', 'Đầm xòe', 'Áo cộc tay'],
                'Đặc điểm'          => ['Thoáng khí', 'Thấm hút', 'Co giãn 4 chiều'],
                'Hướng dẫn giặt'    => 'Giặt nhẹ, không tẩy mạnh, phơi râm',
                'Lưu ý'             => 'Ủi nhiệt độ thấp',
                'Đối tượng'         => 'Trẻ sơ sinh đến 6 tuổi',
                'Xuất xứ'           => 'VN / CN / Hàn',
            ],
            'Đồ chơi, học tập' => [
                'Độ tuổi'           => '6 tháng – 7 tuổi',
                'Chất liệu'         => ['Nhựa ABS an toàn', 'Gỗ sơn gốc nước'],
                'Tính năng'         => ['Phát triển vận động', 'Rèn logic', 'Tương tác nhóm'],
                'Tiêu chuẩn'        => ['EN71', 'ASTM'],
                'Màu sắc'           => 'Đa dạng, bắt mắt',
                'Kích thước'        => '30x20x10 cm – 80x50x40 cm',
                'Lợi ích'           => ['Kích thích sáng tạo', 'Tăng tập trung', 'An toàn cho bé'],
                'Xuất xứ'           => 'VN / CN / Ba Lan',
                'Lưu ý'             => 'Tránh chi tiết nhỏ với trẻ < 3 tuổi nếu không giám sát',
            ],
        ];

        $spec = $map[$danhMuc] ?? ['Thông số' => 'Đang cập nhật'];


        // Thực phẩm - Đồ uống (Xúc xích / Phô mai / Váng sữa / Mì)
        if ($has('Xúc Xích') || $has('Phô Mai') || $has('Váng sữa') || $has('Mì')) {
            $spec = array_merge($spec, [
                'Khối lượng'        => '50g - 200g',
                'Hạn sử dụng'       => '6 - 12 tháng',
                'Thành phần'        => ['Đạm động vật', 'Sữa', 'Ngũ cốc', 'Khoáng chất'],
                'Dinh dưỡng'        => ['Năng lượng ~200 kcal/100g', 'Đạm ~10%', 'Chất xơ ~5%'],
                'Đối tượng sử dụng' => 'Trẻ từ 1 tuổi trở lên',
                'Hướng dẫn sử dụng' => 'Dùng trực tiếp hoặc chế biến nhanh trong 3-5 phút',
                'Bảo quản'          => 'Ngăn mát tủ lạnh hoặc nơi thoáng mát',
                'Chứng nhận'        => 'ISO 22000, HACCP',
                'Lưu ý'             => 'Dùng ngay sau khi mở bao bì.',
            ]);
        }

        // Sức khoẻ & Vitamin: Ferrolip
        if ($has('Ferrolip')) {
            $spec = array_merge($spec, [
                'Dung tích'          => '20 ống x 5ml',
                'Thành phần chính'   => ['Sắt (Fe)', 'Vitamin C', 'Acid folic'],
                'Công dụng'          => 'Bổ sung sắt cho người thiếu máu, mệt mỏi',
                'Đối tượng sử dụng'  => 'Trẻ em và phụ nữ mang thai',
                'Cách dùng'          => '1 ống/ngày hoặc theo chỉ định bác sĩ',
                'Bảo quản'           => 'Nơi khô thoáng, tránh ánh nắng',
                'Lưu ý'              => 'Dùng theo chỉ dẫn bác sĩ',
            ]);
        }

        // Chăm sóc - Mỹ phẩm: Phấn
        if ($has('Phấn')) {
            $spec = array_merge($spec, [
                'Khối lượng'         => '20g - 30g',
                'Công dụng'          => ['Giảm kích ứng', 'Giữ da khô thoáng'],
                'Thành phần'         => ['Talc', 'Chiết xuất thảo dược'],
                'Cách dùng'          => 'Thoa lớp mỏng lên vùng cần khô thoáng',
                'Bảo quản'           => 'Đậy nắp kín sau khi dùng',
                'Lưu ý'              => 'Tránh hít phải bụi phấn',
            ]);
        }

        // Đồ dùng - Gia dụng: Ty ngậm / Khăn tắm / Xe đẩy
        if ($has('Ty ngậm')) {
            $spec = array_merge($spec, [
                'Chất liệu (đầu ty)' => 'Silicon an toàn',
                'Độ tuổi'            => '0 - 6 tháng',
                'Kích thước ty'      => 'Phù hợp miệng trẻ sơ sinh',
                'Hình dạng'          => 'Mô phỏng ti mẹ',
                'Màu sắc'            => 'Hồng / Xanh dương',
                'Xuất xứ'            => 'Hàn Quốc / Việt Nam / Mỹ',
                'Bảo hành'           => '3 tháng',
                'Tính năng'          => ['Chống sặc', 'Thiết kế ôm miệng'],
                'Vệ sinh'            => 'Tiệt trùng định kỳ bằng nước sôi',
            ]);
        }
        if ($has('Khăn tắm')) {
            $spec = array_merge($spec, [
                'Kích thước khăn'    => '70x100 cm',
                'Chất liệu khăn'     => 'Cotton mềm mại',
                'Trọng lượng khăn'   => '≈300g',
                'Đối tượng sử dụng'  => 'Trẻ sơ sinh và trẻ nhỏ',
                'Tính năng'          => ['Thấm hút tốt', 'Thân thiện với da bé'],
                'Hướng dẫn giặt'     => 'Giặt tay hoặc máy ở nhiệt độ thấp',
                'Lưu ý'              => 'Giặt tay để giữ độ bền',
            ]);
        }
        if ($has('Xe đẩy')) {
            $spec = array_merge($spec, [
                'Loại xe'            => 'Xe đẩy 2 chiều',
                'Tải trọng'          => '15kg - 20kg',
                'Khung vải'          => 'Hợp kim nhôm, vải polyester',
                'Độ tuổi phù hợp'    => '6 tháng - 4 tuổi',
                'Trọng lượng xe'     => '≈3kg',
                'Kích thước gập'     => '30 x 50 cm',
                'Kích thước mở'      => '80 x 100 cm',
                'Xuất xứ'            => 'Hàn Quốc / Đức',
                'Tính năng'          => ['Gập gọn', 'Mái che', 'Khoá an toàn'],
                'Bảo hành'           => '12 tháng',
                'Lưu ý'              => 'Luôn giám sát trẻ khi sử dụng',
            ]);
        }

        // Thời trang & phụ kiện: cột tóc / Đầm / Bodysuit
        if ($has('cột tóc')) {
            $spec = array_merge($spec, [
                'Chất liệu'          => 'Thun co giãn',
                'Màu sắc'            => 'Đa dạng',
                'Kích thước'         => 'Phù hợp nhiều lứa tuổi',
                'Đối tượng sử dụng'  => 'Bé gái từ 1 tuổi trở lên',
                'Số lượng'           => '1 bộ nhiều chiếc',
                'Lưu ý'              => 'Giặt tay để giữ độ bền',
            ]);
        }
        if ($has('Đầm')) {
            $spec = array_merge($spec, [
                'Size'               => 'S - XL',
                'Chất liệu'          => 'Cotton, vải thô',
                'Màu sắc'            => 'Hồng, Vàng, Beige',
                'Họa tiết'           => 'Chấm bi, hoa nhí',
                'Kiểu dáng'          => 'Xòe, dễ vận động',
            ]);
        }
        if ($has('Bodysuit')) {
            $spec = array_merge($spec, [
                'Size'               => '0 - 12 tháng',
                'Chất liệu'          => 'Cotton / Modal / Lưới',
                'Màu sắc'            => 'Đỏ, Hồng, Xanh',
                'Họa tiết'           => 'In hình thú ngộ nghĩnh',
                'Kiểu dáng'          => 'Ôm sát, dễ thương',
                'Tính năng'          => ['Thoáng mát', 'Dễ thay tã'],
                'Khuy cài'           => 'Nút bấm đáy',
            ]);
        }

        // Đồ chơi, học tập: Vali / Lưới
        if ($has('Vali')) {
            $spec = array_merge($spec, [
                'Chất liệu'          => 'Nhựa ABS an toàn',
                'Trọng lượng'        => '≈3kg',
                'Tính năng'          => ['Kéo đi dễ dàng', 'Thiết kế hình thú vui nhộn'],
                'Bảo hành'           => '12 tháng',
                'Xuất xứ'            => 'Hàn Quốc / Đức',
                'Kích thước'         => 'Phù hợp trẻ 2–6 tuổi',
            ]);
        }
        if ($has('Lưới')) {
            $spec = array_merge($spec, [
                'Chất liệu'          => 'Nhựa bền',
                'Tính năng'          => ['Trò chơi vận động ngoài trời'],
                'Kích thước'         => 'Dài 1.5m x Cao 1m',
                'Độ tuổi'            => '3 - 7 tuổi',
            ]);
        }

        return $spec;
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
