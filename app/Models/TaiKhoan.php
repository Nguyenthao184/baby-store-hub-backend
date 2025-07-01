<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TaiKhoan extends Model
{
    protected $table = 'TaiKhoan';

    protected $fillable = [
        'id',
        'email',
        'matKhau',
        'vaiTro',
        'trangThai',
    ];

    public $timestamps = false;

}
