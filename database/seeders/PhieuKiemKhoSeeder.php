<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class PhieuKiemKhoSeeder extends Seeder
{
    public function run(): void
    {
        // Tạo danh sách sản phẩm mẫu (giả định đã có bảng SanPham)
        $sanPhamList = DB::table('SanPham')->take(10)->get();

        // Nếu không có sản phẩm, không tạo phiếu
        if ($sanPhamList->isEmpty()) {
            return;
        }

        // Tạo 5 phiếu kiểm kho
        for ($i = 1; $i <= 5; $i++) {
            $phieuId = DB::table('phieu_kiem_kho')->insertGetId([
                'ma_phieu_kiem' => 'PKK00' . $i,
                'ngay_kiem' => now()->subDays(rand(1, 10)),
                'ngay_can_bang' => now(),
                'tong_so_luong_thuc_te' => 0,
                'tong_so_luong_ly_thuyet' => 0,
                'tong_chenh_lech' => 0,
                'tong_lech_tang' => 0,
                'tong_lech_giam' => 0,
                'trang_thai' => 'phieu_tam',
                'nguoi_tao_id' => 1, // chỉnh ID người tạo nếu cần
                'ghi_chu' => 'Phiếu kiểm thử số ' . $i,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $tongThucTe = 0;
            $tongLyThuyet = 0;
            $tongLechTang = 0;
            $tongLechGiam = 0;

            foreach ($sanPhamList->shuffle()->take(rand(3, 6)) as $sp) {
                $lyThuyet = rand(10, 100);
                $thucTe = rand(5, 120);
                $chenhLech = $thucTe - $lyThuyet;

                DB::table('chi_tiet_phieu_kiem_kho')->insert([
                    'phieu_kiem_id' => $phieuId,
                    'san_pham_id' => $sp->id,
                    'so_luong_ly_thuyet' => $lyThuyet,
                    'so_luong_thuc_te' => $thucTe,
                    'so_chenh_lech' => $chenhLech,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $tongLyThuyet += $lyThuyet;
                $tongThucTe += $thucTe;
                if ($chenhLech > 0) $tongLechTang += $chenhLech;
                if ($chenhLech < 0) $tongLechGiam += abs($chenhLech);
            }

            DB::table('phieu_kiem_kho')->where('id', $phieuId)->update([
                'tong_so_luong_thuc_te' => $tongThucTe,
                'tong_so_luong_ly_thuyet' => $tongLyThuyet,
                'tong_chenh_lech' => $tongThucTe - $tongLyThuyet,
                'tong_lech_tang' => $tongLechTang,
                'tong_lech_giam' => $tongLechGiam,
            ]);
        }
    }
}
