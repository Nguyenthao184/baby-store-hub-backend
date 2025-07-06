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
        Schema::create('cuahang', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('tenCuaHang', 255);           
            $table->string('diaChi', 255)->nullable();
            $table->string('sdt', 10)->nullable();
            $table->string('email', 255)->nullable();
            $table->date('ngayTao')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cuahang');
    }
};
