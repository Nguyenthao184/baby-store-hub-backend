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


    private function kiemTraTon(SanPham $sp, int $soLuong): void
    {
        $ton = (int)($sp->soLuongTon ?? 0);
        if ($soLuong < 1 || $soLuong > $ton) {
            throw ValidationException::withMessages([
                'so_luong' => "Số lượng không hợp lệ (tối đa {$ton})."
            ]);
        }
    }

    // private function mapSanPham(SanPham $sp, int $soLuong): array
    // {
    //     $vat         = (float) ($sp->VAT ?? 0);
    //     $giaGoc      = (float) ($sp->giaBan ?? 0);
    //     $giaSauKM    = $this->giaHienHanh($sp);          // đã áp flash_sale nếu KHÔNG nổi bật
    //     $giaSauVAT   = $this->tinhGiaCoVAT($giaSauKM, $vat);

    //     return [
    //         'id'         => $sp->id,
    //         'ten'        => $sp->tenSanPham,
    //         'hinhAnh'    => $sp->hinhAnh,
    //         'moTa'       => $sp->moTa,

    //         'gia_goc'    => round($giaGoc, 2),
    //         'gia'        => round($giaSauKM, 2),          // giá sau KM (chưa VAT)
    //         'VAT'        => round($vat, 2),
    //         'giaSauVAT'  => $giaSauVAT,                   // 1 sp sau VAT

    //         'soLuong'    => $soLuong,
    //         'tonKho'     => (int)($sp->soLuongTon ?? 0),

    //         'noiBat'     => (bool)($sp->is_noi_bat ?? false),
    //         'flash_sale' => round((float)($sp->flash_sale ?? 0), 2),

    //         // KHÔNG dùng giamGia per-item ở đây để tránh áp 2 lần
    //         'giamGia'    => null,
    //     ];

    // }
    private function giaSauVAT(float $giaGoc, float $vat): float
    {
        return round($giaGoc * (1 + $vat / 100), 2);
    }

    private function apFlashSale(float $giaDaVAT, bool $noiBat, float $flash): float
    {
        // chỉ áp flash khi KHÔNG nổi bật
        if ($noiBat) return round($giaDaVAT, 2);
        $flash = max(0, min(1, $flash)); // kẹp 0..1
        return round($giaDaVAT * (1 - $flash), 2);
    }

    private function mapSanPham(SanPham $sp, int $soLuong): array
    {
        $vat       = (float) ($sp->VAT ?? 0);
        $giaGoc    = (float) ($sp->giaBan ?? 0);
        $noiBat    = (bool)  ($sp->is_noi_bat ?? false);
        $flash     = (float) ($sp->flash_sale ?? 0);

        // B1: giá đã VAT (chưa sale)
        $giaDaVAT  = $this->giaSauVAT($giaGoc, $vat);

        // B2: giá cuối sau flash (nếu không nổi bật)
        $giaCuoi1sp = $this->apFlashSale($giaDaVAT, $noiBat, $flash);

        return [
            'id'         => $sp->id,
            'ten'        => $sp->tenSanPham,
            'hinhAnh'    => $sp->hinhAnh,
            'moTa'       => $sp->moTa,

            'gia_goc'    => round($giaGoc, 2),     // giá niêm yết (chưa VAT, chưa sale)
            'VAT'        => round($vat, 2),
            'gia'        => $giaDaVAT,             // <-- GIÁ ĐÃ VAT (đúng yêu cầu)
            'giaSauVAT'  => $giaCuoi1sp,           // <-- GIÁ CUỐI SAU FLASH (tên cũ nhưng nội dung là FINAL)

            'soLuong'    => $soLuong,
            'tonKho'     => (int)($sp->soLuongTon ?? 0),

            'noiBat'     => $noiBat,
            'flash_sale' => round($flash, 2),

            'giamGia'    => null,                  // không dùng per-item discount ở đây
        ];
    }
    private function giaHienHanh(SanPham $sp): float
    {
        // $gia = (float) ($sp->giaBan ?? 0);
        // $noiBat = (bool) ($sp->is_noi_bat ?? false);
        // $saleRate = (float) ($sp->flash_sale ?? 0); // 0..1

        // // Áp flash_sale CHỈ KHI KHÔNG nổi bật
        // if (!$noiBat && $saleRate > 0 && $saleRate <= 1) {
        //     $gia *= (1 - $saleRate);
        // }
        // return round($gia, 2);
        // Không áp khuyến mãi ở đây nữa, vì flash_sale sẽ áp SAU VAT
        $giaGoc = (float) ($sp->giaBan ?? 0);
        return round($giaGoc, 2);
    }

    private function tinhGiaCoVAT(float $giaSauKM, float $vat): float
    {
        return round($giaSauKM * (1 + $vat / 100), 2);
    }

    public function layGio(int|string $nguoiDungId): array
    {
        // $ds = array_values($this->repo->tatCa($nguoiDungId));
        // $tamTinh = 0.0;

        // foreach ($ds as &$sp) {
        //     // $sp['gia'] lúc này đã là giá SAU KM (chưa VAT)
        //     $gia1spCoVAT = $this->tinhGiaCoVAT((float)$sp['gia'], (float)($sp['VAT'] ?? 0));
        //     $sp['gia_cuoi']   = $gia1spCoVAT;                          // 1 sp sau VAT
        //     $sp['thanh_tien'] = round($gia1spCoVAT * (int)$sp['soLuong'], 2);
        //     $tamTinh         += $sp['thanh_tien'];
        // }
        // unset($sp);

        // $result = [
        //     'san_pham' => $ds,
        //     'tam_tinh' => round($tamTinh, 2),
        // ];
        // // Log::info('GIO HANG DEBUG', ['userId' => $nguoiDungId, 'gio' => $result]); // nếu cần
        // return $result;
        $ds = array_values($this->repo->tatCa($nguoiDungId));
        $tamTinh = 0.0;

        foreach ($ds as &$sp) {
            // giá cuối 1 sp sau VAT & flash
            $gia1spCuoi = isset($sp['giaSauVAT']) ? (float)$sp['giaSauVAT']
                : (float)$sp['gia']; // fallback nếu dữ liệu cũ
            $sp['gia_cuoi']   = round($gia1spCuoi, 2);
            $sp['thanh_tien'] = round($gia1spCuoi * (int)$sp['soLuong'], 2);
            $tamTinh         += $sp['thanh_tien'];
        }
        unset($sp);

        return [
            'san_pham' => $ds,
            'tam_tinh' => round($tamTinh, 2),
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
