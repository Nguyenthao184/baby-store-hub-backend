<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\HoaDon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Models\ChiTietDonHang;
use App\Models\SanPham;
use App\Http\Requests\HoaDon\UpdateHoaDonRequest;

class HoaDonController extends Controller
{
    /**
     * Lấy danh sách hóa đơn với các bộ lọc
     */
    public function index(Request $request)
    {
        $query = HoaDon::query()->with('donHang');

        // Tìm theo mã hóa đơn
        if ($request->filled('maHoaDon')) {
            $query->where('ma_hoa_don', 'like', '%' . $request->maHoaDon . '%');
        }

        // Lọc theo phương thức thanh toán (cod|momo|vnpay)
        if ($request->filled('phuongThuc')) {
            $query->where('phuong_thuc_thanh_toan', $request->phuongThuc);
        }

        // Lọc theo khoảng thời gian
        if ($request->filled('thoiGian')) {
            $now = now();
            switch ($request->thoiGian) {
                case 'hom_nay':
                    $query->whereDate('ngay_xuat', $now->toDateString());
                    break;
                case 'hom_qua':
                    $query->whereDate('ngay_xuat', $now->copy()->subDay()->toDateString());
                    break;
                case 'tuan_nay':
                    $query->whereBetween('ngay_xuat', [
                        $now->copy()->startOfWeek(),
                        $now->copy()->endOfWeek()
                    ]);
                    break;
                case 'tuan_truoc':
                    $query->whereBetween('ngay_xuat', [
                        $now->copy()->subWeek()->startOfWeek(),
                        $now->copy()->subWeek()->endOfWeek()
                    ]);
                    break;
                case 'thang_nay':
                    $query->whereBetween('ngay_xuat', [
                        $now->copy()->startOfMonth(),
                        $now->copy()->endOfMonth()
                    ]);
                    break;
                case 'thang_truoc':
                    $query->whereBetween('ngay_xuat', [
                        $now->copy()->subMonth()->startOfMonth(),
                        $now->copy()->subMonth()->endOfMonth()
                    ]);
                    break;
            }
        }

        return response()->json($query->orderByDesc('ngay_xuat')->get());
    }

