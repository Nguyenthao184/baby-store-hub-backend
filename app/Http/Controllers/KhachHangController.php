<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\KhachHang;
use Illuminate\Support\Facades\DB;

class KhachHangController extends Controller
{
    public function timKiem(Request $request)
    {
        $keyword = $request->query('q');

        $khachHangs = DB::table('KhachHang')
            ->where('hoTen', 'like', '%' . $keyword . '%')
            ->orWhere('sdt', 'like', '%' . $keyword . '%')
            ->select('id', 'hoTen', 'sdt', 'email', 'diaChi')
            ->get();

        return response()->json($khachHangs);
    }

    public function themKhachHang(Request $request)
    {
        $validated = $request->validate([
            'hoTen' => 'required|string|max:255',
            'sdt' => 'required|string|max:15|unique:KhachHang,sdt',
        ]);

        $id = DB::table('KhachHang')->insertGetId([
            'hoTen' => $validated['hoTen'],
            'sdt' => $validated['sdt'],
            'email' => null,
            'diaChi' => null,
            'ngaySinh' => null,
            'taiKhoan_id' => null,
        ]);

        return response()->json([
            'message' => 'Thêm khách hàng thành công',
            'id' => $id,
        ]);
    }
}
