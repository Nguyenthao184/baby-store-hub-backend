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
        Schema::create('chitietdonhang', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('donhang_id');
            $table->string('sanpham_id', 36); 

            $table->integer('soLuong');
            $table->decimal('giaBan', 15, 2);
            $table->decimal('giamGia', 15, 2)->default(0);
            $table->decimal('tongTien', 15, 2);

            $table->foreign('donhang_id')->references('id')->on('donhang')->onDelete('cascade');
            $table->foreign('sanpham_id')->references('id')->on('SanPham')->onDelete('cascade'); 
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chitietdonhang');
    }
};
