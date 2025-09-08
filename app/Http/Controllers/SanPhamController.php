<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\SanPham;
use App\Models\DanhMuc;
use App\Models\Kho;
use App\Http\Requests\SanPham\StoreSanPhamRequest;
use App\Http\Requests\SanPham\UpdateSanPhamRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class SanPhamController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            $sanPhams = SanPham::with(['danhMuc'])->get();

            return response()->json([
                'success' => true,
                'message' => 'Lấy danh sách sản phẩm thành công',
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
     * Store a newly created resource in storage.
     */
    public function store(StoreSanPhamRequest $request)
    {
        DB::beginTransaction();
        try {
            $hinhAnhPath = null;

            // Xử lý upload hình ảnh
            if ($request->hasFile('hinhAnh')) {
                $file = $request->file('hinhAnh');
                $fileName = time() . '_' . $file->getClientOriginalName();
                $hinhAnhPath = $file->storeAs('san_pham', $fileName, 'public');
            }

            $sanPham = SanPham::create([
                'tenSanPham' => $request->tenSanPham,
                'maSKU' => $request->maSKU,
                'VAT' => $request->VAT ?? 0,
                'giaBan' => $request->giaBan ?? 0,
                'soLuongTon' => $request->soLuongTon ?? 0,
                'moTa' => $request->moTa,
                'danhMuc_id' => $request->danhMuc_id,
                //'kho_id' => $request->kho_id,
                'hinhAnh' => $hinhAnhPath
            ]);

            // Tăng số lượng sản phẩm cho danh mục
            $danhMuc = DanhMuc::find($request->danhMuc_id);
            if ($danhMuc) {
                $danhMuc->increment('soLuongSanPham');
            }

            // Tăng số lượng sản phẩm cho kho (nếu có)
            // if ($request->kho_id) {
            //     $kho = Kho::find($request->kho_id);
            //     if ($kho) {
            //         $kho->increment('soLuongSanPham');
            //     }
            // }

            DB::commit();

            $sanPham->load(['danhMuc']);

            return response()->json([
                'success' => true,
                'message' => 'Tạo sản phẩm thành công',
                'data' => $sanPham
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
            $sanPham = SanPham::with(['danhMuc'])->find($id);

            if (!$sanPham) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không tìm thấy sản phẩm'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Lấy thông tin sản phẩm thành công',
                'data' => $sanPham
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
    public function update(UpdateSanPhamRequest $request, string $id)
    {
        DB::beginTransaction();
        try {
            $sanPham = SanPham::find($id);

            if (!$sanPham) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không tìm thấy sản phẩm'
                ], 404);
            }

            $oldDanhMucId = $sanPham->danhMuc_id;
            $newDanhMucId = $request->danhMuc_id;
        

            $updateData = $request->only([
                'maSanPham',
                'tenSanPham',
                'maSKU',
                'VAT',
                'giaBan',
                'soLuongTon',
                'moTa',
                'danhMuc_id'
            ]);

            // Xử lý upload hình ảnh mới
            if ($request->hasFile('hinhAnh')) {
                // Xóa hình ảnh cũ nếu có
                if ($sanPham->hinhAnh && Storage::disk('public')->exists($sanPham->hinhAnh)) {
                    Storage::disk('public')->delete($sanPham->hinhAnh);
                }

                // Upload hình ảnh mới
                $file = $request->file('hinhAnh');
                $fileName = time() . '_' . $file->getClientOriginalName();
                $hinhAnhPath = $file->storeAs('san_pham', $fileName, 'public');
                $updateData['hinhAnh'] = $hinhAnhPath;
            }

            $sanPham->update($updateData);

            // Cập nhật số lượng sản phẩm cho danh mục khi thay đổi
            if ($newDanhMucId && $oldDanhMucId !== $newDanhMucId) {
                $oldDanhMuc = DanhMuc::find($oldDanhMucId);
                if ($oldDanhMuc && $oldDanhMuc->soLuongSanPham > 0) {
                    $oldDanhMuc->decrement('soLuongSanPham');
                }
                $newDanhMuc = DanhMuc::find($newDanhMucId);
                if ($newDanhMuc) {
                    $newDanhMuc->increment('soLuongSanPham');
                }
            }

            // Cập nhật số lượng sản phẩm cho kho khi thay đổi
            // if ($oldKhoId !== $newKhoId) {
            //     // Giảm số lượng ở kho cũ
            //     if ($oldKhoId) {
            //         $oldKho = Kho::find($oldKhoId);
            //         if ($oldKho && $oldKho->soLuongSanPham > 0) {
            //             $oldKho->decrement('soLuongSanPham');
            //         }
            //     }
            //     // Tăng số lượng ở kho mới
            //     if ($newKhoId) {
            //         $newKho = Kho::find($newKhoId);
            //         if ($newKho) {
            //             $newKho->increment('soLuongSanPham');
            //         }
            //     }
            // }

            DB::commit();

            $sanPham->load(['danhMuc']);

            return response()->json([
                'success' => true,
                'message' => 'Cập nhật sản phẩm thành công',
                'data' => $sanPham
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
            $sanPham = SanPham::find($id);

            if (!$sanPham) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không tìm thấy sản phẩm'
                ], 404);
            }

            $danhMucId = $sanPham->danhMuc_id;
            // $khoId = $sanPham->kho_id;

            // Xóa hình ảnh nếu có
            if ($sanPham->hinhAnh && Storage::disk('public')->exists($sanPham->hinhAnh)) {
                Storage::disk('public')->delete($sanPham->hinhAnh);
            }

            $sanPham->delete();

            // Giảm số lượng sản phẩm cho danh mục
            $danhMuc = DanhMuc::find($danhMucId);
            if ($danhMuc && $danhMuc->soLuongSanPham > 0) {
                $danhMuc->decrement('soLuongSanPham');
            }

            // Giảm số lượng sản phẩm cho kho
            // if ($khoId) {
            //     $kho = Kho::find($khoId);
            //     if ($kho && $kho->soLuongSanPham > 0) {
            //         $kho->decrement('soLuongSanPham');
            //     }
            // }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Xóa sản phẩm thành công'
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
     * Get products by category
     */
    public function getByCategory(string $danhMucId)
    {
        try {
            $danhMuc = DanhMuc::find($danhMucId);

            if (!$danhMuc) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không tìm thấy danh mục'
                ], 404);
            }

            $sanPhams = DB::table('SanPham')
                ->where('SanPham.danhMuc_id', $danhMucId)
                ->join('DanhMuc', 'SanPham.danhMuc_id', '=', 'DanhMuc.id')
                ->leftJoin('NhaCungCap', 'DanhMuc.nhaCungCap', '=', 'NhaCungCap.id')
                ->select('SanPham.*', 'DanhMuc.tenDanhMuc', 'NhaCungCap.tenNhaCungCap')
                ->get();

            return response()->json([
                'success' => true,
                'message' => 'Lấy sản phẩm theo danh mục thành công',
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
     * Get products by warehouse
     */
    // public function getByWarehouse(string $khoId)
    // {
    //     try {
    //         $kho = Kho::find($khoId);

    //         if (!$kho) {
    //             return response()->json([
    //                 'success' => false,
    //                 'message' => 'Không tìm thấy kho'
    //             ], 404);
    //         }

    //         $sanPhams = SanPham::where('kho_id', $khoId)->with(['danhMuc', 'kho'])->get();

    //         return response()->json([
    //             'success' => true,
    //             'message' => 'Lấy sản phẩm theo kho thành công',
    //             'data' => $sanPhams
    //         ], 200);

    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Lỗi: ' . $e->getMessage()
    //         ], 500);
    //     }
    // }

    public function search(Request $request)
    {
        try {

            $noiDungTim = '%' . $request->noiDungTim . '%';
            if ($noiDungTim === '') {
                return response()->json([
                    'status' => false,
                    'message' => 'Vui lòng nhập nội dung tìm kiếm'
                ])->setStatusCode(400);
            }
            $query = DB::table('SanPham')
                ->where(function ($subQuery) use ($noiDungTim) {
                    $subQuery->where('tenSanPham', 'like', $noiDungTim)
                        ->orWhere('maSanPham', 'like', $noiDungTim)
                        ->orWhere('maSKU', 'like', $noiDungTim);
                })
                ->select('id', 'tenSanPham', 'maSKU', 'hinhAnh', 'moTa', 'giaBan', 'soLuongTon', 'VAT')
                ->limit(20)
                ->get();

            return response()->json($query);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi: ' . $e->getMessage()
            ], 500);
        }
    }


    public function changeNoiBat($id)
    {
        $sanPham = SanPham::find($id);
        if ($sanPham) {
            $is_noi_bat = $sanPham->is_noi_bat == 1 ? 0 : 1;
            $sanPham->update([
                'is_noi_bat' => $is_noi_bat
            ]);
            return response()->json([
                'status' => true,
                'message' => "Đã đổi tình trạng sản phẩm " . $sanPham->tenSanPham . " thành công.",
            ]);
        } else {
            return response()->json([
                'status' => false,
                'message' => 'Sản phẩm không tồn tại.'
            ]);
        }
    }
}
