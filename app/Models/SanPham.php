<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class SanPham extends Model
{
    protected $table = 'SanPham';
    protected $keyType = 'string';
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = [
        'id',
        'maSanPham',
        'tenSanPham',
        'maSKU',
        'VAT',
        'giaBan',
        'soLuongTon',
        'moTa',
        'danhMuc_id',
        //'kho_id',
        'hinhAnh',
        'ngayTao',
        'ngayCapNhat',
        'is_noi_bat',
    ];

    protected $casts = [
        'VAT' => 'decimal:2',
        'ngayTao' => 'datetime',
        'ngayCapNhat' => 'datetime',
        'thongSoKyThuat' => 'array',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->id)) {
                $model->id = (string) Str::uuid();
            }
            if (empty($model->ngayTao)) {
                $model->ngayTao = now();
            }
            if (empty($model->maSanPham)) {
                $latest = self::orderBy('maSanPham', 'desc')->first();
                $nextNumber = $latest ? ((int)substr($latest->maSanPham, 2)) + 1 : 1;
                $model->maSanPham = 'SP' . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
            }
        });

        static::updating(function ($model) {
            $model->ngayCapNhat = now();
        });
    }

    /**
     * Relationship with DanhMuc
     */
    public function danhMuc()
    {
        return $this->belongsTo(DanhMuc::class, 'danhMuc_id', 'id');
    }
    public function chiTietPhieuKiemKho()
    {
        return $this->hasMany(ChiTietPhieuKiemKho::class, 'san_pham_id');
    }
}
