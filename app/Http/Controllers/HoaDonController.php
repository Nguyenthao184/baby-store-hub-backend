<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\HoaDon;
use Illuminate\Support\Facades\DB;
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
            $data   = $request->validated();
            $hoaDon = HoaDon::findOrFail($id);
            $donHang = $hoaDon->donHang;

            // Chuẩn hóa key từ FE
            $giamVoucher = $data['giamVoucher'] ?? $data['giamGiaSanPham'] ?? 0;
            $tongVAT     = $data['tongVAT'] ?? $data['thueVAT'] ?? 0;
            $giamDiem    = $data['giamDiem'] ?? 0;

            // Cập nhật thông tin hóa đơn (phi_van_chuyen luôn 0 cho giao dịch offline)
            $hoaDon->update([
                'tong_tien_hang'         => $data['tongTienHang'],
                'giam_voucher'           => $giamVoucher,
                'giam_diem'              => $giamDiem,
                'tong_vat'               => $tongVAT,
                'tong_thanh_toan'        => $data['tongThanhToan'],
                'phuong_thuc_thanh_toan' => $data['phuongThucThanhToan'],
                'phi_van_chuyen'         => 0,
            ]);

            // Cập nhật ghi chú/trạng thái đơn hàng nếu có
            $donHang->update([
                'ghi_chu'    => $data['ghiChu'] ?? '',
                'trang_thai' => $data['trangThai'] ?? 'completed',
            ]);

            // Xóa sản phẩm cũ ra khỏi đơn + hoàn kho
            if (!empty($data['xoaSanPhamIds']) && is_array($data['xoaSanPhamIds'])) {
                $chiTiets = ChiTietDonHang::where('don_hang_id', $donHang->id)
                    ->whereIn('san_pham_id', $data['xoaSanPhamIds'])
                    ->get();

                foreach ($chiTiets as $ct) {
                    $sp = SanPham::find($ct->san_pham_id);
                    if ($sp) {
                        $sp->soLuongTon = (int)$sp->soLuongTon + (int)$ct->so_luong;
                        $sp->save();
                    }
                }

                ChiTietDonHang::where('don_hang_id', $donHang->id)
                    ->whereIn('san_pham_id', $data['xoaSanPhamIds'])
                    ->delete();
            }

            // Cập nhật / thêm sản phẩm (snapshot vào chitietdonhang)
            if (!empty($data['sanPhams']) && is_array($data['sanPhams'])) {
                foreach ($data['sanPhams'] as $item) {
                    // $item: { id, soLuong, giaBan?, giamGia?, VAT? }
                    $sanPham = SanPham::find($item['id'] ?? null);
                    if (!$sanPham) {
                        continue;
                    }

                    // Lấy chi tiết hiện có (nếu có) để tính chênh lệch kho
                    $chiTietCu = ChiTietDonHang::where('don_hang_id', $donHang->id)
                        ->where('san_pham_id', $sanPham->id)
                        ->first();

                    $soLuongCu  = $chiTietCu ? (int)$chiTietCu->so_luong : 0;
                    $soLuongMoi = (int)($item['soLuong'] ?? 0);
                    $chenhLech  = $soLuongMoi - $soLuongCu;

                    // Kiểm tra & cập nhật tồn kho theo chênh lệch
                    if ($chenhLech > 0) {
                        if ((int)$sanPham->soLuongTon < $chenhLech) {
                            DB::rollBack();
                            return response()->json([
                                'error' => 'Không đủ hàng tồn kho cho sản phẩm ' . ($sanPham->tenSanPham ?? $sanPham->id)
                            ], 400);
                        }
                        $sanPham->soLuongTon = (int)$sanPham->soLuongTon - $chenhLech;
                    } elseif ($chenhLech < 0) {
                        $sanPham->soLuongTon = (int)$sanPham->soLuongTon + abs($chenhLech);
                    }
                    $sanPham->save();

                    // Tính snapshot & thành tiền theo công thức:
                    // thanh_tien = (gia * (1 + vat/100) - giam_gia) * so_luong
                    $gia     = isset($item['giaBan']) ? (float)$item['giaBan'] : (float)$sanPham->giaBan;
                    $vat     = isset($item['VAT']) ? (float)$item['VAT'] : (float)$sanPham->VAT;
                    $giamGia = isset($item['giamGia']) ? (float)$item['giamGia'] : 0.0;
                    $thanhTien = round(($gia * (1 + $vat / 100) - $giamGia) * $soLuongMoi, 2);

                    // Ghi vào bảng chitietdonhang (snapshot)
                    ChiTietDonHang::updateOrCreate(
                        [
                            'don_hang_id' => $donHang->id,
                            'san_pham_id' => $sanPham->id, // string(36)
                        ],
                        [
                            'ten_san_pham' => $sanPham->tenSanPham,
                            'gia'          => $gia,
                            'vat'          => $vat,
                            'giam_gia'     => $giamGia,
                            'so_luong'     => $soLuongMoi,
                            'thanh_tien'   => $thanhTien,
                        ]
                    );
                }
            }

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
        $hoaDon = HoaDon::findOrFail($id);
        $hoaDon->delete();

        return response()->json(['message' => 'Xóa hóa đơn thành công.']);
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
                'tenSanPham'  => $item->ten_san_pham,     // snapshot
                'soLuong'     => (int)$item->so_luong,
                'giaBan'      => (float)$item->gia,       // snapshot
                'VAT'         => (float)$item->vat,       // snapshot
                'giamGia'     => (float)$item->giam_gia,  // snapshot
                'tongTien'    => (float)$item->thanh_tien // snapshot
            ];
        });

        return response()->json([
            'success' => true,
            'data' => [
                'hoaDon' => [
                    'id'                   => $hoaDon->id,
                    'maHoaDon'            => $hoaDon->ma_hoa_don,
                    'phuongThucThanhToan' => $hoaDon->phuong_thuc_thanh_toan,
                    'tongTienHang'        => (float)$hoaDon->tong_tien_hang,
                    'giamVoucher'         => (float)$hoaDon->giam_voucher,
                    'giamDiem'            => (float)$hoaDon->giam_diem,
                    'thueVAT'             => (float)$hoaDon->tong_vat,
                    'tongThanhToan'       => (float)$hoaDon->tong_thanh_toan,
                    'ngayXuat'            => $hoaDon->ngay_xuat,
                    // Offline: không trả phí vận chuyển, hoặc luôn 0 nếu muốn hiển thị
                    // 'phiVanChuyen'      => 0
                ],
                'khachHang' => [
                    'ten'         => $donHang->khachHang->hoTen ?? 'Khách lẻ',
                    'soDienThoai' => $donHang->khachHang->sdt ?? 'Không có'
                ],
                'trangThai' => $donHang->trang_thai,
                'ghiChu'    => $donHang->ghi_chu,
                'sanPhams'  => $sanPhams
            ]
        ]);
    }
}
