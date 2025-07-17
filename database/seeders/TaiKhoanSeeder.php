<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class TaiKhoanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('TaiKhoan')->insert([
            [
                'email' => 'admin@example.com',
                'matKhau' => Hash::make('password'),
                'vaiTro' => 'Admin',
                'trangThai' => true
            ],
            [
                'email' => 'quanly@example.com',
                'matKhau' => Hash::make('password'),
                'vaiTro' => 'QuanLyCuaHang',
                'trangThai' => true
            ],
            [
                'email' => 'nhanvien@example.com',
                'matKhau' => Hash::make('password'),
                'vaiTro' => 'NhanVien',
                'trangThai' => true
            ],
            [
                'email' => 'khach@example.com',
                'matKhau' => Hash::make('password'),
                'vaiTro' => 'KhachHang',
                'trangThai' => true
            ],
            [
                'email' => 'khach2@example.com',
                'matKhau' => Hash::make('password'),
                'vaiTro' => 'KhachHang',
                'trangThai' => true
            ],
            [
                'email' => 'khach3@example.com',
                'matKhau' => Hash::make('password'),
                'vaiTro' => 'KhachHang',
                'trangThai' => true
            ]

        ]);
    }
}
