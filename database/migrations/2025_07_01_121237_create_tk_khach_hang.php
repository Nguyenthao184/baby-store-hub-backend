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
       Schema::create('KhachHang', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('hoTen', 255);
            $table->string('sdt', 10)->nullable();
            $table->string('email', 255)->nullable();
            $table->string('diaChi', 255)->nullable();
            $table->date('ngaySinh')->nullable();
            $table->string('avatar', 255)->nullable();
            $table->unsignedBigInteger('taiKhoan_id')->nullable();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('KhachHang');
    }
};
