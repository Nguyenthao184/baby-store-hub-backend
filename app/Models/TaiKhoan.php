<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class TaiKhoan extends Authenticatable
{
    protected $table = 'TaiKhoan';
    use HasFactory, Notifiable, HasApiTokens;
    protected $fillable = [
        'id',
        'email',
        'matKhau',
        'vaiTro',
        'trangThai',
    ];

    public $timestamps = false;
    
    public function getAuthPassword()
    {
        return $this->matKhau;
    }

}
