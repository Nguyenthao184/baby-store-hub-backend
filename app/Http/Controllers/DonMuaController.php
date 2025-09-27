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

    public function index(Request $request)
    {
        // Lấy user & map sang khách hàng (giống hệt datHang)
        $userId = $request->user()->id ?? null;
        if (!$userId) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $kh = KhachHang::where('taiKhoan_id', $userId)->first();
        $khachHangId = $kh?->id;
        if (!$khachHangId) {
            return response()->json(['message' => 'Không tìm thấy khách hàng'], 404);
        }

        // Bộ lọc tuỳ chọn
        $trangThai = $request->input('trang_thai'); // CHO_XU_LY|...|DA_HUY
        $dateFrom  = $request->input('from');       // YYYY-MM-DD
        $dateTo    = $request->input('to');         // YYYY-MM-DD

        // Lấy đơn hàng + chi tiết (snapshot). Không cần join sản phẩm để tránh lệch giá.
        $q = DonHang::with(['chiTietDonHang' /* ->select([...]) nếu muốn */])
            ->where('khach_hang_id', $khachHangId)
            ->orderByDesc('ngay_tao');

        if ($trangThai) $q->where('trang_thai', $trangThai);
        if ($dateFrom)  $q->whereDate('ngay_tao', '>=', $dateFrom);
        if ($dateTo)    $q->whereDate('ngay_tao', '<=', $dateTo);

        $donHangs = $q->get();

        $data = $donHangs->map(function ($don) {
            $items = $don->chiTietDonHang->map(function ($ct) {
                $giaGoc      = (float)($ct->gia ?? 0);      // snapshot giá lúc mua (chưa VAT)
                $vatPercent  = (float)($ct->vat ?? 0);
                $soLuong     = (int)($ct->so_luong ?? 1);
                $giaCoVAT    = round($giaGoc * (1 + $vatPercent/100), 2);
                $thanhTienSP = round($giaCoVAT * $soLuong, 2); // thành tiền cho dòng

                return [
                    'san_pham_id'   => $ct->san_pham_id,
                    'ten_san_pham'  => $ct->ten_san_pham,
                    'so_luong'      => $soLuong,
                    'gia_goc'       => $giaGoc,
                    'vat_percent'   => $vatPercent,
                    'gia_co_vat'    => $giaCoVAT,     // giá 1 sp đã gồm VAT
                    'thanh_tien'    => $thanhTienSP,  // giá VAT × số lượng
                    // nếu bạn đã lưu sẵn $ct->thanh_tien là VAT×SL, có thể trả thêm để tham chiếu
                    'thanh_tien_snapshot' => (float)($ct->thanh_tien ?? 0),
                ];
            });

            $tongHangTinhLai = (float)$items->sum('thanh_tien');

            return [
                'id'                      => $don->id,
                'ma_don_hang'             => $don->ma_don_hang,
                'khach_hang_id'           => $don->khach_hang_id,

                'ten_nguoi_nhan'          => $don->ten_nguoi_nhan,
                'so_dien_thoai'           => $don->so_dien_thoai,
                'dia_chi'                 => $don->dia_chi,
                'ghi_chu'                 => $don->ghi_chu,

                'tam_tinh'                => (float)$don->tam_tinh,
                'giam_voucher'            => (float)$don->giam_voucher,
                'giam_diem'               => (float)$don->giam_diem,
                'phi_van_chuyen'          => (float)$don->phi_van_chuyen,

                // Tổng tiền theo snapshot trong bảng donhang
                'tong_thanh_toan'         => (float)$don->tong_thanh_toan,

                // Tổng tiền hàng tính lại từ chi tiết (để FE hiển thị dòng “Thành tiền”)
                'tong_hang_tinh_lai'      => $tongHangTinhLai,

                'voucher_id'              => $don->voucher_id,
                'don_vi_van_chuyen'       => $don->don_vi_van_chuyen,
                'ma_van_don'              => $don->ma_van_don,

                'trang_thai'              => $don->trang_thai,
                'phuong_thuc_thanh_toan'  => $don->phuong_thuc_thanh_toan,

                'ngay_tao'                => $don->ngay_tao,
                'ngay_cap_nhat'           => $don->ngay_cap_nhat,

                'san_pham'                => $items,
            ];
        });

        return response()->json([
            'data' => $data,
            'summary' => [
                'so_don'        => $data->count(),
                'tong_tat_ca'   => (float)$data->sum('tong_thanh_toan'), // theo snapshot đơn hàng
            ],
        ], 200);
    }

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
