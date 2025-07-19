<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Models\DonHang;
use App\Models\HoaDon;
use App\Models\ChiTietDonHang;
use App\Models\SanPham;
use App\Http\Requests\DonHang\ThanhToanDonHangRequest;

class DonHangController extends Controller
{
    public function thanhToan(ThanhToanDonHangRequest $request)
    {
        DB::beginTransaction();

        try {
            $data = $request->validated();

            // Map phương thức thanh toán từ frontend sang database enum
            $phuongThucMap = [
                'cash' => 'TienMat',
                'bank' => 'ChuyenKhoan', 
                'card' => 'The',
                'TienMat' => 'TienMat',
                'ChuyenKhoan' => 'ChuyenKhoan',
                'The' => 'The'
            ];

            $phuongThucThanhToan = $phuongThucMap[$request->phuongThuc] ?? 'TienMat';

            // Tính toán lại tổng tiền hàng và giảm giá
            $tongTienHang = 0;
            $tongGiamGia = 0;
            
            foreach ($request->sanPhams as $item) {
                $tongTienHang += $item['giaBan'] * $item['soLuong'];
                $tongGiamGia += ($item['giamGia'] ?? 0) * $item['soLuong'];
            }

            // 1. Tạo đơn hàng
            $donHang = DonHang::create([
                'maDonHang' => 'DH' . now()->format('YmdHis'),
                'khachHang_id' => $request->khachHang_id,
                'trangThai' => 'completed',
                'ngayTao' => now(),
                'ngayCapNhat' => now(),
            ]);

            // 2. Lưu chi tiết sản phẩm
            foreach ($request->sanPhams as $item) {
                $soLuong = $item['soLuong'];
                $giaBan = $item['giaBan'];
                $giamGia = $item['giamGia'] ?? 0;
                $tongTien = $giaBan * $soLuong - $giamGia * $soLuong;

                ChiTietDonHang::create([
                    'donHang_id' => $donHang->id,
                    'sanpham_id' => $item['id'],
                    'soLuong' => $soLuong,
                    'giaBan' => $giaBan,
                    'giamGia' => $giamGia,
                    'tongTien' => $tongTien,
                ]);
                
                // Trừ kho
                $sanPham = SanPham::find($item['id']);
                if (!$sanPham) {
                    DB::rollBack();
                    return response()->json(['error' => 'Không tìm thấy sản phẩm.'], 404);
                }

                if ($sanPham->soLuongTon < $soLuong) {
                    DB::rollBack();
                    return response()->json([
                        'error' => 'Không đủ hàng tồn kho cho sản phẩm ' . $sanPham->ten
                    ], 400);
                }

                $sanPham->soLuongTon -= $soLuong;
                $sanPham->save();
            }

            // 3. Tạo hóa đơn
            $lastMa = HoaDon::orderBy('maHoaDon', 'desc')->value('maHoaDon');
            $nextSo = $lastMa ? intval(substr($lastMa, 2)) + 1 : 1;
            $maHoaDon = 'HD' . str_pad($nextSo, 6, '0', STR_PAD_LEFT);
            $hoaDon = HoaDon::create([
                'id' => Str::uuid(),
                'maHoaDon' => $maHoaDon,
                'donHang_id' => $donHang->id,
                'ngayXuat' => now(),
                'tongTienHang' => $tongTienHang,
                'giamGiaSanPham' => $tongGiamGia,
                'thueVAT' => 0,
                'tongThanhToan' => $tongTienHang - $tongGiamGia,
                'phuongThucThanhToan' => $phuongThucThanhToan,
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Thanh toán thành công',
                'hoaDonId' => $hoaDon->id,
                'phuongThucThanhToan' => $phuongThucThanhToan,
                'tongTienHang' => $tongTienHang,
                'giamGiaSanPham' => $tongGiamGia,
                'tongThanhToan' => $tongTienHang - $tongGiamGia
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'error' => $e->getMessage()
            ], 500);
        }
    }
}