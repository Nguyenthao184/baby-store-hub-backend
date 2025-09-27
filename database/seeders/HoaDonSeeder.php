<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;
use App\Models\DonHang;
use App\Models\ChiTietDonHang;
use Illuminate\Support\Facades\Schema;

class HoaDonSeeder extends Seeder
{
    public function run(): void
    {
        // Chỉ lấy đơn ở trạng thái đủ điều kiện xuất hóa đơn
        $donHangs = DonHang::whereIn('trang_thai', ['DA_THANH_TOAN', 'DANG_GIAO_HANG'])
            ->orderByDesc('ngay_tao')
            ->take(10)
            ->get();
        $now = Carbon::now();
        $hoaDons = [];
        $startIndex = 1;

        // Xóa dữ liệu cũ trong bảng hoadon
        DB::table('hoadon')->delete();

        $hasFlashCol = Schema::hasColumn('chitietdonhang', 'flash_sale');

        foreach ($donHangs as $donHang) {
            if (! in_array($donHang->trang_thai, ['DA_THANH_TOAN', 'DANG_GIAO_HANG'], true)) {
                continue;
            }

            // Lấy chi tiết đơn hàng liên quan
            $chiTiets = ChiTietDonHang::where('don_hang_id', $donHang->id)->get();
            if ($chiTiets->isEmpty()) continue;

            // Tính toán tổng tiền hàng và VAT từ chi tiết
            $tongTienHang = $chiTiets->sum(fn($ct) => $ct->thanh_tien);
            $tongVAT = $chiTiets->sum(function ($ct) {
                $giaAfterFlash = (float) ($ct->gia ?? 0);        // giá đã VAT & đã flash
                $vat           = (float) ($ct->vat ?? 0);        // %
                $flash         = (float) ($ct->flash_sale ?? 0); // 0..1 (nếu không có cột thì đảm bảo = 0 trước đó)
                $qty           = (int)   ($ct->so_luong ?? 0);

                if ($vat <= 0 || $qty <= 0 || $giaAfterFlash <= 0) return 0;

                // Khôi phục "giá sau VAT trước flash":
                $denominator = (1 - $flash) > 0 ? (1 - $flash) : 1; // tránh chia 0
                $giaAfterVat = $giaAfterFlash / $denominator;

                // VAT ẩn trong giá (giá đã gồm VAT): price * VAT/(100+VAT)
                $vatLine = ($giaAfterVat * $vat / (100 + $vat)) * $qty;

                return round($vatLine, 2);
            });

            // ===== Tính giảm do flash sale từ snapshot =====
            $giamFlashSale = $chiTiets->sum(function ($ct) {
                $gia    = (float) ($ct->gia ?? 0);          // đã VAT & đã flash
                $flash  = (float) ($ct->flash_sale ?? 0);   // 0..1
                $qty    = (int)   ($ct->so_luong ?? 0);

                if ($gia <= 0 || $qty <= 0 || $flash <= 0 || $flash >= 1) {
                    return 0;
                }

                // Cách 1: khôi phục giá sau VAT trước flash
                $giaAfterVat   = $gia / (1 - $flash);
                $giamFlashLine = ($giaAfterVat - $gia) * $qty;

                // Nếu muốn làm tròn từng dòng:
                // $giamFlashLine = round($giamFlashLine, 2);

                return $giamFlashLine;
            });

            // Làm tròn cuối cùng cho tổng:
            $giamFlashSale = round($giamFlashSale, 2);


            // Lấy trực tiếp từ đơn hàng để khớp
            $giamVoucher = $donHang->giam_voucher;
            $giamDiem = $donHang->giam_diem;
            $phiVanChuyen = $donHang->phi_van_chuyen;
            $tongThanhToan = $donHang->tong_thanh_toan;

            // Tạo mã hóa đơn
            $maHoaDon = 'HD-' . now()->year . '-' . str_pad($startIndex++, 6, '0', STR_PAD_LEFT);

            $hoaDons[] = [
                'id' => (string) Str::uuid(),
                'ma_hoa_don' => $maHoaDon,
                'don_hang_id' => $donHang->id,
                'ngay_xuat' => $now->copy()->subDays(rand(0, 10)),
                'tong_tien_hang' => round($tongTienHang, 2),
                'tong_vat' => round($tongVAT, 2),
                'giam_flash_sale'  => $giamFlashSale,   
                'giam_voucher' => round($giamVoucher, 2),
                'giam_diem' => round($giamDiem, 2),
                'phi_van_chuyen' => round($phiVanChuyen, 2),
                'tong_thanh_toan' => round($tongThanhToan, 2),
                'phuong_thuc_thanh_toan' => $donHang->phuong_thuc_thanh_toan, // khớp với đơn hàng
            ];
        }

        // Chèn dữ liệu vào bảng hoadon
        DB::table('hoadon')->insert($hoaDons);
    }
}
