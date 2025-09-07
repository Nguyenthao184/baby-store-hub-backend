<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\KhachHang;
use App\Http\Requests\KhachHang\UpdateKhachHangRequest;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class KhachHangProfileController extends Controller
{
    // Lấy hồ sơ cá nhân
    public function show(Request $request)
    {
        $kh = KhachHang::where('taiKhoan_id', $request->user()->id)->firstOrFail();
        return response()->json($kh);
    }

    // Cập nhật hồ sơ
    public function update(UpdateKhachHangRequest $request)
    {
        $kh = KhachHang::where('taiKhoan_id', $request->user()->id)->firstOrFail();
        $kh->update($request->only(['hoTen','email','diaChi','ngaySinh','sdt']));
        return response()->json([
            'message' => 'Cập nhật hồ sơ thành công',
            'data'    => $kh
        ]);
    }

    // Thay avatar
    public function updateAvatar(UpdateKhachHangRequest $request)
    {
        $kh = KhachHang::where('taiKhoan_id', $request->user()->id)->firstOrFail();

        if ($kh->avatar && Storage::disk('public')->exists($kh->avatar)) {
            Storage::disk('public')->delete($kh->avatar);
        }

        $path = $request->file('avatar')->store('avatars', 'public');
        $kh->update(['avatar' => $path]);

        return response()->json([
            'message' => 'Cập nhật avatar thành công',
            'avatar'  => Storage::disk('public')->url($path),
            'data'    => $kh
        ]);
    }

    // Xoá avatar
    public function destroyAvatar(Request $request)
    {
        $kh = KhachHang::where('taiKhoan_id', $request->user()->id)->firstOrFail();

        if ($kh->avatar && Storage::disk('public')->exists($kh->avatar)) {
            Storage::disk('public')->delete($kh->avatar);
        }
        $kh->update(['avatar' => null]);

        return response()->json([
            'message' => 'Đã xoá avatar',
            'data'    => $kh
        ]);
    }

    // Đổi mật khẩu
    public function updatePassword(UpdateKhachHangRequest $request)
    {
        $user = $request->user();
        $user->matKhau = Hash::make($request->password);
        $user->save();

        return response()->json([
            'message' => 'Đổi mật khẩu thành công'
        ]);
    }
}
