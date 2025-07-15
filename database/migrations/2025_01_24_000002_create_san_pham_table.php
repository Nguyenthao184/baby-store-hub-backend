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
        Schema::create('SanPham', function (Blueprint $table) {
            $table->string('id', 36)->primary();
            $table->string('maSanPham', 20)->unique();
            $table->string('tenSanPham', 255);
            $table->string('maSKU', 100)->unique();
            $table->decimal('VAT', 5, 2)->default(0.00);
            $table->decimal('giaBan', 15, 2)->default(0.00);
            $table->integer('soLuongTon')->default(0);
            $table->text('moTa')->nullable();
            $table->string('danhMuc_id', 36);
            //$table->string('kho_id', 36)->nullable();
            $table->string('hinhAnh')->nullable();
            $table->json('thongSoKyThuat')->nullable();
            $table->datetime('ngayTao');
            $table->datetime('ngayCapNhat')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('SanPham');
    }
};
