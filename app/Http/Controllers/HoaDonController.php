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
        $query = HoaDon::query()->with('donHang.khachHang');

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
            $hoaDon  = HoaDon::with('donHang.chiTietDonHang')->findOrFail($id);
            $donHang = $hoaDon->donHang;

            // ============ Cập nhật / thêm / xóa sản phẩm ============
            // Xóa sản phẩm cũ + hoàn kho
            if (!empty($data['xoaSanPhamIds']) && is_array($data['xoaSanPhamIds'])) {
                $chiTiets = ChiTietDonHang::where('don_hang_id', $donHang->id)
                    ->whereIn('san_pham_id', $data['xoaSanPhamIds'])->get();

                foreach ($chiTiets as $ct) {
                    if ($sp = SanPham::find($ct->san_pham_id)) {
                        $sp->soLuongTon = (int)$sp->soLuongTon + (int)$ct->so_luong;
                        $sp->save();
                    }
                }
                ChiTietDonHang::where('don_hang_id', $donHang->id)
                    ->whereIn('san_pham_id', $data['xoaSanPhamIds'])
                    ->delete();
            }

            // Thêm / cập nhật sản phẩm
            if (!empty($data['sanPhams']) && is_array($data['sanPhams'])) {
                $hasFlashCol = Schema::hasColumn('chitietdonhang', 'flash_sale');

                foreach ($data['sanPhams'] as $item) {
                    $sanPham = SanPham::find($item['id'] ?? null);
                    if (!$sanPham) continue;

                    // Tồn kho theo chênh lệch
                    $ctCu = ChiTietDonHang::where('don_hang_id', $donHang->id)
                        ->where('san_pham_id', $sanPham->id)->first();

                    $soLuongCu  = $ctCu ? (int)$ctCu->so_luong : 0;
                    $soLuongMoi = (int)($item['soLuong'] ?? 0);
                    $chenhLech  = $soLuongMoi - $soLuongCu;

                    if ($chenhLech > 0) {
                        if ((int)$sanPham->soLuongTon < $chenhLech) {
                            DB::rollBack();
                            return response()->json(['error' => 'Không đủ hàng tồn kho cho ' . ($sanPham->tenSanPham ?? $sanPham->id)], 400);
                        }
                        $sanPham->soLuongTon -= $chenhLech;
                    } elseif ($chenhLech < 0) {
                        $sanPham->soLuongTon += abs($chenhLech);
                    }
                    $sanPham->save();

                    // ===== Snapshot đơn giá theo công thức: VAT → flash → trừ giam_gia =====
                    $giaGoc   = isset($item['giaBan']) ? (float)$item['giaBan'] : (float)$sanPham->giaBan;
                    $vat      = isset($item['VAT']) ? (float)$item['VAT'] : (float)($sanPham->VAT ?? 8.0);
                    $flash    = isset($item['flashSale']) ? (float)$item['flashSale'] : (float)($sanPham->flash_sale ?? 0.0);
                    $flash    = max(0.0, min(0.9, $flash));
                    $giamGia  = isset($item['giamGia']) ? (float)$item['giamGia'] : 0.0;

                    $giaSauVat   = round($giaGoc * (1 + $vat / 100), 2);
                    $giaSauFlash = round($giaSauVat * (1 - $flash), 2);
                    $donGiaSauG  = max(0, round($giaSauFlash - $giamGia, 2));
                    $thanhTien   = round($donGiaSauG * $soLuongMoi, 2);

                    $payload = [
                        'ten_san_pham' => $sanPham->tenSanPham,
                        'gia'          => $giaSauFlash, // đơn giá sau VAT & flash (trước giảm)
                        'vat'          => $vat,
                        'giam_gia'     => $giamGia,
                        'so_luong'     => $soLuongMoi,
                        'thanh_tien'   => $thanhTien,
                    ];
                    if ($hasFlashCol) {
                        $payload['flash_sale'] = $flash; // 0..1
                    }

                    ChiTietDonHang::updateOrCreate(
                        ['don_hang_id' => $donHang->id, 'san_pham_id' => $sanPham->id],
                        $payload
                    );
                }
            }

            // ============ TÍNH LẠI TỔNG từ snapshot chi tiết ============
            $donHang->load('chiTietDonHang');
            $chiTiets = $donHang->chiTietDonHang;

            // tam_tinh = SUM(thanh_tien)
            $tamTinh = round($chiTiets->sum(fn($ct) => (float)$ct->thanh_tien), 2);

            // giam_flash_sale (VAT trước, khôi phục giá sau VAT rồi lấy chênh)
            $giamFlashSale = round($chiTiets->sum(function ($ct) {
                $gia   = (float)($ct->gia ?? 0);          // đã VAT & flash
                $flash = (float)($ct->flash_sale ?? 0);   // 0..1
                $qty   = (int)($ct->so_luong ?? 0);
                if ($gia <= 0 || $qty <= 0 || $flash <= 0 || $flash >= 1) return 0;
                $giaAfterVat   = $gia / (1 - $flash);
                return ($giaAfterVat - $gia) * $qty;
            }), 2);

            // tong_vat (tính trên giá sau VAT trước flash)
            $tongVAT = round($chiTiets->sum(function ($ct) {
                $giaAfterFlash = (float)($ct->gia ?? 0);
                $vat           = (float)($ct->vat ?? 0);
                $flash         = (float)($ct->flash_sale ?? 0);
                $qty           = (int)($ct->so_luong ?? 0);
                if ($vat <= 0 || $qty <= 0 || $giaAfterFlash <= 0) return 0;
                $den = (1 - $flash) > 0 ? (1 - $flash) : 1;
                $giaAfterVat = $giaAfterFlash / $den; // giá sau VAT trước flash
                return ($giaAfterVat * $vat / (100 + $vat)) * $qty;
            }), 2);

            // Nhận các giá trị từ request (nếu có), nhưng ưu tiên dùng snapshot
            $giamVoucher   = (float)($data['giamVoucher'] ?? $data['giamGiaSanPham'] ?? $hoaDon->giam_voucher ?? 0);
            $giamDiem      = (float)($data['giamDiem'] ?? $hoaDon->giam_diem ?? 0);
            $phiVanChuyen  = (float)($hoaDon->phi_van_chuyen ?? 0); // offline có thể là 0

            // tong_thanh_toan theo DonHangSeeder
            $tongThanhToan = round($tamTinh - $giamVoucher - $giamDiem + $phiVanChuyen, 2);

            // ============ Cập nhật DonHang & HoaDon để đồng bộ ============
            $donHang->update([
                'tam_tinh'         => $tamTinh,
                'giam_voucher'     => $giamVoucher,
                'giam_diem'        => $giamDiem,
                'phi_van_chuyen'   => $phiVanChuyen,
                'tong_thanh_toan'  => $tongThanhToan,
                'ghi_chu'          => $data['ghiChu'] ?? $donHang->ghi_chu,
                'trang_thai'       => $data['trangThai'] ?? $donHang->trang_thai,
            ]);

            $hoaDon->update([
                'tong_tien_hang'         => $tamTinh,        // = SUM thanh_tien
                'giam_flash_sale'        => $giamFlashSale,  // tính tự động
                'giam_voucher'           => $giamVoucher,
                'giam_diem'              => $giamDiem,
                'tong_vat'               => $tongVAT,        // tính theo chính sách VAT trước flash
                'tong_thanh_toan'        => $tongThanhToan,  // khớp đơn hàng
                'phuong_thuc_thanh_toan' => $data['phuongThucThanhToan'] ?? $hoaDon->phuong_thuc_thanh_toan,
                // 'phi_van_chuyen'      => $phiVanChuyen,    // giữ như hiện có; nếu muốn ép 0 cho offline thì mở
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Cập nhật hóa đơn thành công',
                'data'    => $hoaDon->load('donHang.chiTietDonHang')
            ]);
        } catch (\Exception $e) {
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
                    'ten'         => $donHang->khachHang->hoTen ?? 'Khách lẻ',
                    'soDienThoai' => $donHang->khachHang->sdt ?? 'Không có',
                    'diaChi'      => $donHang->khachHang->diaChi ?? 'Không có'
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