    /**
     * Cập nhật thông tin hóa đơn (offline: không có phí vận chuyển)
     */
    public function update(UpdateHoaDonRequest $request, $id)
    {
        DB::beginTransaction();
        try {
            $data    = $request->validated();
            $hoaDon  = HoaDon::with('donHang.chiTietDonHang')->lockForUpdate()->findOrFail($id);
            $donHang = $hoaDon->donHang;

            $money = fn($v) => (int) round((float)$v, 0, PHP_ROUND_HALF_UP);

            /* ========== XÓA SẢN PHẨM (có hoàn kho) ========== */
            if (!empty($data['xoaSanPhamIds']) && is_array($data['xoaSanPhamIds'])) {
                $cts = ChiTietDonHang::where('don_hang_id', $donHang->id)
                    ->whereIn('san_pham_id', $data['xoaSanPhamIds'])
                    ->lockForUpdate()
                    ->get();

                foreach ($cts as $ct) {
                    if ($sp = SanPham::lockForUpdate()->find($ct->san_pham_id)) {
                        $sp->soLuongTon = (int)$sp->soLuongTon + (int)$ct->so_luong;
                        $sp->save();
                    }
                }
                ChiTietDonHang::where('don_hang_id', $donHang->id)
                    ->whereIn('san_pham_id', $data['xoaSanPhamIds'])
                    ->delete();
            }

            /* ========== CẬP NHẬT DÒNG: CHỈ tên/ số_lượng (giữ nguyên đơn giá) ========== */
            if (!empty($data['sanPhams']) && is_array($data['sanPhams'])) {
                foreach ($data['sanPhams'] as $item) {
                    $spId = $item['id'] ?? null;
                    if (!$spId) continue;

                    $ct = ChiTietDonHang::where('don_hang_id', $donHang->id)
                            ->where('san_pham_id', $spId)
                            ->lockForUpdate()
                            ->first();
                    if (!$ct) continue; // không thêm mới ở màn này

                    // Số lượng mới (nếu không gửi thì giữ cũ)
                    $qtyOld = (int)$ct->so_luong;
                    $qtyNew = array_key_exists('soLuong', $item) ? (int)$item['soLuong'] : $qtyOld;

                    // Điều chỉnh kho theo chênh lệch
                    $delta = $qtyNew - $qtyOld;
                    if ($delta !== 0) {
                        $sp = SanPham::lockForUpdate()->find($spId);
                        if ($delta > 0) {
                            if ((int)$sp->soLuongTon < $delta) {
                                DB::rollBack();
                                return response()->json(['error' => "Không đủ tồn kho cho {$sp->tenSanPham}"], 400);
                            }
                            $sp->soLuongTon -= $delta;
                        } else {
                            $sp->soLuongTon += abs($delta);
                        }
                        $sp->save();
                    }

                    // ✅ KHÔNG tái tính đơn giá; suy ra đơn giá NET/1sp từ snapshot hiện tại
                    if ($qtyOld > 0) {
                        $unitNet = $money(((float)$ct->thanh_tien) / $qtyOld); // giá đã trừ giảm theo 1sp
                    } else {
                        // fallback hiếm khi cần
                        $flashRaw = (float)($ct->flash_sale ?? 0);
                        $flash    = $flashRaw > 1 ? $flashRaw/100.0 : $flashRaw;
                        $unitNet  = $money(max(0, (float)$ct->gia * (1 - $flash) - (float)($ct->giam_gia ?? 0)));
                    }

                    $lineTotal = $money($unitNet * $qtyNew);

                    // Cập nhật: chỉ tên, số lượng, thành tiền (không đụng giá/vat/flash/giam_gia)
                    ChiTietDonHang::where('id', $ct->id)->update([
                        'ten_san_pham' => $item['tenSanPham'] ?? $ct->ten_san_pham,
                        'so_luong'     => $qtyNew,
                        'thanh_tien'   => $lineTotal,
                    ]);
                }
            }

            /* ========== TÍNH LẠI TỔNG (chỉ khi qty/xóa/voucher đổi) ========== */
            $donHang->load('chiTietDonHang');
            $cts = $donHang->chiTietDonHang;

            // tạm tính = SUM(thành_tiền)
            $tamTinh = $money($cts->sum(fn($c) => (float)$c->thanh_tien));

            // giảm flash = Σ( (unitAfterVat - unitAfterFlash) * qty )
            $giamFlashSale = $money($cts->sum(function ($c) use ($money) {
                $qty = (int)$c->so_luong;
                if ($qty <= 0) return 0;

                // khôi phục đơn giá NET/1sp và afterVAT/1sp từ snapshot
                $perUnitDisc    = (float)($c->giam_gia ?? 0);
                $unitNet        = $money(((float)$c->thanh_tien / $qty)); // đã trừ giảm
                $unitAfterFlash = $unitNet + $perUnitDisc;

                $flashRaw  = (float)($c->flash_sale ?? 0);
                $flash     = $flashRaw > 1 ? $flashRaw/100.0 : $flashRaw;
                $unitAfterVat = ($flash > 0 && $flash < 1)
                    ? $money($unitAfterFlash / (1 - $flash))
                    : (float)($c->gia ?? 0);

                return $money(($unitAfterVat - $unitAfterFlash) * $qty);
            }));

            // VAT = Σ( unitAfterVat * rate/(1+rate) * qty )
            $tongVAT = $money($cts->sum(function ($c) use ($money) {
                $qty = (int)$c->so_luong;
                if ($qty <= 0) return 0;

                $rateRaw  = (float)($c->vat ?? 0);
                $rate     = $rateRaw > 1 ? $rateRaw/100.0 : $rateRaw;

                $perUnitDisc    = (float)($c->giam_gia ?? 0);
                $unitNet        = $money(((float)$c->thanh_tien / $qty));
                $unitAfterFlash = $unitNet + $perUnitDisc;

                $flashRaw  = (float)($c->flash_sale ?? 0);
                $flash     = $flashRaw > 1 ? $flashRaw/100.0 : $flashRaw;

                $unitAfterVat = ($flash > 0 && $flash < 1)
                    ? $money($unitAfterFlash / (1 - $flash))
                    : (float)($c->gia ?? 0);

                if ($unitAfterVat <= 0 || $rate <= 0) return 0;
                return $money($unitAfterVat * ($rate/(1+$rate)) * $qty);
            }));

            // Voucher: CHỈ khi FE gửi mới đổi, còn lại giữ nguyên
            $giamVoucher  = isset($data['giamVoucher']) ? $money($data['giamVoucher']) : (int)$hoaDon->giam_voucher;
            // Các khoản khác giữ như cũ
            $giamDiem     = (int)($hoaDon->giam_diem ?? 0);
            $phiVC        = isset($data['phiVanChuyen']) ? $money($data['phiVanChuyen']) : (int)$hoaDon->phi_van_chuyen;

            $tongThanhToan = $money($tamTinh - $giamVoucher - $giamDiem + $phiVC);

            /* ========== CẬP NHẬT ĐƠN HÀNG: các field cho phép ========== */
            $donHang->update([
                'ten_nguoi_nhan'    => $data['tenNguoiNhan']   ?? $donHang->ten_nguoi_nhan,
                'so_dien_thoai'     => $data['soDienThoai']    ?? $donHang->so_dien_thoai,
                'don_vi_van_chuyen' => $data['donViVanChuyen'] ?? $donHang->don_vi_van_chuyen,
                'phi_van_chuyen'    => $phiVC,
                'dia_chi'           => $data['diaChi']         ?? $donHang->dia_chi,
                'ghi_chu'           => $data['ghiChu']         ?? $donHang->ghi_chu,

                'tam_tinh'          => $tamTinh,
                'giam_voucher'      => $giamVoucher,
                // giam_diem giữ nguyên
                'tong_thanh_toan'   => $tongThanhToan,
                'ngay_cap_nhat'     => now(),
            ]);

            /* ========== CẬP NHẬT HÓA ĐƠN (đồng bộ) ========== */
            $hoaDon->update([
                'tong_tien_hang'   => $tamTinh,
                'giam_flash_sale'  => $giamFlashSale,
                'tong_vat'         => $tongVAT,
                'giam_voucher'     => $giamVoucher,
                'giam_diem'        => $giamDiem,
                'phi_van_chuyen'   => $phiVC,
                'tong_thanh_toan'  => $tongThanhToan,
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Cập nhật hóa đơn thành công',
                'data'    => $hoaDon->load('donHang.chiTietDonHang'),
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Xóa hóa đơn
     */
    public function destroy($id)
    {
        return DB::transaction(function () use ($id) {
            // Khóa bản ghi hoá đơn + đơn hàng + chi tiết để tránh race
            $hoaDon = HoaDon::with(['donHang.chiTietDonHang'])
                ->lockForUpdate()
                ->findOrFail($id);

            $donHang  = $hoaDon->donHang;
            $chiTiets = $donHang ? $donHang->chiTietDonHang : collect();

            // Hoàn kho cho từng dòng chi tiết
            foreach ($chiTiets as $ct) {
                $sp = SanPham::lockForUpdate()->find($ct->san_pham_id);
                if ($sp) {
                    $sp->soLuongTon = (int)$sp->soLuongTon + (int)$ct->so_luong;
                    $sp->save();
                }
            }

            // Xóa hoá đơn
            $hoaDon->delete();

            return response()->json([
                'message' => 'Xóa hóa đơn thành công và đã hoàn kho.',
                'restocked_items' => $chiTiets->map(fn($ct) => [
                    'san_pham_id' => $ct->san_pham_id,
                    'so_luong_hoan' => (int)$ct->so_luong,
                ])->values(),
            ]);
        });
    }


    /**
     * Hiển thị chi tiết hóa đơn (dùng snapshot từ chitietdonhang)
     */
    public function show($id)
    {
        $hoaDon = HoaDon::with([
            'donHang.khachHang',
            'donHang.chiTietDonHang.sanPham'
        ])->findOrFail($id);

        $donHang = $hoaDon->donHang;

        $sanPhams = $donHang->chiTietDonHang->map(function ($item) {
            return [
                'id'          => $item->san_pham_id,
                'tenSanPham'  => $item->ten_san_pham,
                'soLuong'     => (int)$item->so_luong,
                'giaBan'      => (float)$item->gia,         // sau VAT & flash (trước giảm)
                'VAT'         => (float)$item->vat,
                'flashSale'   => (float)($item->flash_sale ?? 0),
                'giamGia'     => (float)$item->giam_gia,
                'tongTien'    => (float)$item->thanh_tien
            ];
        });

        return response()->json([
            'success' => true,
            'data' => [
                'hoaDon' => [
                    'id'                   => $hoaDon->id,
                    'maHoaDon'             => $hoaDon->ma_hoa_don,
                    'phuongThucThanhToan'  => $hoaDon->phuong_thuc_thanh_toan,
                    'tongTienHang'         => (float)$hoaDon->tong_tien_hang,
                    'giamFlashSale'        => (float)$hoaDon->giam_flash_sale,
                    'giamVoucher'          => (float)$hoaDon->giam_voucher,
                    'giamDiem'             => (float)$hoaDon->giam_diem,
                    'thueVAT'              => (float)$hoaDon->tong_vat,
                    'tongThanhToan'        => (float)$hoaDon->tong_thanh_toan,
                    'ngayXuat'             => $hoaDon->ngay_xuat,
                ],
                'khachHang' => [
                    'ten'         => $donHang->ten_nguoi_nhan,
                    'soDienThoai' => $donHang->so_dien_thoai,
                    'diaChi'      => $donHang->dia_chi
                ],
                'trangThai' => $donHang->trang_thai,
                'ghiChu'    => $donHang->ghi_chu,
                'vanChuyen' => [
                    'donVi'        => $donHang->don_vi_van_chuyen,
                    'maVanDon'     => $donHang->ma_van_don,
                    'phiVanChuyen' => (float)($donHang->phi_van_chuyen ?? 0),
                ],
                'sanPhams'  => $sanPhams
            ]
        ]);
    }
}
