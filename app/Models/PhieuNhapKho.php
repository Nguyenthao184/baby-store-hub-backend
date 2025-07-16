<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PhieuNhapKho extends Model
{
    use HasFactory;
    protected $table = 'phieu_nhap_kho';

    protected $fillable = [
        'so_phieu',
        'ngay_nhap',
        'nha_cung_cap_id',
        'tong_tien_nhap',
        'ghi_chu',
        'trang_thai'
    ];

    public function chiTiet()
    {
        return $this->hasMany(ChiTietPhieuNhapKho::class, 'phieu_nhap_id');
    }

    public function nhaCungCap()
    {
        return $this->belongsTo(NhaCungCap::class, 'nha_cung_cap_id');
    }
}
