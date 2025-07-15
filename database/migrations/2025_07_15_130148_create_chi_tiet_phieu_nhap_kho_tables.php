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
        Schema::create('chi_tiet_phieu_nhap_kho', function (Blueprint $table) {
            $table->id();
            $table->uuid('phieu_nhap_id');
            $table->uuid('san_pham_id');
            $table->integer('so_luong_nhap');
            $table->decimal('gia_nhap', 15, 2);
            $table->decimal('thue_nhap', 5, 2)->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chi_tiet_phieu_nhap_kho_tables');
    }
};
