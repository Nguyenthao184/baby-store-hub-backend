<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('donhang', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('maDonHang', 100);
            $table->unsignedBigInteger('khachHang_id');
            $table->enum('trangThai', ['draft', 'awaiting_payment', 'paid', 'shipping', 'completed', 'cancelled'])->nullable();
            $table->unsignedBigInteger('voucher_id')->nullable();
            $table->dateTime('ngayTao')->nullable();
            $table->dateTime('ngayCapNhat')->nullable();
            $table->text('ghiChu')->nullable();
            $table->unsignedBigInteger('donViVanChuyen_id')->nullable();
          
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('donhang');
    }
};
