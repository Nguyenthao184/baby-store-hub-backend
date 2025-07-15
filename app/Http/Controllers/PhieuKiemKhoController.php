<?php

namespace App\Http\Controllers;

use App\Models\PhieuKiemKho;
use App\Models\ChiTietPhieuKiemKho;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Requests\PhieuKiemKho\StorePhieuKiemKhoRequest;
use App\Http\Requests\PhieuKiemKho\StoreChiTietPhieuKiemKhoRequest;


class PhieuKiemKhoController extends Controller
{
    /**
     * Lấy danh sách phiếu kiểm kho
     */
    public function index()
    {
        $list = PhieuKiemKho::orderBy('ngay_kiem', 'desc')->get();

        return response()->json([
            'success' => true,
            'data' => $list
        ]);
    }

    /**
     * Tạo phiếu kiểm kho mới
     */
    public function store(StorePhieuKiemKhoRequest $request)
    {

        $user = auth('taikhoan')->user();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn chưa đăng nhập hoặc token không hợp lệ.'
            ], 401);
        }

        $phieu = PhieuKiemKho::create([
            'ma_phieu_kiem' => $request->ma_phieu_kiem,
            'ngay_kiem' => $request->ngay_kiem,
            'ghi_chu' => $request->ghi_chu,
            'nguoi_tao_id' => $user->id,
            'trang_thai' => 'phieu_tam'
        ]);

        return response()->json([
            'success' => true,
            'data' => $phieu
        ]);
    }


    public function update(Request $request, $id)
    {
        $phieu = PhieuKiemKho::findOrFail($id);

        if ($phieu->trang_thai !== 'phieu_tam') {
            return response()->json([
                'success' => false,
                'message' => 'Chỉ được cập nhật phiếu ở trạng thái tạm.'
            ], 400);
        }

        $phieu->update([
            'ngay_kiem' => $request->ngay_kiem ?? $phieu->ngay_kiem,
            'ghi_chu' => $request->ghi_chu ?? $phieu->ghi_chu,
        ]);

        return response()->json([
            'success' => true,
            'data' => $phieu
        ]);
    }

    public function destroy($id)
    {
        $phieu = PhieuKiemKho::findOrFail($id);

        if ($phieu->trang_thai !== 'phieu_tam') {
            return response()->json([
                'success' => false,
                'message' => 'Chỉ được xóa phiếu ở trạng thái tạm.'
            ], 400);
        }

        $phieu->delete();

        return response()->json([
            'success' => true,
            'message' => 'Đã xóa phiếu kiểm kho.'
        ]);
    }
    /**
     * Thêm chi tiết sản phẩm kiểm kho
     */
    public function addDetail(StoreChiTietPhieuKiemKhoRequest $request, $phieu_id)
    {
        $chenhLech = $request->so_luong_thuc_te - $request->so_luong_ly_thuyet;

        $detail = ChiTietPhieuKiemKho::create([
            'phieu_kiem_id' => $phieu_id,
            'san_pham_id' => $request->san_pham_id,
            'so_luong_ly_thuyet' => $request->so_luong_ly_thuyet,
            'so_luong_thuc_te' => $request->so_luong_thuc_te,
            'so_chenh_lech' => $chenhLech,
        ]);

        return response()->json([
            'success' => true,
            'data' => $detail
        ]);
    }

    /**
     * Xem chi tiết phiếu kiểm kho
     */
    public function show($id)
    {
        $phieu = PhieuKiemKho::with(['chiTiet'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $phieu
        ]);
    }

    /**
     * Cân bằng tồn kho và cập nhật trạng thái
     */
    public function canBang($id)
    {
        DB::beginTransaction();

        $phieu = PhieuKiemKho::with('chiTiet')->findOrFail($id);

        if ($phieu->trang_thai !== 'phieu_tam') {
            return response()->json([
                'success' => false,
                'message' => 'Phiếu này đã được cân bằng hoặc hủy.'
            ], 400);
        }

        $tongLyThuyet = $phieu->chiTiet->sum('so_luong_ly_thuyet');
        $tongThucTe = $phieu->chiTiet->sum('so_luong_thuc_te');
        $tongChenhLech = $phieu->chiTiet->sum('so_chenh_lech');
        $tongTang = $phieu->chiTiet->where('so_chenh_lech', '>', 0)->sum('so_chenh_lech');
        $tongGiam = $phieu->chiTiet->where('so_chenh_lech', '<', 0)->sum('so_chenh_lech');

        $phieu->update([
            'ngay_can_bang' => now(),
            'tong_so_luong_ly_thuyet' => $tongLyThuyet,
            'tong_so_luong_thuc_te' => $tongThucTe,
            'tong_chenh_lech' => $tongChenhLech,
            'tong_lech_tang' => $tongTang,
            'tong_lech_giam' => $tongGiam,
            'trang_thai' => 'da_can_bang',
        ]);

        DB::commit();

        return response()->json([
            'success' => true,
            'message' => 'Cân bằng tồn kho thành công.',
            'data' => $phieu
        ]);
    }
    public function deleteDetail($id)
    {
        $detail = ChiTietPhieuKiemKho::findOrFail($id);

        // Optional: Chỉ cho xóa nếu phiếu tạm
        $phieu = PhieuKiemKho::find($detail->phieu_kiem_id);
        if ($phieu && $phieu->trang_thai !== 'phieu_tam') {
            return response()->json([
                'success' => false,
                'message' => 'Chỉ được xóa chi tiết khi phiếu đang ở trạng thái tạm.'
            ], 400);
        }

        $detail->delete();

        return response()->json([
            'success' => true,
            'message' => 'Đã xóa chi tiết sản phẩm.'
        ]);
    }
}
