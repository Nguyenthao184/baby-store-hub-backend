<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\DonHang;
use App\Models\ChiTietDonHang;
use App\Models\KhachHang;
use App\Services\GioHangService;

class DonMuaController extends Controller
{
    public function __construct(private GioHangService $gioHang) {}

    /**
     * GET /don-mua
     * Lọc đơn mua theo trạng thái & khoảng ngày.
     * Query:
     *  - status: CHO_XU_LY | CHO_LAY_HANG | DANG_GIAO_HANG | THANH_CONG | HUY | (bỏ trống = tất cả)
     *  - date: YYYY-MM-DD (lọc đúng 1 ngày)
     *  - from, to: YYYY-MM-DD (lọc khoảng ngày [from..to])
     *  - page, per_page
     */
    public function index(Request $request)
    {
        $userId = $request->user()->id;

        $kh = KhachHang::where('taiKhoan_id', $userId)->first();
        if (!$kh) {
            return response()->json(['message' => 'Không tìm thấy khách hàng'], 404);
        }

        $status   = $request->query('status');  // trạng thái
        $date     = $request->query('date');    // YYYY-MM-DD
        $from     = $request->query('from');    // YYYY-MM-DD
        $to       = $request->query('to');      // YYYY-MM-DD

        $allowedStatuses = ['CHO_THANH_TOAN','CHO_XU_LY','CHO_LAY_HANG','DANG_GIAO_HANG','THANH_CONG','HUY'];

        $q = DonHang::query()
            ->where('khach_hang_id', $kh->id)
            ->orderByDesc('ngay_tao');

        if ($status && in_array($status, $allowedStatuses, true)) {
            $q->where('trang_thai', $status);
        }

        if ($date) {
            $q->whereDate('ngay_tao', $date);
        }

        if ($from && $to) {
            $q->whereDate('ngay_tao', '>=', $from)
            ->whereDate('ngay_tao', '<=', $to);
        }

        $data = $q->get();

        $result = $data->map(function (DonHang $d) {
            return [
                'id'              => $d->id,
                'ma_don_hang'     => $d->ma_don_hang,
                'trang_thai'      => $d->trang_thai,
                'ngay_tao'        => $d->ngay_tao,
                'tong_thanh_toan' => $d->tong_thanh_toan,
                'so_san_pham'     => ChiTietDonHang::where('don_hang_id', $d->id)->sum('so_luong'),
                'ma_van_don'      => $d->ma_van_don,
                'don_vi_vc'       => $d->don_vi_van_chuyen,
            ];
        });

        return response()->json($result);
    }


    /**
     * GET /don-mua/{id}
     * Xem chi tiết 1 đơn hàng của chính khách.
     */
    public function show(Request $request, string $id)
    {
        $userId = $request->user()->id;

        $kh = KhachHang::where('taiKhoan_id', $userId)->first();
        if (!$kh) return response()->json(['message' => 'Không tìm thấy khách hàng'], 404);

        $don = DonHang::where('id', $id)
            ->where('khach_hang_id', $kh->id)
            ->first();

        if (!$don) return response()->json(['message' => 'Không tìm thấy đơn hàng'], 404);

        $items = ChiTietDonHang::where('don_hang_id', $don->id)->get(['san_pham_id','ten_san_pham','gia','vat','giam_gia','so_luong','thanh_tien']);

        return response()->json([
            'id'           => $don->id,
            'ma_don_hang'  => $don->ma_don_hang,
            'trang_thai'   => $don->trang_thai,
            'phuong_thuc'  => $don->phuong_thuc_thanh_toan,
            'ngay_tao'     => $don->ngay_tao,
            'tong_tien_hang'  => $don->tam_tinh,
            'giam_voucher'    => $don->giam_voucher,
            'giam_diem'       => $don->giam_diem,
            'phi_van_chuyen'  => $don->phi_van_chuyen,
            'tong_thanh_toan' => $don->tong_thanh_toan,
            'nguoi_nhan'   => [
                'ten'   => $don->ten_nguoi_nhan,
                'sdt'   => $don->so_dien_thoai,
                'dia_chi' => $don->dia_chi,
            ],
            'van_don' => [
                'don_vi' => $don->don_vi_van_chuyen,
                'ma_van_don' => $don->ma_van_don,
            ],
            'items' => $items,
        ]);
    }

    /**
     * POST /don-mua/{id}/reorder
     * Mua lại: đưa toàn bộ item của đơn cũ vào giỏ hiện tại của user.
     * - Mặc định cộng dồn số lượng (tùy GioHangService của bạn).
     */
    public function reorder(Request $request, string $id)
    {
        $userId = $request->user()->id;

        $kh = KhachHang::where('taiKhoan_id', $userId)->first();
        if (!$kh) return response()->json(['message' => 'Không tìm thấy khách hàng'], 404);

        $don = DonHang::where('id', $id)
            ->where('khach_hang_id', $kh->id)
            ->first();

        if (!$don) return response()->json(['message' => 'Không tìm thấy đơn hàng'], 404);

        $items = ChiTietDonHang::where('don_hang_id', $don->id)->get();
        if ($items->isEmpty()) {
            return response()->json(['message' => 'Đơn hàng không có sản phẩm'], 422);
        }

        // Đưa items vào giỏ
        DB::transaction(function () use ($items, $userId) {
            foreach ($items as $ct) {
                // tuỳ GioHangService của bạn: giả sử có hàm them()
                // them($userId, $san_pham_id, $soLuong, $gia, $vat, $giam_gia, $ten)
                $this->gioHang->them(
                    $userId,
                    $ct->san_pham_id,
                    (int) $ct->so_luong,
                    (float) ($ct->gia ?? 0),
                    (float) ($ct->vat ?? 0),
                    (float) ($ct->giam_gia ?? 0),
                    (string) ($ct->ten_san_pham ?? '')
                );
            }
        });

        $gio = $this->gioHang->layGio($userId);

        return response()->json([
            'message' => 'Đã thêm sản phẩm vào giỏ từ đơn hàng cũ',
            'gio_hang' => $gio,
        ], 200);
    }

    public function cancelByCustomer(Request $request, string $id)
    {
        $data = $request->validate([
            'ly_do' => ['nullable','string','max:500'],
        ]);

        $userId = $request->user()->id;

        return DB::transaction(function () use ($id, $userId, $data) {
            // Khóa bản ghi tránh race
            $don = DonHang::lockForUpdate()->find($id);
            if (!$don) {
                return response()->json(['message' => 'Không tìm thấy đơn hàng'], 404);
            }

            // Xác nhận quyền sở hữu đơn
            $kh = $don->khach_hang_id ? KhachHang::find($don->khach_hang_id) : null;
            if (!$kh || (int)$kh->taiKhoan_id !== (int)$userId) {
                return response()->json(['message' => 'Bạn không có quyền hủy đơn này'], 403);
            }

            // Chỉ cho hủy khi CHO_XU_LY
            if ($don->trang_thai !== 'CHO_LAY_HANG') {
                return response()->json([
                    'message' => 'Chỉ được hủy khi đơn ở trạng thái CHO_XU_LY',
                    'current' => $don->trang_thai,
                ], 422);
            }

            // (Tuỳ chọn) nếu đã tạo vận đơn GHN, bạn có thể gọi API hủy ở đây
            // if (!empty($don->ma_van_don)) {
            //     app(\App\Services\GhnService::class)->cancelOrder($don->ma_van_don);
            // }

            // Cập nhật trạng thái sang HUY
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
