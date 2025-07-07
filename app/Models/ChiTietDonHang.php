<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Relations\BelongsTo;


class ChiTietDonHang extends Model
{
    protected $table = 'chitietdonhang';

    public $timestamps = false;

    protected $fillable = [
        'id',
        'donHang_id',
        'sanpham_id',
        'soLuong',
        'giaBan',
        'giamGia',
        'tongTien',
    ];

    public function donHang(): BelongsTo
    {
        return $this->belongsTo(DonHang::class, 'donHang_id');
    }

    public function sanPham(): BelongsTo
    {
        return $this->belongsTo(SanPham::class, 'sanpham_id');
    }
}
