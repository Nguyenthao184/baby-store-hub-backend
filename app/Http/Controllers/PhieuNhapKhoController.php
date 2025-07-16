<?php

namespace App\Http\Controllers;

use App\Models\PhieuNhapKho;
use App\Models\ChiTietPhieuNhapKho;
use App\Models\SanPham;
use App\Models\NhaCungCap;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class PhieuNhapKhoController extends Controller
{
    /**
     * Lọc và hiển thị danh sách phiếu nhập
     */
    public function loc(Request $request)
    {
        $query = PhieuNhapKho::query()->with('nhaCungCap');

        // Lọc theo trạng thái
        if ($request->filled('trangThai')) {
            $query->where('trang_thai', $request->trangThai);
        }

        // Lọc theo ngày tùy chọn
        if ($request->filled('ngayTu') && $request->filled('ngayDen')) {
            $query->whereBetween('ngay_nhap', [$request->ngayTu, $request->ngayDen]);
        } elseif ($request->filled('thoiGian')) {
            $now = now()->startOfDay();
            switch ($request->thoiGian) {
                case 'hom_nay':
                    $query->whereDate('ngay_nhap', $now);
                    break;
                case 'hom_qua':
                    $query->whereDate('ngay_nhap', $now->copy()->subDay());
                    break;
                case 'tuan_nay':
                    $query->whereBetween('ngay_nhap', [$now->copy()->startOfWeek(), $now->copy()->endOfWeek()]);
                    break;
                case 'tuan_truoc':
                    $query->whereBetween('ngay_nhap', [$now->copy()->subWeek()->startOfWeek(), $now->copy()->subWeek()->endOfWeek()]);
                    break;
                case 'thang_nay':
                    $query->whereBetween('ngay_nhap', [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()]);
                    break;
                case 'thang_truoc':
                    $query->whereBetween('ngay_nhap', [$now->copy()->subMonth()->startOfMonth(), $now->copy()->subMonth()->endOfMonth()]);
                    break;
            }
        }

        // Tìm theo mã phiếu
        if ($request->filled('maPhieu')) {
            $query->where('so_phieu', 'like', '%' . $request->maPhieu . '%');
        }

        // Lọc theo người tạo hoặc người nhập (cần cột created_by hoặc user nhập kho nếu có)
        if ($request->filled('nguoiTao')) {
            $query->where('created_by', $request->nguoiTao);
        }

        return response()->json($query->orderByDesc('ngay_nhap')->get());
    }    

    /**
     * Lấy danh sách tất cả phiếu nhập kho (kèm chi tiết và sản phẩm)
     */
    public function index()
    {
        $dsPhieu = PhieuNhapKho::with('chiTiet.sanPham')
            ->orderByDesc('ngay_nhap')
            ->get();

        return response()->json($dsPhieu);
    }

    /**
     * Tạo mới phiếu nhập kho (dạng phiếu tạm, chưa cộng vào tồn kho)
     */
    public function store(Request $request)
    {
        DB::beginTransaction();

        try {
            $lastPhieu = DB::table('phieu_nhap_kho')->orderBy('id', 'desc')->first();
            $nextNumber = $lastPhieu ? ((int)substr($lastPhieu->so_phieu, 3)) + 1 : 1;
            $soPhieu = 'PNK' . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
            // 1. Tạo phiếu nhập
            $phieuNhap = PhieuNhapKho::create([
                'so_phieu' => $soPhieu,
                'ngay_nhap' => $request->ngay_nhap,
                'nha_cung_cap_id' => $request->nha_cung_cap_id,
                'tong_tien_nhap' => 0, // sẽ tính lại phía dưới
                'ghi_chu' => $request->ghi_chu,
                'trang_thai' => $request->trang_thai ?? 'phieu_tam'
            ]);

            $tongTien = 0;

            // 2. Tạo chi tiết phiếu nhập (sản phẩm, giá, thuế, số lượng)
            foreach ($request->chiTiet as $item) {
                $thanhTien = $item['gia_nhap'] * $item['so_luong_nhap'];
                $tongTien += $thanhTien;

                ChiTietPhieuNhapKho::create([
                    'phieu_nhap_id' => $phieuNhap->id,
                    'san_pham_id' => $item['san_pham_id'],
                    'so_luong_nhap' => $item['so_luong_nhap'],
                    'gia_nhap' => $item['gia_nhap'],
                    'thue_nhap' => $item['thue_nhap'] ?? 0,
                ]);

                // Nếu trạng thái là "da_nhap", cập nhật tồn kho
                if ($request->trang_thai === 'da_nhap') {
                    $sanPham = SanPham::find($item['san_pham_id']);
                    if ($sanPham) {
                        $sanPham->soLuongTon += $item['so_luong_nhap'];
                        $sanPham->save();
                    }
                }
            }

            // 3. Cập nhật lại tổng tiền
            $phieuNhap->tong_tien_nhap = $tongTien;
            $phieuNhap->save();

            DB::commit();

            return response()->json([
                'message' => 'Tạo phiếu nhập kho thành công',
                'data' => $phieuNhap
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Lấy chi tiết một phiếu nhập kho (kèm chi tiết sản phẩm)
     */
    public function show($id)
    {
        $phieu = PhieuNhapKho::with('chiTiet.sanPham', 'nhaCungCap')->findOrFail($id);
        return response()->json($phieu);
    }

    /**
     * Cập nhật thông tin phiếu (chỉ cho phép cập nhật nếu đang là phiếu_tạm)
     */
    public function update(Request $request, $id)
    {
        DB::beginTransaction();

        try {
            $phieuNhap = PhieuNhapKho::find($id);

            if (!$phieuNhap) {
                return response()->json([
                    'error' => "Không tìm thấy phiếu nhập kho có ID = $id"
                ], 404);
            }


            // Lấy chi tiết cũ để rollback tồn kho nếu trạng thái trước là "da_nhap"
            $chiTietCu = ChiTietPhieuNhapKho::where('phieu_nhap_id', $phieuNhap->id)->get();

            if ($phieuNhap->trang_thai === 'da_nhap') {
                // Trả lại số lượng cũ cho tồn kho
                foreach ($chiTietCu as $ct) {
                    $sanPham = SanPham::find($ct->san_pham_id);
                    if ($sanPham) {
                        $sanPham->soLuongTon -= $ct->so_luong_nhap;
                        $sanPham->save();
                    }
                }
            }

            // Xóa chi tiết cũ
            ChiTietPhieuNhapKho::where('phieu_nhap_id', $phieuNhap->id)->delete();

            // Cập nhật lại thông tin phiếu
            $phieuNhap->update([
                'ngay_nhap' => $request->ngay_nhap,
                'ghi_chu' => $request->ghi_chu,
                'trang_thai' => $request->trang_thai
            ]);

            $tongTien = 0;

            foreach ($request->chiTiet as $item) {
                $thanhTien = $item['so_luong_nhap'] * $item['gia_nhap'];
                $tongTien += $thanhTien;

                // Thêm mới chi tiết
                ChiTietPhieuNhapKho::create([
                    'phieu_nhap_id' => $phieuNhap->id,
                    'san_pham_id' => $item['san_pham_id'],
                    'so_luong_nhap' => $item['so_luong_nhap'],
                    'gia_nhap' => $item['gia_nhap'],
                    'thue_nhap' => $item['thue_nhap'] ?? 0,
                ]);

                // Nếu trạng thái mới là da_nhap, cập nhật tồn kho
                if ($request->trang_thai === 'da_nhap') {
                    $sanPham = SanPham::find($item['san_pham_id']);
                    if ($sanPham) {
                        $sanPham->soLuongTon += $item['so_luong_nhap'];
                        $sanPham->save();
                    }
                }
            }

            $phieuNhap->tong_tien_nhap = $tongTien;
            $phieuNhap->save();

            DB::commit();

            return response()->json([
                'message' => 'Cập nhật phiếu nhập kho thành công',
                'data' => $phieuNhap
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Xác nhận nhập kho: cập nhật tồn kho + chuyển trạng thái sang "da_nhap"
     */
    public function xacNhanNhapKho($id)
    {
        DB::beginTransaction();

        try {
            $phieu = PhieuNhapKho::with('chiTiet')->find($id);
            if (!$phieu || $phieu->trang_thai !== 'phieu_tam') {
                return response()->json(['error' => 'Phiếu không tồn tại hoặc không được phép thao tác'], 404);
            }
            // Cộng số lượng sản phẩm vào kho
            foreach ($phieu->chiTiet as $item) {
                $sanPham = SanPham::find($item->san_pham_id);
                if ($sanPham) {
                    $sanPham->soLuongTon += $item->so_luong_nhap;
                    $sanPham->save();
                }
            }

            // Đánh dấu phiếu đã nhập
            $phieu->update(['trang_thai' => 'da_nhap']);

            DB::commit();
            return response()->json(['message' => 'Đã xác nhận nhập kho']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Xóa phiếu nhập kho (chỉ được xóa nếu chưa xác nhận)
     */
    public function destroy($id)
    {
        $phieu = PhieuNhapKho::where('id', $id)
            ->where('trang_thai', 'phieu_tam')
            ->first();

        if (!$phieu) {
            return response()->json(['error' => 'Không tìm thấy phiếu nhập kho hoặc phiếu không ở trạng thái tạm'], 404);
        }

        // Xóa chi tiết trước rồi mới xóa phiếu
        $phieu->chiTiet()->delete();
        $phieu->delete();

        return response()->json(['message' => 'Đã xóa phiếu nhập kho']);
    }

}
