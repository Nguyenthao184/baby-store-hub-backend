<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\DanhMuc;
use App\Http\Requests\DanhMuc\StoreDanhMucRequest;
use App\Http\Requests\DanhMuc\UpdateDanhMucRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class DanhMucController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            $danhMucs = DB::table('DanhMuc')
                ->leftJoin('NhaCungCap', 'DanhMuc.nhaCungCap', '=', 'NhaCungCap.id')
                ->select(
                    'DanhMuc.*',
                    'NhaCungCap.tenNhaCungCap as tenNhaCungCap'
                )
                ->get();

            return response()->json([
                'success' => true,
                'message' => 'Lấy danh sách danh mục thành công',
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
     * Store a newly created resource in storage.
     */
    public function store(StoreDanhMucRequest $request)
    {
        DB::beginTransaction();
        try {
            $hinhAnhPath = null;

            // Xử lý upload hình ảnh
            if ($request->hasFile('hinhAnh')) {
                $file = $request->file('hinhAnh');
                $fileName = time() . '_' . $file->getClientOriginalName();
                $hinhAnhPath = $file->storeAs('danh_muc', $fileName, 'public');
            }

            $danhMuc = DanhMuc::create([
                'tenDanhMuc' => $request->tenDanhMuc,
                'moTa' => $request->moTa,
                'soLuongSanPham' => $request->soLuongSanPham ?? 0,
                'hinhAnh' => $hinhAnhPath,
                'nhaCungCap' => $request->nhaCungCap,
                // 'idKho' => $request->idKho
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Tạo danh mục thành công',
                'data' => $danhMuc
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
            $danhMuc = DB::table('DanhMuc')
                ->leftJoin('NhaCungCap', 'DanhMuc.nhaCungCap', '=', 'NhaCungCap.id')
                ->select(
                    'DanhMuc.*',
                    'NhaCungCap.tenNhaCungCap as tenNhaCungCap'
                )
                ->where('DanhMuc.id', $id)
                ->first();

            if (!$danhMuc) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không tìm thấy danh mục'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Lấy thông tin danh mục thành công',
                'data' => $danhMuc
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
    public function update(UpdateDanhMucRequest $request, string $id)
    {
        DB::beginTransaction();
        try {
            $danhMuc = DanhMuc::find($id);

            if (!$danhMuc) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không tìm thấy danh mục'
                ], 404);
            }

            $updateData = $request->only([
                'maDanhMuc',
                'tenDanhMuc',
                'moTa',
                'soLuongSanPham',
                'nhaCungCap'
            ]);

            // Xử lý upload hình ảnh mới
            if ($request->hasFile('hinhAnh')) {
                // Xóa hình ảnh cũ nếu có
                if ($danhMuc->hinhAnh && Storage::disk('public')->exists($danhMuc->hinhAnh)) {
                    Storage::disk('public')->delete($danhMuc->hinhAnh);
                }

                // Upload hình ảnh mới
                $file = $request->file('hinhAnh');
                $fileName = time() . '_' . $file->getClientOriginalName();
                $hinhAnhPath = $file->storeAs('danh_muc', $fileName, 'public');
                $updateData['hinhAnh'] = $hinhAnhPath;
            }

            $danhMuc->update($updateData);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Cập nhật danh mục thành công',
                'data' => $danhMuc
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
            $danhMuc = DanhMuc::find($id);

            if (!$danhMuc) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không tìm thấy danh mục'
                ], 404);
            }

            // Check if category has products
            if ($danhMuc->sanPhams()->count() > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không thể xóa danh mục vì còn có sản phẩm'
                ], 400);
            }

            // Xóa hình ảnh nếu có
            if ($danhMuc->hinhAnh && Storage::disk('public')->exists($danhMuc->hinhAnh)) {
                Storage::disk('public')->delete($danhMuc->hinhAnh);
            }

            $danhMuc->delete();
            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Xóa danh mục thành công'
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Lỗi: ' . $e->getMessage()
            ], 500);
        }
    }
}
