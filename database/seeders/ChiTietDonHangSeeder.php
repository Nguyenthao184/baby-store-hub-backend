<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Schema; 
use App\Models\SanPham;
use App\Models\DonHang;

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
        $hasFlashSaleCol = Schema::hasColumn('chitietdonhang', 'flash_sale');

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

                // 1) VAT trước: giá sau VAT
                $vat = 8.00; 
                $giaSauVat = round($giaGoc * (1 + $vat / 100), 2);

                // 2) Flash sale sau VAT
                $flashSale = 0.0; // mặc định 0%
                if (isset($sp->flash_sale) && is_numeric($sp->flash_sale)) {
                    $flashSale = (float) $sp->flash_sale; // kỳ vọng 0..1
                }
                $flashSale = max(0.0, min(0.9, $flashSale)); // clamp 0..90%
                $giaSauFlash = round($giaSauVat * (1 - $flashSale), 2);

                // 3) Giảm giá dòng (voucher theo dòng) 0..20k
                $giam_gia = rand(0, 20000);

                // 4) Đơn giá sau giảm (không âm) — TRÊN GIÁ SAU FLASH
                $donGiaSauGiam = max(0, round($giaSauFlash - $giam_gia, 2));

                // 5) Thành tiền = đơn giá sau giảm * số lượng
                $thanhTien = round($donGiaSauGiam * $soLuong, 2);

                // Lưu snapshot
                $row = [
                    'id'           => (string) Str::uuid(),
                    'don_hang_id'  => $donHang->id,
                    'san_pham_id'  => $sp->id,
                    'ten_san_pham' => $sp->tenSanPham,
                    'gia'          => $giaSauFlash,   // ✅ đơn giá sau VAT & flash (trước giảm)
                    'vat'          => $vat,           // % VAT đã áp dụng trước đó
                    'giam_gia'     => $giam_gia,      // giảm thêm theo dòng (VND)
                    'so_luong'     => $soLuong,
                    'thanh_tien'   => $thanhTien,     // đã VAT + flash + trừ giảm, nhân SL
                ];

                if ($hasFlashSaleCol) {
                    $row['flash_sale'] = $flashSale;  // 0..1
                }

                $records[] = $row;
            }
        }

        DB::table('chitietdonhang')->insert($records);

        // Tổng hợp lại DonHang: tam_tinh & tong_thanh_toan
        $tongTheoDon = DB::table('chitietdonhang')
            ->select('don_hang_id', DB::raw('SUM(thanh_tien) as tam_tinh'))
            ->groupBy('don_hang_id')
            ->pluck('tam_tinh', 'don_hang_id');

        foreach ($tongTheoDon as $donHangId => $tamTinh) {
            $row = DB::table('donhang')->where('id', $donHangId)->first();
            if (!$row) continue;

            $giamVoucher   = (float) ($row->giam_voucher ?? 0);
            $giamDiem      = (float) ($row->giam_diem ?? 0);
            $phiVanChuyen  = (float) ($row->phi_van_chuyen ?? 0);

            $tongThanhToan = round($tamTinh - $giamVoucher - $giamDiem + $phiVanChuyen, 2);
            $tongThanhToan = max(0, $tongThanhToan);

            DB::table('donhang')
                ->where('id', $donHangId)
                ->update([
                    'tam_tinh'        => round($tamTinh, 2),
                    'tong_thanh_toan' => $tongThanhToan,
                    'ngay_cap_nhat'   => now(),
                ]);
        }
    }
}
