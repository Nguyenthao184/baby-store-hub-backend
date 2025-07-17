<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NhaCungCap extends Model
{
    protected $table = 'NhaCungCap';

    public $timestamps = false;

    protected $fillable = [
        'tenNhaCungCap',
        'maSoThue',
        'sdt',
        'email',
        'diaChi',
        'trangThai',
        'ngayTao',
        'ngayCapNhat',
    ];
    
    public function danhMucs()
    {
        return $this->hasMany(DanhMuc::class, 'nhaCungCap', 'id');
    }

}
