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
          Schema::create('phieu_kiem_kho', function (Blueprint $table) {
            $table->id();
            $table->string('ma_phieu_kiem')->unique();
            $table->dateTime('ngay_kiem');
            $table->date('ngay_can_bang')->nullable();

            $table->integer('tong_so_luong_thuc_te')->default(0);
            $table->integer('tong_so_luong_ly_thuyet')->default(0);
            $table->integer('tong_chenh_lech')->default(0);
            $table->integer('tong_lech_tang')->default(0);
            $table->integer('tong_lech_giam')->default(0);

            $table->enum('trang_thai', ['phieu_tam','da_can_bang','da_huy'])->default('phieu_tam');
            $table->bigInteger('nguoi_tao_id')->nullable();
            $table->text('ghi_chu')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('phieu_kiem_kho');
    }
};
