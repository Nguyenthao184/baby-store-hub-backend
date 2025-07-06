<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\TaiKhoan;
use App\Models\KhachHang;
use Illuminate\Support\Facades\DB;

class AuthController extends Controller
{
    public function register(RegisterRequest $request)
    {
        // Lấy dữ liệu đã validate
        $validated = $request->validated();

        DB::beginTransaction();
        try {
            // Tạo tài khoản
            $taiKhoan = TaiKhoan::create([
                'email' => $validated['email'],
                'matKhau' => Hash::make($validated['password']),
                'vaiTro' => 'KhachHang',
                'trangThai' => true
            ]);

            // Tạo thông tin khách hàng
            KhachHang::create([
                'hoTen' => $validated['hoTen'],
                'sdt' => $validated['sdt'] ?? null,
                'email' => $validated['email'],
                'diaChi' => $validated['diaChi'] ?? null,
                'ngaySinh' => $validated['ngaySinh'] ?? null,
                'taiKhoan_id' => $taiKhoan->id
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Đăng ký thành công'
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Lỗi: ' . $e->getMessage()
            ], 500);
        }
    }
    public function login(LoginRequest $request)
    {
        $validated = $request->validated();

        // Tìm tài khoản
        $taiKhoan = TaiKhoan::where('email', $validated['email'])->first();

        if (!$taiKhoan) {
            return response()->json([
                'success' => false,
                'message' => 'Email không tồn tại'
            ], 404);
        }

        // Kiểm tra mật khẩu
        if (!Hash::check($validated['password'], $taiKhoan->matKhau)) {
            return response()->json([
                'success' => false,
                'message' => 'Mật khẩu không chính xác'
            ], 401);
        }

        // Kiểm tra trạng thái
        if (!$taiKhoan->trangThai) {
            return response()->json([
                'success' => false,
                'message' => 'Tài khoản đã bị khóa'
            ], 403);
        }
        $token = $taiKhoan->createToken('auth_token')->plainTextToken;
        // Trả response
        return response()->json([
            'success' => true,
            'message' => 'Đăng nhập thành công',
            'data' => [
                'id' => $taiKhoan->id,
                'token' => $token,
                'vaiTro' => $taiKhoan->vaiTro
            ]
        ]);
    }
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Đăng xuất thành công'
        ]);
    }
}
