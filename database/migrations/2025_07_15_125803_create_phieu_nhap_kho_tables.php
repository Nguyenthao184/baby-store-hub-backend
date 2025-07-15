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
        Schema::create('phieu_nhap_kho', function (Blueprint $table) {
            $table->id();
            $table->string('so_phieu')->unique();
            $table->date('ngay_nhap');
            $table->unsignedBigInteger('nha_cung_cap_id')->nullable();
            $table->decimal('tong_tien_nhap', 15, 2)->default(0);
            $table->text('ghi_chu')->nullable();
            $table->enum('trang_thai', ['phieu_tam','da_nhap','da_huy'])->default('phieu_tam');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('phieu_nhap_kho_tables');
    }
};
