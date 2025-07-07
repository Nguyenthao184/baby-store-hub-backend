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
        'tenSanPham',
        'maSKU',
        'VAT',
        'giaBan',
        'soLuongTon',
        'moTa',
        'danhMuc_id',
        'kho_id',
        'hinhAnh',
        'ngayTao',
        'ngayCapNhat',
    ];

    protected $casts = [
        'VAT' => 'decimal:2',
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
     * Relationship with DanhMuc
     */
    public function danhMuc()
    {
        return $this->belongsTo(DanhMuc::class, 'danhMuc_id', 'id');
    }

    /**
     * Relationship with Kho
     */
    public function kho()
    {
        return $this->belongsTo(Kho::class, 'kho_id', 'id');
    }
}
