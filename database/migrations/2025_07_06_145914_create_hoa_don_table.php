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
        Schema::create('HoaDon', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('maHoaDon', 50)->unique();
            $table->unsignedBigInteger('donHang_id');
            $table->dateTime('ngayXuat');
            $table->decimal('tongTienHang', 15, 2);
            $table->decimal('giamGiaSanPham', 15, 2);
            $table->decimal('thueVAT', 15, 2);
            $table->decimal('tongThanhToan', 15, 2);
            $table->string('phuongThucThanhToan', 50);
            
            $table->foreign('donHang_id')->references('id')->on('DonHang')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hoa_don');
    }
};
