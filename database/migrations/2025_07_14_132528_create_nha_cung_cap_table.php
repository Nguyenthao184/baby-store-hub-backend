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
           Schema::create('NhaCungCap', function (Blueprint $table) {
            $table->bigIncrements('id'); 
            $table->string('tenNhaCungCap', 255);
            $table->string('maSoThue', 50)->nullable();
            $table->string('sdt', 20)->nullable();
            $table->string('email', 100)->nullable();
            $table->text('diaChi')->nullable();
            $table->boolean('trangThai')->default(true);
            $table->dateTime('ngayTao');
            $table->dateTime('ngayCapNhat')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('nha_cung_cap');
    }
    
};
