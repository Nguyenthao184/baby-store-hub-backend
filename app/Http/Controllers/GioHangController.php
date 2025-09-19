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

    // POST /api/checkout/tinh-tong
    public function tinhTong(Request $request)
    {
        $userId = $request->user()->id;

        // 1) Validate input
        $data = $request->validate([
            'giam_voucher'    => 'nullable|numeric|min:0',
            'giam_diem'       => 'nullable|numeric|min:0',
            'phi_van_chuyen'  => 'nullable|numeric|min:0',
            'phi_cod'         => 'nullable|numeric|min:0',
            'kenh_thanh_toan' => 'nullable|in:cod,momo,vnpay', 
        ]);

        // 2) Lấy lại giỏ + tạm tính từ server
        $gio = $this->service->layGio($userId);
        $tamTinh = (float) $gio['tam_tinh'];

        // 3) Clamp (không cho giảm vượt tạm tính)
        $giamVoucher   = min((float)($data['giam_voucher']   ?? 0), $tamTinh);
        $giamDiem      = min((float)($data['giam_diem']      ?? 0), $tamTinh - $giamVoucher);
        $phiVanChuyen  = (float)($data['phi_van_chuyen']     ?? 0);
        $phiCod        = (float)($data['phi_cod']            ?? 0);

        // 4) Nếu không phải COD thì không tính phí COD (tuỳ bạn)
        if (($data['kenh_thanh_toan'] ?? null) !== 'cod') {
            $phiCod = 0;
        }

        // 5) Tính tổng
        $tongThanhToan = $tamTinh - $giamVoucher - $giamDiem + $phiVanChuyen + $phiCod;
        if ($tongThanhToan < 0) $tongThanhToan = 0;

        return response()->json([
            'success' => true,
            'message' => 'Tính tổng đơn hàng thành công',
            'data' => [
                'san_pham'        => $gio['san_pham'],
                'tam_tinh'        => round($tamTinh, 2),
                'giam_voucher'    => round($giamVoucher, 2),
                'giam_diem'       => round($giamDiem, 2),
                'phi_van_chuyen'  => round($phiVanChuyen, 2),
                'phi_cod'         => round($phiCod, 2),
                'tong_thanh_toan' => round($tongThanhToan, 2),
            ]
        ]);
    }
}
