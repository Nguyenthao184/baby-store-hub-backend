<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DonHang extends Model
{
    use HasFactory, SoftDeletes;
    protected $table = 'donhang';
    public $incrementing = false;
    public $timestamps = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'ma_don_hang',
        'khach_hang_id',
        'ten_nguoi_nhan',
        'so_dien_thoai',
        'dia_chi',
        'ghi_chu',
        'tam_tinh',
        'giam_voucher',
        'giam_diem',
        'phi_van_chuyen',
        'tong_thanh_toan',
        'voucher_id',
        'don_vi_van_chuyen',
        'ma_van_don',
        'trang_thai',
        'phuong_thuc_thanh_toan',
        'ngay_tao',
        'ngay_cap_nhat',
    ];

    protected $casts = [
        'tam_tinh' => 'decimal:2',
        'giam_voucher' => 'decimal:2',
        'giam_diem' => 'decimal:2',
        'phi_van_chuyen' => 'decimal:2',
        'tong_thanh_toan' => 'decimal:2',
        'ngay_tao' => 'datetime',
        'ngay_cap_nhat' => 'datetime'
    ];

    public function khachHang()
    {
        return $this->belongsTo(KhachHang::class, 'khach_hang_id');
    }

    public function hoaDon()
    {
        return $this->hasOne(HoaDon::class, 'don_hang_id', 'id');
    }

    public function chiTietDonHang()
    {
        return $this->hasMany(ChiTietDonHang::class, 'don_hang_id', 'id');
    }
}
