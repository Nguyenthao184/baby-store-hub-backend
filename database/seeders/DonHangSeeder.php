<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Models\KhachHang;

class DonHangSeeder extends Seeder
{
    public function run(): void
    {
        $khachHangs = KhachHang::all();
        if ($khachHangs->isEmpty()) {
            return;
        }

        // Xoá dữ liệu cũ
        DB::table('donhang')->delete();

        $records = [];

        foreach ($khachHangs as $kh) {
            // Random 1–3 đơn cho mỗi KH
            $soDonHang = rand(1, 3);

            for ($i = 0; $i < $soDonHang; $i++) {
                $giamVoucher    = rand(0, 50_000);
                $giamDiem       = rand(0, 20_000);
                $isOffline      = (bool) rand(0, 1); // true: offline, false: online

                if ($isOffline) {
                    // ================= OFFLINE =================
                    $phiVanChuyen   = 0;

                    // Chỉ 3 phương thức offline: cod/bank/card (map sang enum DB)
                    $offlineMethod  = ['cod', 'bank', 'card'][rand(0, 2)];
                    $mapOffline     = [
                        'cod'  => 'cash',
                        'bank' => 'bank_transfer',
                        'card' => 'credit_card',
                    ];

                    $records[] = [
                        'id'                     => (string) Str::uuid(),
                        'ma_don_hang'            => 'DH-' . now()->year . '-' . str_pad(rand(1, 999999), 6, '0', STR_PAD_LEFT),
                        'khach_hang_id'          => $kh->id,
                        'ten_nguoi_nhan'         => $kh->hoTen,
                        'so_dien_thoai'          => $kh->sdt,
                        'dia_chi'                => null,
                        'ghi_chu'                => 'Thanh toán tại quầy',
                        'tam_tinh'               => 0,
                        'giam_voucher'           => $giamVoucher,
                        'giam_diem'              => $giamDiem,
                        'phi_van_chuyen'         => $phiVanChuyen,   // 0
                        'tong_thanh_toan'        => 0,
                        'voucher_id'             => null,
                        'don_vi_van_chuyen'      => null,            // offline: không ship
                        'ma_van_don'             => null,            // offline: không ship
                        'trang_thai'             => 'DA_THANH_TOAN', // đã thu tiền tại quầy
                        'phuong_thuc_thanh_toan' => $mapOffline[$offlineMethod],
                        'ngay_tao'               => now(),
                        'ngay_cap_nhat'          => now(),
                    ];
                } else {
                    // ================= ONLINE =================
                    $phiVanChuyen   = rand(20_000, 50_000);

                    // Online: cod / vnpay / momo
                    $onlineMethod   = ['cod', 'vnpay', 'momo'][rand(0, 2)];

                    $trangThai = match ($onlineMethod) {
                        'cod'           => 'CHO_XU_LY',
                        'vnpay', 'momo' => 'CHO_LAY_HANG',
                        default         => 'CHO_XU_LY',
                    };

                    // Đơn vị VC & mã vận đơn (luôn có cho ONLINE)
                    $donViVC  = ['GHN', 'GHTK'][rand(0, 1)];
                    $maVD     = $donViVC . '-' . strtoupper(Str::random(6)); // ví dụ: GHN-8F2KQZ

                    $records[] = [
                        'id'                     => (string) Str::uuid(),
                        'ma_don_hang'            => 'DH-' . now()->year . '-' . str_pad(rand(1, 999999), 6, '0', STR_PAD_LEFT),
                        'khach_hang_id'          => $kh->id,
                        'ten_nguoi_nhan'         => $kh->hoTen,
                        'so_dien_thoai'          => $kh->sdt,
                        'dia_chi'                => $kh->diaChi ?? 'Chưa có địa chỉ', // <-- luôn lấy từ KH
                        'ghi_chu'                => rand(0, 1) ? 'Giao hàng nhanh' : null,
                        'tam_tinh'               => 0,
                        'giam_voucher'           => $giamVoucher,
                        'giam_diem'              => $giamDiem,
                        'phi_van_chuyen'         => $phiVanChuyen,
                        'tong_thanh_toan'        => 0,
                        'voucher_id'             => rand(0, 1) ? (string) Str::uuid() : null,
                        'don_vi_van_chuyen'      => $donViVC,
                        'ma_van_don'             => $maVD,                // <-- luôn có mã vận đơn
                        'trang_thai'             => $trangThai,
                        'phuong_thuc_thanh_toan' => $onlineMethod,        // 'cod' | 'vnpay' | 'momo'
                        'ngay_tao'               => now(),
                        'ngay_cap_nhat'          => now(),
                    ];
                }
            }
        }

        DB::table('donhang')->insert($records);
    }
}
