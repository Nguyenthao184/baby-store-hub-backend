<?php

namespace App\Http\Controllers;

use App\Http\Requests\KhachHang\StoreKhachHangRequest;
use App\Models\KhachHang;
use Illuminate\Http\Request;

class KhachHangController extends Controller
{
    /**
     * GET /api/khach-hang?q=...
     * Tìm kiếm khách hàng theo họ tên hoặc số điện thoại
     */
    public function timKiem(Request $request)
    {
        $q = trim((string) $request->query('q', ''));

        $query = KhachHang::query()
            ->select(['id', 'hoTen', 'sdt', 'email', 'diaChi']);

        if ($q !== '') {
            $query->where(function ($sub) use ($q) {
                $sub->where('hoTen', 'like', "%{$q}%")
                    ->orWhere('sdt', 'like', "%{$q}%");
            });
        }

        return response()->json($query->get());
    }

    /**
     * POST /api/khach-hang
     * Thêm khách hàng mới (chỉ nhập họ tên và số điện thoại)
     */
    public function themKhachHang(StoreKhachHangRequest $request)
    {
        $data = $request->validated();

        $kh = KhachHang::create([
            'hoTen'       => $data['hoTen'],
            'sdt'         => $data['sdt'],
            'email'       => null,
            'diaChi'      => null,
            'ngaySinh'    => null,
            'avatar'      => null,
            'taiKhoan_id' => null,
        ]);

        return response()->json([
            'message' => 'Thêm khách hàng thành công',
            'id'      => $kh->id,
            'hoTen' => $kh->hoTen,
            'sdt'   => $kh->sdt,
        ], 201);
    }
}
