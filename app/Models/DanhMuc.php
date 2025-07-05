<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class DanhMuc extends Model
{
    protected $table = 'DanhMuc';
    protected $keyType = 'string';
    public $incrementing = false;
    public $timestamps = false;

        protected $fillable = [
        'id',
        'tenDanhMuc',
        'moTa',
        'soLuongSanPham',
        'hinhAnh',
        'nhaCungCap',
        'idKho',
    ];

    protected $casts = [
        'soLuongSanPham' => 'integer',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->id)) {
                $model->id = (string) Str::uuid();
            }
        });
    }

    /**
     * Relationship with SanPham
     */
    public function sanPhams()
    {
        return $this->hasMany(SanPham::class, 'danhMuc_id', 'id');
    }

    /**
     * Relationship with Kho
     */
    public function kho()
    {
        return $this->belongsTo(Kho::class, 'idKho', 'id');
    }
}
