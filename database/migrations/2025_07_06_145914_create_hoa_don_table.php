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
        Schema::create('hoadon', function (Blueprint $table) {
            $table->uuid('id')->primary();            // mã hoá đơn (UUID)
            $table->string('ma_hoa_don', 50)->unique();
            $table->uuid('don_hang_id')->index();

            $table->dateTime('ngay_xuat');
            $table->decimal('tong_tien_hang', 15, 2);
            $table->decimal('tong_vat', 15, 2)->default(0);
            $table->decimal('giam_flash_sale', 15, 2)->default(0);
            $table->decimal('giam_voucher', 15, 2)->default(0);
            $table->decimal('giam_diem', 15, 2)->default(0);
            $table->decimal('phi_van_chuyen', 15, 2)->default(0);
            $table->decimal('tong_thanh_toan', 15, 2);

            $table->string('phuong_thuc_thanh_toan', 20);   // cod|momo|vnpay
            $table->timestamps();
            $table->unique('don_hang_id');
            $table->foreign('don_hang_id')->references('id')->on('donhang')->cascadeOnDelete();
            
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hoadon');
    }
};
