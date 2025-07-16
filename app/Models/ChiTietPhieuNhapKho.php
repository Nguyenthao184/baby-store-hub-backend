<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChiTietPhieuNhapKho extends Model
{
    use HasFactory;

    protected $table = 'chi_tiet_phieu_nhap_kho';

    protected $fillable = [
        'phieu_nhap_id',
        'san_pham_id',
        'so_luong_nhap',
        'gia_nhap',
        'thue_nhap'
    ];

    public function phieuNhap()
    {
        return $this->belongsTo(PhieuNhapKho::class, 'phieu_nhap_id');
    }

    public function sanPham()
    {
        return $this->belongsTo(SanPham::class, 'san_pham_id');
    }
}
