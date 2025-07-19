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
                'moTa' => 'Chuyên cung cấp sữa cho bé từ sơ sinh đến 6 tuổi, đầy đủ dinh dưỡng, hỗ trợ phát triển chiều cao, cân nặng và trí tuệ toàn diện.',
                'hinhAnh' => 'danh_muc/the-gioi-sua.png',
            ],
            [
                'ten' => 'Bỉm, tã',
                'moTa' => 'Tã bỉm cao cấp cho bé, thấm hút vượt trội, chống tràn, mềm mại, bảo vệ làn da nhạy cảm và an toàn suốt ngày dài hoạt động.',
                'hinhAnh' => 'danh_muc/bim-ta.png',
            ],
            [
                'ten' => 'Thực phẩm - Đồ uống',
                'moTa' => 'Thực phẩm bổ sung, đồ uống dinh dưỡng cho bé và mẹ, hỗ trợ tiêu hóa tốt, tăng đề kháng và cung cấp năng lượng mỗi ngày.',
                'hinhAnh' => 'danh_muc/thuc-pham.png',
            ],
            [
                'ten' => 'Sức khoẻ & Vitamin',
                'moTa' => 'Vitamin cho bé, thực phẩm chức năng giúp tăng sức đề kháng, phát triển thể chất và phòng ngừa các bệnh vặt trong giai đoạn đầu đời.',
                'hinhAnh' => 'danh_muc/suc-khoe.png',
            ],
            [
                'ten' => 'Chăm sóc - Mỹ phẩm',
                'moTa' => 'Mỹ phẩm cho mẹ và bé như dầu gội, sữa tắm, kem dưỡng, an toàn, dịu nhẹ, không gây kích ứng, phù hợp với da nhạy cảm.',
                'hinhAnh' => 'danh_muc/cham-soc.png',
            ],
            [
                'ten' => 'Đồ dùng - Gia dụng',
                'moTa' => 'Đồ dùng trẻ em như bình sữa, chén ăn, máy hâm sữa, khay ăn dặm… giúp chăm sóc bé dễ dàng, tiện lợi và an toàn mỗi ngày.',
                'hinhAnh' => 'danh_muc/do-dung.png',
            ],
            [
                'ten' => 'Thời trang và phụ kiện',
                'moTa' => 'Thời trang trẻ em gồm quần áo, giày dép, mũ nón và phụ kiện đáng yêu, chất liệu an toàn, thiết kế đẹp mắt phù hợp theo mùa.',
                'hinhAnh' => 'danh_muc/thoi-trang.png',
            ],
            [
                'ten' => 'Đồ chơi, học tập',
                'moTa' => 'Đồ chơi trẻ em an toàn, giúp phát triển tư duy, kỹ năng vận động, cùng dụng cụ học tập kích thích trí não và sự sáng tạo cho bé.',
                'hinhAnh' => 'danh_muc/do-choi.png',
            ],
        ];



        $nhaCungCapIds = DB::table('NhaCungCap')->pluck('id')->toArray();

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
                'nhaCungCap' => $nhaCungCapIds[array_rand($nhaCungCapIds)],
            ];
            $counter++;
        }


        DB::table('DanhMuc')->insert($data);
    }
}
