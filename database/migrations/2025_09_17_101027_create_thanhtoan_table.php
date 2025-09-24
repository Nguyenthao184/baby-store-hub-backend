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
        Schema::create('thanhtoan', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->uuid('don_hang_id')->index();
            // Kênh thanh toán
            $table->string('kenh', 20);                         // momo|vnpay|cod
            $table->string('ma_tham_chieu', 64)->nullable();    // orderId/txnRef từ cổng
            $table->string('ma_giao_dich',64)->nullable();
            $table->decimal('so_tien', 15, 2);                  // số tiền giao dịch
            $table->string('don_vi_tien', 5)->default('VND');

            // CHO_XU_LY|DA_THANH_TOAN|THAT_BAI|HUY|HOAN_TIEN
            $table->string('trang_thai', 20)->default('CHO_XU_LY')->index();
            $table->string('ma_ket_qua', 50)->nullable();
            $table->string('thong_diep', 255)->nullable();

            $table->json('raw_return')->nullable();
            $table->timestamps();

            $table->index(['kenh', 'trang_thai']);
            $table->unique(['kenh', 'ma_tham_chieu']);
            $table->foreign('don_hang_id')->references('id')->on('donhang')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('thanhtoan');
    }
};
