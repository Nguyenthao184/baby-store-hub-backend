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
    public function index(Request $request)
    {
        $query = HoaDon::query()->with('donHang.khachHang');

        // Tìm theo mã hóa đơn
        if ($request->filled('maHoaDon')) {
            $query->where('maHoaDon', 'like', '%' . $request->maHoaDon . '%');
        }

        // Lọc theo phương thức thanh toán
        if ($request->filled('phuongThuc')) {
            $query->where('phuongThucThanhToan', $request->phuongThuc);
        }

        // Lọc theo khoảng thời gian
        if ($request->filled('thoiGian')) {
            $now = now()->startOfDay();
            switch ($request->thoiGian) {
                case 'hom_nay':
                    $query->whereDate('ngayXuat', $now);
                    break;
                case 'hom_qua':
                    $query->whereDate('ngayXuat', $now->copy()->subDay());
                    break;
                case 'tuan_nay':
                    $query->whereBetween('ngayXuat', [$now->copy()->startOfWeek(), $now->copy()->endOfWeek()]);
                    break;
                case 'tuan_truoc':
                    $query->whereBetween('ngayXuat', [
                        $now->copy()->subWeek()->startOfWeek(),
                        $now->copy()->subWeek()->endOfWeek()
                    ]);
                    break;
                case 'thang_nay':
                    $query->whereBetween('ngayXuat', [
                        $now->copy()->startOfMonth(),
                        $now->copy()->endOfMonth()
                    ]);
                    break;
                case 'thang_truoc':
                    $query->whereBetween('ngayXuat', [
                        $now->copy()->subMonth()->startOfMonth(),
                        $now->copy()->subMonth()->endOfMonth()
                    ]);
                    break;
            }
        }

        return response()->json($query->orderByDesc('ngayXuat')->get());
    }

    /**
     * Cập nhật thông tin hóa đơn
     */
    public function update(UpdateHoaDonRequest $request, $id)
    {
        DB::beginTransaction();

        try {
            $data = $request->validated(); 
            $hoaDon = HoaDon::findOrFail($id);
            $donHang = $hoaDon->donHang;

            // Cập nhật thông tin hóa đơn
            $hoaDon->update([
                'tongTienHang' => $request->tongTienHang,
                'giamGiaSanPham' => $request->giamGiaSanPham ?? 0,
                'thueVAT' => $request->thueVAT ?? 0,
                'tongThanhToan' => $request->tongThanhToan,
                'phuongThucThanhToan' => $request->phuongThucThanhToan,
            ]);

            // Cập nhật ghi chú/trạng thái đơn hàng nếu có
            $donHang->update([
                'ghiChu' => $request->ghiChu ?? '',
                'trangThai' => $request->trangThai ?? 'completed',
            ]);

            // Xóa sản phẩm cũ và cộng lại kho
            if ($request->has('xoaSanPhamIds')) {
                $chiTiets = ChiTietDonHang::where('donHang_id', $donHang->id)
                    ->whereIn('sanpham_id', $request->xoaSanPhamIds)
                    ->get();

                foreach ($chiTiets as $chiTiet) {
                    $sanPham = SanPham::find($chiTiet->sanpham_id);
                    if ($sanPham) {
                        $sanPham->soLuongTon  += $chiTiet->soLuong;
                        $sanPham->save();
                    }
                }

                ChiTietDonHang::where('donHang_id', $donHang->id)
                    ->whereIn('sanpham_id', $request->xoaSanPhamIds)
                    ->delete();
            }

            // Cập nhật / thêm sản phẩm
            if ($request->has('sanPhams')) {
                foreach ($request->sanPhams as $item) {
                    $sanPham = SanPham::find($item['id']);
                    if (!$sanPham) continue;

                    $chiTietCu = ChiTietDonHang::where('donHang_id', $donHang->id)
                        ->where('sanpham_id', $item['id'])
                        ->first();

                    $soLuongCu = $chiTietCu ? $chiTietCu->soLuong : 0;
                    $soLuongMoi = $item['soLuong'];
                    $chenhLech = $soLuongMoi - $soLuongCu;

                    if ($chenhLech > 0) {
                        if ($sanPham->soLuongTon < $chenhLech) {
                            DB::rollBack();
                            return response()->json([
                                'error' => 'Không đủ hàng tồn kho cho sản phẩm ' . $sanPham->tenSanPham
                            ], 400);
                        }
                        $sanPham->soLuongTon  -= $chenhLech;
                    } elseif ($chenhLech < 0) {
                        $sanPham->soLuongTon  += abs($chenhLech);
                    }

                    $sanPham->save();

                    // Cập nhật sau khi xử lý kho
                    ChiTietDonHang::updateOrCreate(
                        [
                            'donHang_id' => $donHang->id,
                            'sanpham_id' => $item['id']
                        ],
                        [
                            'soLuong' => $item['soLuong'],
                            'giaBan' => $item['giaBan'],
                            'giamGia' => $item['giamGia'] ?? 0,
                            'tongTien' => $item['tongTien']
                        ]
                    );
                }
            }

            DB::commit();

            return response()->json([
                'message' => 'Cập nhật hóa đơn thành công',
                'data' => $hoaDon->load('donHang.chiTietDonHang')
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

    public function show($id)
    {
        $hoaDon = HoaDon::with([
            'donHang.khachHang',
            'donHang.chiTietDonHang.sanPham'
        ])->findOrFail($id);
        $donHang = $hoaDon->donHang;

        $sanPhams = $donHang->chiTietDonHang->map(function ($item) {
            return [
                'id' => $item->sanpham_id,
                'tenSanPham' => $item->sanPham->ten ?? '',
                'soLuong' => $item->soLuong,
                'giaBan' => $item->giaBan,
                'giamGia' => $item->giamGia,
                'VAT' => $item->sanPham->VAT ?? 0,
                'tongTien' => $item->tongTien
            ];
        });

        return response()->json([
            'success' => true,
            'data' => [
                'hoaDon' => [
                    'id' => $hoaDon->id,
                    'maHoaDon' => $hoaDon->maHoaDon,
                    'phuongThucThanhToan' => $hoaDon->phuongThucThanhToan,
                    'tongTienHang' => $hoaDon->tongTienHang,
                    'giamGia' => $hoaDon->giamGiaSanPham,
                    'thueVAT' => $hoaDon->thueVAT,
                    'tongThanhToan' => $hoaDon->tongThanhToan,
                    'ngayXuat' => $hoaDon->ngayXuat,
                ],
                'khachHang' => [
                    'ten' => $donHang->khachHang->hoTen ?? 'Khách lẻ',
                    'soDienThoai' => $donHang->khachHang->sdt ?? 'Không có'
                ],
                'trangThai' => $donHang->trangThai,
                'ghiChu' => $donHang->ghiChu,
                'sanPhams' => $sanPhams
            ]
        ]);
    }


}
