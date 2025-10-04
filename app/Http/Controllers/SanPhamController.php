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
                'hinhAnh' => $hinhAnhPath,
                'is_noi_bat' => $request->is_noi_bat ?? false,
                'flash_sale' => $request->flash_sale ?? 0
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
                'danhMuc_id',
                'is_noi_bat',
                'flash_sale',
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
            $keyword = trim((string) $request->noiDungTim);
            if ($keyword === '') {
                return response()->json([
                    'status'  => false,
                    'message' => 'Vui lòng nhập nội dung tìm kiếm'
                ], 400);
            }
            $like = "%{$keyword}%";

            // Lấy thêm cột flash_sale
            $rows = DB::table('SanPham')
                ->where(function ($q) use ($like) {
                    $q->where('tenSanPham', 'like', $like)
                    ->orWhere('maSanPham', 'like', $like)
                    ->orWhere('maSKU', 'like', $like);
                })
                ->select('id', 'tenSanPham', 'maSKU', 'hinhAnh', 'moTa', 'giaBan', 'soLuongTon', 'VAT', 'flash_sale')
                ->limit(20)
                ->get();

            // Làm tròn tiền về đồng (half up)
            $money = fn($v) => (int) round((float) $v, 0, PHP_ROUND_HALF_UP);

            // Tính giá hiển thị: giá sau VAT và sau flash sale
            $data = $rows->map(function ($sp) use ($money) {
                $giaBan  = (float) ($sp->giaBan ?? 0);

                // VAT: mặc định 0.08, normalize nếu đang lưu 8 -> 0.08
                $vat = isset($sp->VAT) ? (float) $sp->VAT : 0.08;
                if ($vat > 1) $vat /= 100.0;

                // Flash: normalize 0..1
                $flash = isset($sp->flash_sale) ? (float) $sp->flash_sale : 0.0;
                if ($flash > 1) $flash /= 100.0;

                $giaSauVAT   = $money($giaBan * (1 + $vat));
                $giaHienThi  = $money($giaSauVAT * (1 - $flash));

                return [
                    'id'         => $sp->id,
                    'tenSanPham' => $sp->tenSanPham,
                    'maSKU'      => $sp->maSKU,
                    'hinhAnh'    => $sp->hinhAnh,
                    'moTa'       => $sp->moTa,
                    'soLuongTon' => (int) $sp->soLuongTon,

                    // Thông tin gốc (giữ lại nếu cần)
                    'giaBan'     => $giaBan,
                    'VAT'        => $sp->VAT,
                    'flash_sale' => $sp->flash_sale,

                    // 👇 Giá dùng để bán/hiển thị
                    'gia_sau_vat' => $giaSauVAT,
                    'gia'         => $giaHienThi, // sau VAT & flash sale
                ];
            });

            return response()->json($data);
        } catch (\Throwable $e) {
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
