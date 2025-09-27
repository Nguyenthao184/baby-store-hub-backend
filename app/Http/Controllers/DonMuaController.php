<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\DonHang;
use App\Models\ChiTietDonHang;
use App\Models\KhachHang;
use App\Services\GioHangService;

class DonMuaController extends Controller
{
    public function __construct(private GioHangService $gioHang) {}

    /**
     * Danh sách đơn đã mua của user (theo snapshot)
     */
    public function index(Request $request)
    {
        $userId = $request->user()->id ?? null;
        if (!$userId) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $kh = \App\Models\KhachHang::where('taiKhoan_id', $userId)->first();
        if (!$kh) {
            return response()->json(['message' => 'Không tìm thấy khách hàng'], 404);
        }

        $q = \App\Models\DonHang::with(['chiTietDonHang'])
            ->where('khach_hang_id', $kh->id)
            ->orderByDesc('ngay_tao');

        if ($t = $request->input('trang_thai')) $q->where('trang_thai', $t);
        if ($from = $request->input('from'))     $q->whereDate('ngay_tao', '>=', $from);
        if ($to = $request->input('to'))         $q->whereDate('ngay_tao', '<=', $to);

        $donHangs = $q->get();

        $data = $donHangs->map(function ($don) {
            // Mỗi dòng: đơn giá hiển thị = thành_tiền / số_lượng (đã VAT + flash + giảm dòng nếu có)
            $items = $don->chiTietDonHang->map(function ($ct) {
                $soLuong   = max(1, (int)($ct->so_luong ?? 1));
                $thanhTien = (float)($ct->thanh_tien ?? 0);
                $donGia    = round($soLuong > 0 ? $thanhTien / $soLuong : 0, 2);

                return [
                    'san_pham_id'  => $ct->san_pham_id,
                    'ten_san_pham' => $ct->ten_san_pham,
                    'so_luong'     => $soLuong,
                    'don_gia'      => $donGia,                   // đơn giá như trong giỏ
                    'thanh_tien'   => round($thanhTien, 2),      // thành tiền dòng
                ];
            });

            // Tổng tiền hàng (chỉ cộng sản phẩm)
            $tongTienSanPham = (float) $items->sum('thanh_tien');

            return [
                'id'                  => $don->id,
                'ma_don_hang'         => $don->ma_don_hang,
                'trang_thai'          => $don->trang_thai,
                'ngay_tao'            => $don->ngay_tao,

                // Tổng tiền sản phẩm (đúng như giỏ lúc đặt)
                'tong_tien_san_pham'  => round($tongTienSanPham, 2),

                // ✅ Các trường cấp-đơn mà bạn yêu cầu thêm
                'giam_voucher'        => (float) ($don->giam_voucher ?? 0),
                'giam_diem'           => (float) ($don->giam_diem ?? 0),
                'phi_van_chuyen'      => (float) ($don->phi_van_chuyen ?? 0),
                'don_vi_van_chuyen'   => $don->don_vi_van_chuyen,   // null nếu đơn offline
                'ma_van_don'          => $don->ma_van_don,          // null nếu đơn offline
                'tong_thanh_toan'     => (float) ($don->tong_thanh_toan ?? 0),

                'san_pham'            => $items,
            ];
        });

        return response()->json([
            'data'    => $data,
            'summary' => [
                'so_don'                  => $data->count(),
                'tong_tat_ca_san_pham'    => (float) $data->sum('tong_tien_san_pham'),
                'tong_tat_ca_thanh_toan'  => (float) $data->sum('tong_thanh_toan'),
            ],
        ], 200);
    }



    /**
     * Mua lại: thêm lại các SP từ đơn cũ vào giỏ theo GIÁ HIỆN HÀNH (GioHangService tự tính VAT/flash hiện tại)
     */
    public function reorder(Request $request, string $id)
    {
        $userId = $request->user()->id ?? null;
        if (!$userId) return response()->json(['message' => 'Unauthenticated'], 401);

        $kh = KhachHang::where('taiKhoan_id', $userId)->first();
        if (!$kh) return response()->json(['message' => 'Không tìm thấy khách hàng'], 404);

        $don = DonHang::where('id', $id)->where('khach_hang_id', $kh->id)->first();
        if (!$don) return response()->json(['message' => 'Không tìm thấy đơn hàng'], 404);

        $items = ChiTietDonHang::where('don_hang_id', $don->id)->get();
        if ($items->isEmpty()) {
            return response()->json(['message' => 'Đơn hàng không có sản phẩm'], 422);
        }

        $errors = [];
        DB::beginTransaction();
        try {
            foreach ($items as $ct) {
                try {
                    // them($userId, $sanPhamId, $soLuong) -> GioHangService tự kiểm kho & tự tính giá hiện hành
                    $this->gioHang->them($userId, (string)$ct->san_pham_id, (int)$ct->so_luong);
                } catch (\Throwable $e) {
                    $errors[] = [
                        'san_pham_id' => $ct->san_pham_id,
                        'error'       => $e->getMessage(),
                    ];
                    // tiếp tục SP khác, không rollback toàn bộ
                }
            }
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['message' => 'Có lỗi khi thêm vào giỏ', 'error' => $e->getMessage()], 500);
        }

        $gio = $this->gioHang->layGio($userId);

        return response()->json([
            'message'   => empty($errors) ? 'Đã thêm sản phẩm vào giỏ từ đơn hàng cũ' : 'Đã thêm một phần sản phẩm (một số bị lỗi)',
            'gio_hang'  => $gio,
            'errors'    => $errors, // danh sách SP không thêm được (hết hàng / không tồn tại)
        ], 200);
    }

    /**
     * Khách hủy đơn: chỉ cho hủy khi CHO_XU_LY
     */
    public function cancelByCustomer(Request $request, string $id)
    {
        $data = $request->validate([
            'ly_do' => ['nullable','string','max:500'],
        ]);

        $userId = $request->user()->id ?? null;
        if (!$userId) return response()->json(['message' => 'Unauthenticated'], 401);

        return DB::transaction(function () use ($id, $userId, $data) {
            $don = DonHang::lockForUpdate()->find($id);
            if (!$don) {
                return response()->json(['message' => 'Không tìm thấy đơn hàng'], 404);
            }

            $kh = $don->khach_hang_id ? KhachHang::find($don->khach_hang_id) : null;
            if (!$kh || (int)$kh->taiKhoan_id !== (int)$userId) {
                return response()->json(['message' => 'Bạn không có quyền hủy đơn này'], 403);
            }

            if ($don->trang_thai !== 'CHO_XU_LY') {
                return response()->json([
                    'message' => 'Chỉ được hủy khi đơn ở trạng thái CHO_XU_LY',
                    'current' => $don->trang_thai,
                ], 422);
            }

            // (tuỳ chọn) nếu đã có mã vận đơn: gọi API hủy vận đơn GHN ở đây

            $don->update([
                'trang_thai'    => 'DA_HUY',
                'ngay_cap_nhat' => now(),
                'ghi_chu'       => trim(($don->ghi_chu ? $don->ghi_chu.' | ' : '').'KH hủy: '.($data['ly_do'] ?? '')),
            ]);

            return response()->json([
                'message'  => 'Đã hủy đơn hàng thành công',
                'don_hang' => [
                    'id'          => $don->id,
                    'ma_don_hang' => $don->ma_don_hang,
                    'trang_thai'  => $don->trang_thai,
                ],
            ], 200);
        });
    }
}
