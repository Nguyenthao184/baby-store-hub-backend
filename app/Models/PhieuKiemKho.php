<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PhieuKiemKho extends Model
{
    protected $table = 'phieu_kiem_kho';

    protected $fillable = [
        'ma_phieu_kiem',
        'ngay_kiem',
        'ngay_can_bang',
        'tong_so_luong_thuc_te',
        'tong_so_luong_ly_thuyet',
        'tong_chenh_lech',
        'tong_lech_tang',
        'tong_lech_giam',
        'trang_thai',
        'nguoi_tao_id',
        'ghi_chu',
    ];

    public function chiTiet()
    {
        return $this->hasMany(ChiTietPhieuKiemKho::class, 'phieu_kiem_id');
    }
    public function sanPham()
    {
        return $this->belongsTo(SanPham::class, 'san_pham_id');
    }

}

