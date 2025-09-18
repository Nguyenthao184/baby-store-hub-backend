<?php
// app/Models/ThanhToan.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ThanhToan extends Model
{
    protected $table = 'thanhtoan';

    protected $fillable = [
        'don_hang_id','kenh','so_tien','trang_thai','ma_giao_dich',
        'ma_ket_qua','thong_diep','ma_tham_chieu','raw_return'
    ];

    protected $casts = [
        'raw_return' => 'array',
        'so_tien' => 'decimal:2',
    ];

    public function donHang() {
        return $this->belongsTo(DonHang::class, 'don_hang_id');
    }
}
