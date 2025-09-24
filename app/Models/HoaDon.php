<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HoaDon extends Model
{
    use HasFactory;

    protected $table = 'hoadon';
    protected $guarded = [];
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'ma_hoa_don',
        'don_hang_id',
        'ngay_xuat',
        'tong_tien_hang',
        'tong_vat',
        'giam_voucher',
        'giam_diem',
        'phi_van_chuyen',
        'tong_thanh_toan',
        'phuong_thuc_thanh_toan',
    ];

    protected $casts = [
        'tong_tien_hang' => 'decimal:2',
        'tong_vat' => 'decimal:2',
        'giam_voucher' => 'decimal:2',
        'giam_diem' => 'decimal:2',
        'phi_van_chuyen' => 'decimal:2',
        'tong_thanh_toan' => 'decimal:2',
        'ngay_xuat' => 'datetime',
    ];

    /**
     * Quan hệ với model DonHang.
     */
    public function donHang()
    {
        return $this->belongsTo(DonHang::class, 'don_hang_id');
    }

    public static function genSoHoaDon(): string
    {
        $today = now()->toDateString(); // YYYY-MM-DD

        // Đếm theo ngày với lock, gọi trong transaction để chắc chắn
        $seq = self::whereDate('ngay_xuat', $today)
            ->lockForUpdate()
            ->count() + 1;

        return 'HD-' . now()->format('Y') . '-' . now()->format('Ymd') . '-' . str_pad((string)$seq, 6, '0', STR_PAD_LEFT);
    }
}
