<?php

namespace Database\Seeders;

use App\Http\Controllers\PhieuNhapKhoController;
use App\Models\NhaCungCap;
use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        // User::factory()->create([
        //     'name' => 'Test User',
        //     'email' => 'test@example.com',
        // ]);

        // Seed kho data
        //$this->call(KhoSeeder::class);
        $this->call(TaiKhoanSeeder::class);
        $this->call(KhachHangSeeder::class);
        $this->call(NhaCungCapSeeder::class);
        $this->call(DanhMucSeeder::class);
        $this->call(SanPhamSeeder::class);
        $this->call(DonHangSeeder::class);
        $this->call(ChiTietDonHangSeeder::class);
        $this->call(HoaDonSeeder::class);
        $this->call(PhieuNhapKhoSeeder::class);
        $this->call(ChiTietPhieuNhapKhoSeeder::class);
        $this->call(PhieuKiemKhoSeeder::class);
    }
}
