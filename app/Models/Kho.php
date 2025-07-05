<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Kho extends Model
{
    protected $table = 'Kho';
    protected $keyType = 'string';
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = [
        'id',
        'tenKho',
        'diaChi',
        'moTa',
        'trangThai',
        'soLuongSanPham',
        'nguoiQuanLy',
        'dienTich',
        'ngayTao',
        'ngayCapNhat',
    ];

    protected $casts = [
        'trangThai' => 'boolean',
        'soLuongSanPham' => 'integer',
        'dienTich' => 'decimal:2',
        'ngayTao' => 'datetime',
        'ngayCapNhat' => 'datetime',
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
        });

        static::updating(function ($model) {
            $model->ngayCapNhat = now();
        });
    }

    /**
     * Relationship with SanPham
     */
    public function sanPhams()
    {
        return $this->hasMany(SanPham::class, 'kho_id', 'id');
    }

    /**
     * Relationship with DanhMuc
     */
    public function danhMucs()
    {
        return $this->hasMany(DanhMuc::class, 'idKho', 'id');
    }
}
