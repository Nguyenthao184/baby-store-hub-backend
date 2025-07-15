<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

class DonHangSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = now();

        // Kiểm tra nếu đã có dữ liệu thì không làm gì
        if (DB::table('donhang')->count() === 0) {
            // Lấy id khách hàng và cửa hàng có sẵn
            $khachHangIds = DB::table('KhachHang')->pluck('id')->toArray();

            $donHangs = [];

            for ($i = 1; $i <= 10; $i++) {
                $donHangs[] = [
                    'maDonHang' => 'DH' . now()->format('YmdHis') . $i,
                    'khachHang_id' => $khachHangIds[array_rand($khachHangIds)],
                    'trangThai' => 'completed',
                    'ngayTao' => $now->copy()->subDays(rand(0, 10)),
                    'ngayCapNhat' => $now,
                    'ghiChu' => 'Đơn hàng số ' . $i,
                    'voucher_id' => null,
                    'donViVanChuyen_id' => null,
                ];
            }

            DB::table('donhang')->insert($donHangs);
        }
    }
}