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
            $table->uuid('id')->primary();
            $table->uuid('don_hang_id')->index();
            $table->string('san_pham_id', 36)->index();       // khớp với SanPham

            // Snapshot giá tại thời điểm đặt
            $table->string('ten_san_pham', 255);
            $table->decimal('gia', 15, 2);                    // giá gốc
            $table->decimal('vat', 5, 2)->default(0.00);
            $table->decimal('flash_sale', 5, 2)->default(0.00);         // % VAT lúc đặt
            $table->decimal('giam_gia', 15, 2)->default(0);   // số tiền giảm riêng item (nếu có)
            $table->unsignedInteger('so_luong');

            // Thành tiền dòng: (gia * (1 + vat/100) - giam_gia) * so_luong
            $table->decimal('thanh_tien', 15, 2);

            $table->timestamps();

            $table->foreign('don_hang_id')->references('id')->on('donhang')->cascadeOnDelete();
            $table->foreign('san_pham_id')->references('id')->on('SanPham')->cascadeOnUpdate()->restrictOnDelete();


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
