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
        Schema::create('Kho', function (Blueprint $table) {
            $table->string('id', 36)->primary();
            $table->string('tenKho', 255);
            $table->text('diaChi')->nullable();
            $table->text('moTa')->nullable();
            $table->boolean('trangThai')->default(true);
            $table->integer('soLuongSanPham')->default(0);
            $table->string('nguoiQuanLy', 255)->nullable();
            $table->decimal('dienTich', 8, 2)->nullable();
            $table->datetime('ngayTao');
            $table->datetime('ngayCapNhat')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('Kho');
    }
};
