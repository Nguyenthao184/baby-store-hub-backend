<?php

namespace App\Services;

use App\Models\SanPham;
use App\Repositories\GioHangRepository;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class GioHangService
{
    public function __construct(private GioHangRepository $repo) {}

    private function mapSanPham(SanPham $sp, int $soLuong): array
    {
        $vat      = (float) ($sp->VAT ?? 0);
        $giaGoc   = (float) ($sp->giaBan ?? 0);
        $giaKM    = $this->giaHienHanh($sp);                 // đã xét is_noi_bat + giamGiaNoiBat
        $giaSauVAT = round($giaKM * (1 + $vat / 100), 2);

        return [
            'id'            => $sp->id,
            'ten'           => $sp->tenSanPham,
            'hinhAnh'       => $sp->hinhAnh,
            'moTa'          => $sp->moTa,

            // gửi cả giá gốc & giá đã KM để FE hiển thị
            'gia_goc'       => round($giaGoc, 2),
            'gia'           => round($giaKM, 2),
            'VAT'           => round($vat, 2),
            'giaSauVAT'     => $giaSauVAT,

            'soLuong'       => $soLuong,
            'tonKho'        => (int)($sp->soLuongTon ?? 0),

            'noiBat'        => (bool)($sp->is_noi_bat ?? false),
            'flash_sale'    => round((float)($sp->flash_sale ?? 0), 2), // 0.05 = 5%
            // 'giamGia' item-level khác (nếu bạn có), hiện để null:
            'giamGia'       => null,
        ];
    }

    private function kiemTraTon(SanPham $sp, int $soLuong): void
    {
        $ton = (int)($sp->soLuongTon ?? 0);
        if ($soLuong < 1 || $soLuong > $ton) {
            throw ValidationException::withMessages([
                'so_luong' => "Số lượng không hợp lệ (tối đa {$ton})."
            ]);
        }
    }

    private function giaHienHanh(SanPham $sp): float
    {
        $gia = (float) ($sp->giaBan ?? 0);
        $rate = (float) ($sp->giamGiaNoiBat ?? 0);

        // CHỈ giảm giá nếu KHÔNG nổi bật
        if (!($sp->is_noi_bat ?? false) && $rate > 0) {
            $gia = $gia * (1 - $rate);
        }
        return round($gia, 2);
    }


    private function tinhGiaCuoi(float $giaHienHanh, float $vat, ?float $giamGia, bool $noiBat): float
    {
        // $giaHienHanh đã là giá sau khi áp giảm nổi bật (nếu có)
        $giaCoVAT = $giaHienHanh * (1 + $vat / 100);

        // Nếu còn chính sách giảm riêng per-item (không phải nổi bật) thì áp tiếp:
        if (!$noiBat && $giamGia) {
            // $giamGia cũng là tỷ lệ (vd 0.10 = 10%)
            $giaCoVAT *= (1 - $giamGia);
        }
        return round($giaCoVAT, 2);
    }

    public function layGio(int|string $nguoiDungId): array
    {
        $ds = array_values($this->repo->tatCa($nguoiDungId));
        $tamTinh = 0.0;

        foreach ($ds as &$sp) {
            $giaCuoi = $this->tinhGiaCuoi(
                (float)$sp['gia'],                  // đã là giá sau KM nổi bật
                (float)($sp['VAT'] ?? 0),
                $sp['giamGia'] ?? null,
                (bool)($sp['noiBat'] ?? false)
            );
            $sp['gia_cuoi']   = $giaCuoi;
            $sp['thanh_tien'] = round($giaCuoi * (int)$sp['soLuong'], 2);
            $tamTinh         += $sp['thanh_tien'];
        }
        unset($sp);

        return [
            'san_pham' => $ds,
            'tam_tinh' => round($tamTinh, 2),
        ];
       Log::info('GIO HANG DEBUG', ['userId' => $nguoiDungId, 'gio' => $result]);
    }


    public function them(int|string $nguoiDungId, string $sanPhamId, int $soLuong = 1): array

    {
        $sp = SanPham::findOrFail($sanPhamId);
        $this->kiemTraTon($sp, $soLuong);

        $hienTai = $this->repo->lay($nguoiDungId, $sanPhamId);
        $tong = $soLuong + (int)Arr::get($hienTai, 'soLuong', 0);
        $this->kiemTraTon($sp, $tong);

        $item = $this->mapSanPham($sp, $tong);
        $this->repo->luu($nguoiDungId, $sanPhamId, $item);

        return $this->layGio($nguoiDungId);
    }

    public function capNhat(int|string $nguoiDungId, string $sanPhamId, int $soLuong): array
    {
        $sp = SanPham::findOrFail($sanPhamId);
        $this->kiemTraTon($sp, $soLuong);

        $item = $this->mapSanPham($sp, $soLuong);
        $this->repo->luu($nguoiDungId, $sanPhamId, $item);

        return $this->layGio($nguoiDungId);
    }

    public function xoa(int|string $nguoiDungId, string $sanPhamId): array
    {
        $this->repo->xoa($nguoiDungId, $sanPhamId);
        return $this->layGio($nguoiDungId);
    }

    public function xoaHet(int|string $nguoiDungId): array
    {
        $this->repo->xoaHet($nguoiDungId);
        return ['san_pham' => [], 'tam_tinh' => 0];
    }
}
