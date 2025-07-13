<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HoaDon extends Model
{
    protected $table = 'HoaDon';
    public $timestamps = false;
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'maHoaDon',
        'donHang_id',
        'ngayXuat',
        'tongTienHang',
        'giamGiaSanPham',
        'thueVAT',
        'tongThanhToan',
        'phuongThucThanhToan',
    ];

    public function donHang()
    {
        return $this->belongsTo(DonHang::class, 'donHang_id');
    }
}
