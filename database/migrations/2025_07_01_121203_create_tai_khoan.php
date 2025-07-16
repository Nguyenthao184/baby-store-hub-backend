<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
         Schema::create('TaiKhoan', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('email', 100)->unique();
            $table->string('matKhau', 255);
            $table->enum('vaiTro', ['Admin', 'QuanLyCuaHang', 'NhanVien', 'KhachHang']);
            $table->boolean('trangThai')->default(true);
           
        });
    }

   
    public function down(): void
    {
        Schema::dropIfExists('tai_khoan');
    }
};
