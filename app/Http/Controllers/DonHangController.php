<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Models\DonHang;
use App\Models\HoaDon;
use App\Models\ChiTietDonHang;

class DonHangController extends Controller
{
    public function thanhToan(Request $request)
    {
        DB::beginTransaction();

        try {
            // 1. Tạo đơn hàng
            $donHang = DonHang::create([
                'maDonHang' => 'DH' . now()->format('YmdHis'),
                'khachHang_id' => $request->khachHang_id,
                'cuaHang_id' => $request->cuaHang_id,
                'trangThai' => 'completed',
                'ngayTao' => now(),
                'ngayCapNhat' => now(),
            ]);

            // 2. Lưu chi tiết sản phẩm
            foreach ($request->sanPhams as $item) {
                ChiTietDonHang::create([
                    'donHang_id' => $donHang->id,
                    'sanpham_id' => $item['id'],
                    'soLuong' => $item['soLuong'],
                    'giaBan' => $item['giaBan'],
                    'giamGia' => $item['giamGia'] ?? 0,
                    'tongTien' => $item['tongTien'],
                ]);
            }

            // 3. Tạo hóa đơn
            $hoaDon = HoaDon::create([
                'id' => Str::uuid(),
                'maHoaDon' => 'HD' . now()->format('YmdHis'),
                'donHang_id' => $donHang->id,
                'ngayXuat' => now(),
                'tongTienHang' => $request->tongTienHang,
                'giamGiaSanPham' => $request->giamGia ?? 0,
                'thueVAT' => 0,
                'tongThanhToan' => $request->tongThanhToan,
                'phuongThucThanhToan' => $request->phuongThuc,
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Thanh toán thành công',
                'hoaDonId' => $hoaDon->id
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
