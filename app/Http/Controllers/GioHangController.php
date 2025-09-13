<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\GioHang\ThemVaoGioRequest;
use App\Http\Requests\GioHang\CapNhatSoLuongRequest;
use App\Services\GioHangService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class GioHangController extends Controller
{
    public function __construct(private GioHangService $service) {}

 
    public function xem(Request $request)
    {
        try {
            $nguoiDungId = $request->user()->id;
            $gio = $this->service->layGio($nguoiDungId);

            return response()->json([
                'success' => true,
                'message' => 'Lấy giỏ hàng thành công',
                'data'    => $gio
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * POST /gio-hang/them
     * Thêm sản phẩm vào giỏ
     */
    public function them(ThemVaoGioRequest $request)
    {
        try {
            $nguoiDungId = $request->user()->id;
            $sanPhamId   = $request->input('san_pham_id');
            $soLuong     = (int)($request->input('so_luong', 1));

            $gio = $this->service->them($nguoiDungId, $sanPhamId, $soLuong);

            return response()->json([
                'success' => true,
                'message' => 'Thêm vào giỏ hàng thành công',
                'data'    => $gio
            ], 200);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Dữ liệu không hợp lệ',
                'errors'  => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * PUT /gio-hang/cap-nhat/{sanPhamId}
     * Cập nhật số lượng 1 sản phẩm trong giỏ
     */
    public function capNhat(CapNhatSoLuongRequest $request, string $sanPhamId)
    {
        try {
            // Nếu body có san_pham_id thì kiểm tra khớp với path param (tránh nhầm)
            if ($request->filled('san_pham_id') && $request->input('san_pham_id') !== $sanPhamId) {
                return response()->json([
                    'success' => false,
                    'message' => 'san_pham_id không khớp đường dẫn'
                ], 422);
            }

            $nguoiDungId = $request->user()->id;
            $soLuong     = (int)$request->input('so_luong');

            $gio = $this->service->capNhat($nguoiDungId, $sanPhamId, $soLuong);

            return response()->json([
                'success' => true,
                'message' => 'Cập nhật số lượng thành công',
                'data'    => $gio
            ], 200);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Dữ liệu không hợp lệ',
                'errors'  => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * DELETE /gio-hang/xoa/{sanPhamId}
     * Xóa 1 sản phẩm khỏi giỏ
     */
    public function xoa(Request $request, string $sanPhamId)
    {
        try {
            $nguoiDungId = $request->user()->id;
            $gio = $this->service->xoa($nguoiDungId, $sanPhamId);

            return response()->json([
                'success' => true,
                'message' => 'Xóa sản phẩm khỏi giỏ hàng thành công',
                'data'    => $gio
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * DELETE /gio-hang/xoa-het
     * Xóa toàn bộ giỏ hàng
     */
    public function xoaHet(Request $request)
    {
        try {
            $nguoiDungId = $request->user()->id;
            $gio = $this->service->xoaHet($nguoiDungId);

            return response()->json([
                'success' => true,
                'message' => 'Đã xóa toàn bộ giỏ hàng',
                'data'    => $gio
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi: ' . $e->getMessage()
            ], 500);
        }
    }
}
