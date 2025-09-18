<?php

namespace App\Observers;

use App\Models\ThanhToan;
use App\Models\HoaDon;
use App\Models\DonHang;
use App\Models\ChiTietDonHang;
use App\Models\SanPham;
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

            // Nếu đã có Hóa đơn => bỏ qua (đồng nghĩa đã trừ kho trước đó)
            if (HoaDon::where('don_hang_id', $donhang->id)->exists()) {
                Log::info('Observer: HoaDon existed, skip create & stock deduction', ['don_hang_id' => $donhang->id]);
                return;
            }

            // Snapshot chi tiết
            $items = ChiTietDonHang::where('don_hang_id', $donhang->id)->get();

            // ======= TÍNH LẠI THEO CÔNG THỨC CỦA BẠN =======
            $tamTinh            = 0.0;  // tổng tiền hàng (đã gồm VAT & giảm)
            $tongVatCongDon     = 0.0;  // cộng dồn VAT để lưu báo cáo (không bắt buộc)
            $tongTruocVatCongDon= 0.0;  // cộng dồn giá trước VAT sau chiết khấu (để đối chiếu)

            foreach ($items as $it) {
                $gia     = (float) ($it->gia ?? 0);       // giá niêm yết / 1 sp (chưa VAT)
                $vatPct  = (float) ($it->vat ?? 0);       // % VAT
                $giamRaw = (float) ($it->giam_gia ?? 0);  // có thể là tỷ lệ (0..1) hoặc VND
                $sl      = (int)   ($it->so_luong ?? 0);

                // Nếu có cờ 'noi_bat' trong chi tiết thì ưu tiên không giảm (đúng công thức ảnh)
                $noiBat  = (bool) ($it->noi_bat ?? false);

                // Xác định hệ số giảm
                // - Nếu giam_gia trong [0,1] => coi là TỶ LỆ giảm
                // - Nếu giam_gia > 1 => coi là SỐ TIỀN giảm / 1sp (fallback)
                if ($giamRaw >= 0 && $giamRaw <= 1) {
                    $discountFactor = $noiBat ? 1.0 : max(0.0, 1.0 - $giamRaw);
                    // Giá cuối / 1 sp: gia * (1 + VAT) * discountFactor
                    $giaCuoi1sp = round($gia * (1 + $vatPct/100.0) * $discountFactor, 2);

                    // Tách phần trước VAT & VAT (để lưu báo cáo nếu muốn)
                    $truocVAT1sp = round($gia * $discountFactor, 2);
                    $vat1sp      = round($giaCuoi1sp - $truocVAT1sp, 2);
                } else {
                    // Fallback: giảm theo SỐ TIỀN VND / 1sp (giống luồng cũ)
                    $giaSauGiam  = max(0.0, $gia - $giamRaw);
                    $truocVAT1sp = round($giaSauGiam, 2);
                    $vat1sp      = round($truocVAT1sp * $vatPct/100.0, 2);
                    $giaCuoi1sp  = round($truocVAT1sp + $vat1sp, 2);
                }

                $thanhTien = round($giaCuoi1sp * $sl, 2);

                $tamTinh             += $thanhTien;
                $tongTruocVatCongDon += $truocVAT1sp * $sl;
                $tongVatCongDon      += $vat1sp * $sl;
            }

            // Các khoản sau bước đặt hàng
            $giamVoucher   = (float) ($donhang->giam_voucher    ?? 0);
            $giamDiem      = (float) ($donhang->giam_diem       ?? 0);
            $phiVanChuyen  = (float) ($donhang->phi_van_chuyen  ?? 0);
            $phiCod        = (float) ($donhang->phi_cod         ?? 0); // nếu không có cột này, để = 0

            // Tổng thanh toán cuối cùng
            $tongThanhToan = round($tamTinh - $giamVoucher - $giamDiem + $phiVanChuyen + $phiCod, 2);

            // 1) Tạo Hóa đơn theo số đã tính
            $hoaDon = HoaDon::create([
                'id'                     => (string) Str::uuid(),
                'ma_hoa_don'             => $this->genSoHoaDon(),
                'don_hang_id'            => $donhang->id,
                'ngay_xuat'              => now(), // đổi thành cột ngày của bạn nếu khác

                // Ghi 'tiền hàng' theo đúng định nghĩa bạn đang dùng (đã gồm VAT & giảm)
                'tong_tien_hang'         => $tamTinh,

                // Lưu tổng VAT cộng dồn (để báo cáo). Nếu bạn không dùng, có thể để 0.
                'tong_vat'               => $tongVatCongDon,

                'giam_voucher'           => $giamVoucher,
                'giam_diem'              => $giamDiem,
                'phi_van_chuyen'         => $phiVanChuyen,
                'tong_thanh_toan'        => $tongThanhToan,
                'phuong_thuc_thanh_toan' => $donhang->phuong_thuc_thanh_toan,
            ]);

            // 2) Trừ kho – cùng transaction
            foreach ($items as $ct) {
                $sp = \App\Models\SanPham::where('id', $ct->san_pham_id)->lockForUpdate()->first();
                if (!$sp) {
                    Log::warning('Observer: SanPham not found', ['san_pham_id' => $ct->san_pham_id]);
                    continue;
                }
                $sp->soLuongTon = (int)$sp->soLuongTon - (int)$ct->so_luong;
                $sp->save();
            }

            // (tuỳ chọn) cập nhật trạng thái đơn
            $donhang->update(['trang_thai' => 'DA_THANH_TOAN']);

            Log::info('Observer: Created HoaDon & deducted stock (with new formula)', [
                'don_hang_id'      => $donhang->id,
                'hoa_don_id'       => $hoaDon->id,
                'tam_tinh'         => $tamTinh,
                'tong_vat'         => $tongVatCongDon,
                'tong_thanh_toan'  => $tongThanhToan,
            ]);
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

    private function genSoHoaDon(): string
    {
        // Ví dụ: HD-20250918-000123
        $dateKey = now()->toDateString(); // YYYY-MM-DD

        // Đếm có khoá để tránh race
        $count = HoaDon::whereDate('ngay_xuat', $dateKey)
            ->lockForUpdate()
            ->count() + 1;

        return 'HD-' . now()->format('Ymd') . '-' . str_pad((string)$count, 6, '0', STR_PAD_LEFT);
    }
}
