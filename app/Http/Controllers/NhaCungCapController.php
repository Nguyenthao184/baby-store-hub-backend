<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\NhaCungCap\StoreNhaCungCapRequest;
use App\Http\Requests\NhaCungCap\UpdateNhaCungCapRequest;
use App\Models\NhaCungCap;
use Illuminate\Support\Carbon;

class NhaCungCapController extends Controller
{
     public function index()
    {
        $list = NhaCungCap::orderBy('ngayTao', 'desc')->get();

        return response()->json([
            'success' => true,
            'data' => $list
        ]);
    }
    public function store(StoreNhaCungCapRequest $request)
    {
        $data = $request->validated();
        $data['ngayTao'] = now();

        $ncc = NhaCungCap::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Tạo nhà cung cấp thành công',
            'data' => $ncc
        ]);
    }

    public function update(UpdateNhaCungCapRequest $request, $id)
    {
        $ncc = NhaCungCap::find($id);

        if (!$ncc) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy nhà cung cấp'
            ], 404);
        }

        $data = $request->validated();
        $data['ngayCapNhat'] = now();

        $ncc->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật thành công',
            'data' => $ncc
        ]);
    }
        public function show($id)
    {
        $ncc = NhaCungCap::find($id);

        if (!$ncc) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy nhà cung cấp'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $ncc
        ]);
    }

    public function destroy($id)
    {
        $ncc = NhaCungCap::find($id);

        if (!$ncc) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy nhà cung cấp'
            ], 404);
        }

        $ncc->delete();

        return response()->json([
            'success' => true,
            'message' => 'Xóa thành công'
        ]);
    }
}
