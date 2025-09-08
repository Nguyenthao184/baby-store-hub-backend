<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KhachHang extends Model
{
protected $table = 'KhachHang';

    protected $fillable = [
        'id',
        'hoTen',
        'sdt',
        'email',
        'diaChi',
        'ngaySinh',
        'avatar',
        'taiKhoan_id'
    ];

    public $timestamps = false;
}
