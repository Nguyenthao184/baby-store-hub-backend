<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChiTietPhieuKiemKho extends Model
{
    protected $table = 'chi_tiet_phieu_kiem_kho';

    protected $fillable = [
        'phieu_kiem_id',
        'san_pham_id',
        'so_luong_ly_thuyet',
        'so_luong_thuc_te',
        'so_chenh_lech',
    ];

    public function phieu()
    {
        return $this->belongsTo(PhieuKiemKho::class, 'phieu_kiem_id');
    }

   
}
