<?php

namespace App\Observers;

use App\Models\ThanhToan;
use App\Models\HoaDon;
use App\Models\DonHang;
use App\Models\ChiTietDonHang;
use App\Models\SanPham;
use App\Models\KhachHang;
use App\Services\GioHangService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ThanhToanObserver
{
    /**
     * Handle the ThanhToan "created" event.
     */
    public function created(ThanhToan $thanhToan): void
    {
        //
    }

    /**
     * Handle the ThanhToan "updated" event.
     */
    public function updated(ThanhToan $thanhToan): void
    {
        // Chỉ xử lý khi trạng thái vừa đổi sang DA_THANH_TOAN
        if (!$thanhToan->wasChanged('trang_thai') || $thanhToan->trang_thai !== 'DA_THANH_TOAN') {
            return;
        }

        DB::transaction(function () use ($thanhToan) {
            // Khóa đơn chống race
            $donhang = DonHang::where('id', $thanhToan->don_hang_id)->lockForUpdate()->first();
            if (!$donhang) {
                Log::warning('Observer: DonHang not found', ['don_hang_id' => $thanhToan->don_hang_id]);
                return;
            }

            // Nếu đã có Hóa đơn => bỏ qua
            $hadInvoice = HoaDon::where('don_hang_id', $donhang->id)->exists();
            if (!$hadInvoice) {
                $items = ChiTietDonHang::where('don_hang_id', $donhang->id)->get();

                $tongTienHang = 0.0;
                $tongVAT      = 0.0;

                foreach ($items as $it) {
                    $gia     = (float) ($it->gia ?? 0);
                    $vatPct  = (float) ($it->vat ?? 0);
                    $giamGia = (float) ($it->giam_gia ?? 0);
                    $sl      = (int)   ($it->so_luong ?? 0);

                    $giaSauGiam = max(0, $gia - $giamGia);
                    $truocVAT   = $giaSauGiam * $sl;
                    $tienVAT    = $truocVAT * ($vatPct / 100.0);

                    $tongTienHang += $truocVAT;
                    $tongVAT      += $tienVAT;
                }

                // Các khoản thêm từ đơn hàng
                $giamVoucher   = (float) ($donhang->giam_voucher ?? 0);
                $giamDiem      = (float) ($donhang->giam_diem ?? 0);
                $phiVC         = (float) ($donhang->phi_van_chuyen ?? 0);
                $tongThanhToan = (float) ($donhang->tong_thanh_toan ?? 0);

                // Tạo Hóa đơn
                $hoaDon = HoaDon::create([
                    'id'                     => (string) Str::uuid(),
                    'ma_hoa_don'             => HoaDon::genSoHoaDon(),
                    'don_hang_id'            => $donhang->id,
                    'ngay_xuat'              => now(),
                    'tong_tien_hang'         => $tongTienHang,
                    'tong_vat'               => $tongVAT,
                    'giam_voucher'           => $giamVoucher,
                    'giam_diem'              => $giamDiem,
                    'phi_van_chuyen'         => $phiVC,
                    'tong_thanh_toan'        => $tongThanhToan,
                    'phuong_thuc_thanh_toan' => $donhang->phuong_thuc_thanh_toan,
                ]);

                Log::info('Observer: Created HoaDon', [
                    'don_hang_id' => $donhang->id,
                    'hoa_don_id'  => $hoaDon->id,
                ]);

                // Trừ kho
                foreach ($items as $ct) {
                    $sp = SanPham::where('id', $ct->san_pham_id)->lockForUpdate()->first();
                    if ($sp) {
                        $sp->soLuongTon = (int)$sp->soLuongTon - (int)$ct->so_luong;
                        $sp->save();
                    }
                }
            }

            // Cập nhật trạng thái Đơn hàng
            $donhang->update([
                'trang_thai'    => 'CHO_LAY_HANG',
                'ngay_cap_nhat' => now(),
            ]);

            // Xoá giỏ hàng user
            if ($donhang->khach_hang_id) {
                $kh = KhachHang::find($donhang->khach_hang_id);
                if ($kh?->taiKhoan_id) {
                    app(GioHangService::class)->xoaHet($kh->taiKhoan_id);
                    Log::info('Observer: cleared cart after paid', [
                        'taiKhoan_id' => $kh->taiKhoan_id,
                        'don_hang_id' => $donhang->id,
                    ]);
                }
            }
        });
    }

    /**
     * Handle the ThanhToan "deleted" event.
     */
    public function deleted(ThanhToan $thanhToan): void
    {
        //
    }

    /**
     * Handle the ThanhToan "restored" event.
     */
    public function restored(ThanhToan $thanhToan): void
    {
        //
    }

    /**
     * Handle the ThanhToan "force deleted" event.
     */
    public function forceDeleted(ThanhToan $thanhToan): void
    {
        //
    }
}
