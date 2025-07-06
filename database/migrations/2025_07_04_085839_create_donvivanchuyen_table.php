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
        Schema::create('donvivanchuyen', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('tenDonVi', 255);
            $table->string('sdt', 10)->nullable();
            $table->string('diaChi', 255)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('donvivanchuyen');
    }
};
