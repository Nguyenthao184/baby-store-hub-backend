<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DonHang extends Model
{
    protected $table = 'donhang';
    public $timestamps = false;

    protected $fillable = [
        'maDonHang',
        'khachHang_id',
        'trangThai',
        'voucher_id',
        'cuaHang_id',
        'ngayTao',
        'ngayCapNhat',
        'ghiChu',
        'donViVanChuyen_id'
    ];
    
    public function khachHang(): BelongsTo
    {
        return $this->belongsTo(KhachHang::class, 'khachHang_id');
    }

    public function voucher(): BelongsTo
    {
        return $this->belongsTo(Voucher::class, 'voucher_id');
    }

    public function cuaHang(): BelongsTo
    {
        return $this->belongsTo(CuaHang::class, 'cuaHang_id');
    }

    public function donViVanChuyen(): BelongsTo
    {
        return $this->belongsTo(DonViVanChuyen::class, 'donViVanChuyen_id');
    }

    public function chiTietDonHang(): HasMany
    {
        return $this->hasMany(ChiTietDonHang::class, 'donHang_id');
    }
}
