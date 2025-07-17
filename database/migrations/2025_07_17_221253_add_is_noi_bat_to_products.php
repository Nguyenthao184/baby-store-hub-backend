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
        Schema::table('SanPham', function (Blueprint $table) {
            $table->integer('is_noi_bat')->default(0)->comment('Nổi bật: 0 - sẽ về trạng thái bình thường, 1 - Sản phẩm nổi bật');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            //
        });
    }
};
