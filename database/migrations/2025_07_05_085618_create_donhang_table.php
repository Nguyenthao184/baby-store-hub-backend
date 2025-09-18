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
            $table->uuid('id')->primary();
            $table->string('ma_don_hang', 40)->unique();                 // ví dụ: DH-2025-000123
            $table->uuid('khach_hang_id')->index();

            // Địa chỉ/nhận hàng (snapshot)
            $table->string('ten_nguoi_nhan', 150);
            $table->string('so_dien_thoai', 20);
            $table->string('dia_chi', 255)->nullable();
            $table->string('ghi_chu', 255)->nullable();

            // Tổng tiền (snapshot giỏ)
            $table->decimal('tam_tinh', 15, 2);          // tổng tiền hàng trước ưu đãi
            $table->decimal('giam_voucher', 15, 2)->default(0);
            $table->decimal('giam_diem', 15, 2)->default(0);
            $table->decimal('phi_van_chuyen', 15, 2)->default(0);
            $table->decimal('tong_thanh_toan', 15, 2);   // = tam_tinh - giam_voucher - giam_diem + phi_van_chuyen

            // Thông tin voucher/ship (tuỳ chọn)
            $table->uuid('voucher_id')->nullable();
            $table->string('don_vi_van_chuyen', 60)->nullable();
            $table->string('ma_van_don', 60)->nullable();

            // Trạng thái quy trình
            // draft|awaiting_payment|paid|confirming|packing|shipping|completed|canceled
            $table->string('trang_thai', 30)->default('awaiting_payment')->index();

            // Phương thức thanh toán user chọn lúc đặt
            // cod|momo|vnpay
            $table->string('phuong_thuc_thanh_toan', 20)->default('cod');

            $table->timestamp('ngay_tao')->useCurrent();
            $table->timestamp('ngay_cap_nhat')->nullable();
            $table->softDeletes();                 
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
