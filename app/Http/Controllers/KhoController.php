<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Kho;
use App\Http\Requests\Kho\StoreKhoRequest;
use App\Http\Requests\Kho\UpdateKhoRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class KhoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            $khos = Kho::with(['sanPhams', 'danhMucs'])->get();

            return response()->json([
                'success' => true,
                'message' => 'Lấy danh sách kho thành công',
                'data' => $khos
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreKhoRequest $request)
    {
        DB::beginTransaction();
        try {
            $kho = Kho::create([
                'tenKho' => $request->tenKho,
                'diaChi' => $request->diaChi,
                'moTa' => $request->moTa,
                'trangThai' => $request->trangThai ?? true,
                'soLuongSanPham' => $request->soLuongSanPham ?? 0,
                'nguoiQuanLy' => $request->nguoiQuanLy,
                'dienTich' => $request->dienTich
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Tạo kho thành công',
                'data' => $kho
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Lỗi: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        try {
            $kho = Kho::with(['sanPhams', 'danhMucs'])->find($id);

            if (!$kho) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không tìm thấy kho'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Lấy thông tin kho thành công',
                'data' => $kho
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateKhoRequest $request, string $id)
    {
        DB::beginTransaction();
        try {
            $kho = Kho::find($id);

            if (!$kho) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không tìm thấy kho'
                ], 404);
            }

            $kho->update($request->only([
                'tenKho', 'diaChi', 'moTa', 'trangThai',
                'soLuongSanPham', 'nguoiQuanLy', 'dienTich'
            ]));

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Cập nhật kho thành công',
                'data' => $kho
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Lỗi: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        DB::beginTransaction();
        try {
            $kho = Kho::find($id);

            if (!$kho) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không tìm thấy kho'
                ], 404);
            }

            // Check if warehouse has products or categories
            $sanPhamCount = $kho->sanPhams()->count();
            $danhMucCount = $kho->danhMucs()->count();

            if ($sanPhamCount > 0 || $danhMucCount > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không thể xóa kho vì còn có sản phẩm hoặc danh mục liên kết'
                ], 400);
            }

            $kho->delete();
            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Xóa kho thành công'
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Lỗi: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get products in a specific warehouse
     */
    public function getSanPhams(string $id)
    {
        try {
            $kho = Kho::find($id);

            if (!$kho) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không tìm thấy kho'
                ], 404);
            }

            $sanPhams = $kho->sanPhams()->with('danhMuc')->get();

            return response()->json([
                'success' => true,
                'message' => 'Lấy sản phẩm trong kho thành công',
                'data' => $sanPhams
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get categories in a specific warehouse
     */
    public function getDanhMucs(string $id)
    {
        try {
            $kho = Kho::find($id);

            if (!$kho) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không tìm thấy kho'
                ], 404);
            }

            $danhMucs = $kho->danhMucs()->with('sanPhams')->get();

            return response()->json([
                'success' => true,
                'message' => 'Lấy danh mục trong kho thành công',
                'data' => $danhMucs
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get warehouse statistics
     */
    public function getThongKe(string $id)
    {
        try {
            $kho = Kho::find($id);

            if (!$kho) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không tìm thấy kho'
                ], 404);
            }

            $thongKe = [
                'tongSanPham' => $kho->sanPhams()->count(),
                'tongDanhMuc' => $kho->danhMucs()->count(),
                'trangThai' => $kho->trangThai ? 'Hoạt động' : 'Ngừng hoạt động',
                'dienTich' => $kho->dienTich,
                'nguoiQuanLy' => $kho->nguoiQuanLy,
                'ngayTao' => $kho->ngayTao,
                'capNhatGanNhat' => $kho->ngayCapNhat
            ];

            return response()->json([
                'success' => true,
                'message' => 'Lấy thống kê kho thành công',
                'data' => $thongKe
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi: ' . $e->getMessage()
            ], 500);
        }
    }
}
