<?php

namespace App\Services;

use App\Models\SanPham;
use App\Repositories\GioHangRepository;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;

class GioHangService
{
    public function __construct(private GioHangRepository $repo) {}

    private function mapSanPham(SanPham $sp, int $soLuong): array
    {
        $vatPercent = (float)($sp->VAT ?? 0);
        $giaVnd     = (float)($sp->giaBan ?? 0);
        $giaSauVAT  = round($giaVnd * (1 + $vatPercent / 100), 2);
        return [
            'id'        => $sp->id,
            'ten'       => $sp->tenSanPham,
            'hinhAnh'   => $sp->hinhAnh,
            'moTa'      => $sp->moTa,
            'gia'       => round($giaVnd, 2),
            'VAT'       => round($vatPercent, 2),
            'giaSauVAT' => $giaSauVAT,
            'soLuong'   => $soLuong,
            'tonKho'    => (int)($sp->soLuongTon ?? 0),
            'noiBat'    => (bool)($sp->is_noi_bat ?? false),
            'giamGia'   => null,
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

    private function tinhGiaCuoiVND(float $giaBanVnd, float $vat, ?float $giamGia, bool $noiBat): float
    {
        // cộng VAT
        $giaCoVAT = $giaBanVnd * (1 + $vat / 100);

        // giảm giá
        if (!$noiBat && $giamGia) {
            $giaCoVAT *= (1 - $giamGia);
        }

        // giữ 2 chữ số thập phân
        return round($giaCoVAT, 2);
    }

    public function layGio(int|string $nguoiDungId): array
    {
        $ds = array_values($this->repo->tatCa($nguoiDungId));

        $tamTinh = 0;
        foreach ($ds as &$sp) {
            $giaCuoi = $this->tinhGiaCuoiVND(
                (float)$sp['gia'],
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
            'tam_tinh' => round($tamTinh, 2)   // tổng cũng 2 số thập phân
        ];
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
