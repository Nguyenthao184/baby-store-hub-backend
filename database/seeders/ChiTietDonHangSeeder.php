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
            // Lấy ngẫu nhiên 1..3 sản phẩm
            $countPick = min(max(1, rand(1, 3)), $sanPhams->count());
            $sanPhamsRandom = $sanPhams->random($countPick);
            if ($countPick === 1 && !$sanPhamsRandom instanceof \Illuminate\Support\Collection) {
                $sanPhamsRandom = collect([$sanPhamsRandom]);
            }

            foreach ($sanPhamsRandom as $sp) {
                $soLuong = rand(1, 5);
                $giaGoc  = (float) $sp->giaBan;
                $vat     = 8.00; // <<== VAT cố định 8%
                $giam_gia = rand(0, 20000);

                // Giá sau VAT
                $giaSauVat = round($giaGoc * (1 + $vat / 100), 2);


                // Đơn giá sau khi trừ giảm giá (không âm)
                $donGiaSauGiam = max(0, round($giaSauVat - $giam_gia, 2));

                // Thành tiền = đơn giá sau giảm * số lượng
                $thanhTien = round($donGiaSauGiam * $soLuong, 2);

                $records[] = [
                    'id'           => (string) Str::uuid(),
                    'don_hang_id'  => $donHang->id,
                    'san_pham_id'  => $sp->id,
                    'ten_san_pham' => $sp->tenSanPham,
                    'gia'          => $giaSauVat,   
                    'vat'          => $vat,                // VAT cố định
                    'giam_gia'     => $giam_gia,
                    'so_luong'     => $soLuong,
                    'thanh_tien'   => $thanhTien,    // thành tiền đã VAT
                ];
            }
        }

        DB::table('chitietdonhang')->insert($records);

        // Lấy tổng thành_tien theo don_hang_id
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
            $tongThanhToan = max(0, $tongThanhToan); // không âm

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
