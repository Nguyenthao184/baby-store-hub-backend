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
        'maDanhMuc',
        'tenDanhMuc',
        'moTa',
        'soLuongSanPham',
        'hinhAnh',
        'nhaCungCap',
        //'idKho',
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
            if (empty($model->maDanhMuc)) {
            $latest = self::orderBy('maDanhMuc', 'desc')->first();
            $nextNumber = $latest ? ((int)substr($latest->maDanhMuc, 2)) + 1 : 1;
            $model->maDanhMuc = 'DM' . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
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
    public function nhaCungCap()
    {
        return $this->belongsTo(NhaCungCap::class, 'nhaCungCap', 'id');
    }
    /**
     * Relationship with Kho
     */
    // public function kho()
    // {
    //     return $this->belongsTo(Kho::class, 'idKho', 'id');
    // }
}
