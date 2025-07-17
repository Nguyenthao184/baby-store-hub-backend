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
        Schema::create('DanhMuc', function (Blueprint $table) {
            $table->string('id', 36)->primary();
            $table->string('maDanhMuc', 20)->unique();
            $table->string('tenDanhMuc', 255);
            $table->text('moTa')->nullable();
            $table->integer('soLuongSanPham')->default(0);
            $table->string('hinhAnh')->nullable();
            $table->unsignedBigInteger('nhaCungCap')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('DanhMuc');
    }
};
