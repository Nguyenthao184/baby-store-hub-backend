<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DanhMucController;
use App\Http\Controllers\SanPhamController;
use App\Http\Controllers\KhoController;
use App\Http\Controllers\DonHangController;
use App\Http\Controllers\KhachHangController;
use App\Http\Controllers\HoaDonController;
use App\Http\Controllers\NhaCungCapController;
use App\Http\Controllers\PhieuKiemKhoController;
use App\Http\Controllers\PhieuNhapKhoController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Auth routes
Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login']);
Route::post('/auth/logout', [AuthController::class, 'logout']);

Route::get('khachHang/san-pham', [SanPhamController::class, 'index']); // Lấy danh sách sản phẩm
Route::get('khachHang/danh-muc', [DanhMucController::class, 'index']); // Lấy danh sách danh mục
Route::get('khachHang/danh-muc/{danhMucId}/san-pham', [SanPhamController::class, 'getByCategory']); // Lấy sản phẩm theo danh mục
Route::get('khachHang/san-pham/{id}', [SanPhamController::class, 'show']); // Lấy chi tiết sản phẩm
Route::post('/khachHang/san-pham/tim-kiem', [SanPhamController::class, 'search']); //Tìm sản phẩm


Route::middleware(['authRole:Admin,QuanLyCuaHang'])->group(function () {
// DanhMuc (Categories) CRUD routes
Route::get('/danh-muc', [DanhMucController::class, 'index']); // Lấy danh sách danh mục
Route::post('/danh-muc', [DanhMucController::class, 'store']); // Tạo danh mục
Route::get('/danh-muc/{id}', [DanhMucController::class, 'show']); // Lấy chi tiết danh mục
Route::post('/danh-muc/{id}', [DanhMucController::class, 'update']); // Cập nhật danh mục
Route::delete('/danh-muc/{id}', [DanhMucController::class, 'destroy']); // Xóa danh mục

// SanPham (Products) CRUD routes
Route::get('/san-pham', [SanPhamController::class, 'index']); // Lấy danh sách sản phẩm
Route::post('/san-pham', [SanPhamController::class, 'store']); // Tạo sản phẩm
Route::get('/san-pham/{id}', [SanPhamController::class, 'show']); // Lấy chi tiết sản phẩm
Route::post('/san-pham/{id}', [SanPhamController::class, 'update']); // Cập nhật sản phẩm
Route::delete('/san-pham/{id}', [SanPhamController::class, 'destroy']); // Xóa sản phẩm
Route::post('/san-pham/change-noi-bat/{id}', [SanPhamController::class, 'changeNoiBat']); // Thay đổi trạng thái nổi bật của sản phẩm

// Additional route to get products by category
Route::get('/danh-muc/{danhMucId}/san-pham', [SanPhamController::class, 'getByCategory']); // Lấy sản phẩm theo danh mục

// Additional route to get products by warehouse
Route::get('/san-pham/kho/{khoId}', [SanPhamController::class, 'getByWarehouse']); // Lấy sản phẩm theo kho

// Kho (Warehouse) CRUD routes
// Route::get('/kho', [KhoController::class, 'index']); // Lấy danh sách kho
// Route::post('/kho', [KhoController::class, 'store']);
// Route::get('/kho/{id}', [KhoController::class, 'show']);
// Route::post('/kho/{id}', [KhoController::class, 'update']);
// Route::delete('/kho/{id}', [KhoController::class, 'destroy']);

// // Additional routes for warehouse management
// Route::get('/kho/{id}/san-pham', [KhoController::class, 'getSanPhams']); // Lấy sản phẩm theo kho
// Route::get('/kho/{id}/danh-muc', [KhoController::class, 'getDanhMucs']); // Lấy danh mục theo kho
// Route::get('/kho/{id}/thong-ke', [KhoController::class, 'getThongKe']); // Lấy thống kê theo kho

//DonHang (Products) CRUD routes
Route::post('/ban-hang/san-pham', [SanPhamController::class, 'search']); //Tìm sản phẩm
Route::post('/ban-hang/tao-don', [DonHangController::class, 'taoDon']); //Tạo đơn hàng
Route::get('/ban-hang/khach-hang', [KhachHangController::class, 'timKiem']); //Tìm khách hàng
Route::post('/ban-hang/them-khach-hang', [KhachHangController::class, 'themKhachHang']); //Thêm khách hàng
Route::post('/thanh-toan', [DonHangController::class, 'thanhToan']); //Thanh toán

//Khách hàng
Route::get('/khach-hang', [KhachHangController::class, 'timKiem']);
Route::post('/khach-hang', [KhachHangController::class, 'themKhachHang']);

//HoaDon
Route::prefix('hoa-don')->group(function () {
    Route::get('/', [HoaDonController::class, 'index']); // lọc & tìm kiếm
    Route::put('/{id}', [HoaDonController::class, 'update']); // cập nhật
    Route::get('/{id}', [HoaDonController::class, 'show']); // lấy chi tiết hóa đơn
    Route::delete('/{id}', [HoaDonController::class, 'destroy']); // xóa
});

//NCC () CRUD routes
Route::get('/nha-cung-cap', [NhaCungCapController::class, 'index']);
Route::post('/nha-cung-cap', [NhaCungCapController::class, 'store']);
Route::get('/nha-cung-cap/{id}', [NhaCungCapController::class, 'show']);
Route::post('/nha-cung-cap/{id}', [NhaCungCapController::class, 'update']);
Route::delete('/nha-cung-cap/{id}', [NhaCungCapController::class, 'destroy']);

//PhieuKiemKho
Route::get('/phieu-kiem-kho', [PhieuKiemKhoController::class, 'index']);
Route::get('/phieu-kiem-kho/{id}', [PhieuKiemKhoController::class, 'show']);
Route::post('/phieu-kiem-kho', [PhieuKiemKhoController::class, 'store']);
Route::post('/phieu-kiem-kho/{id}', [PhieuKiemKhoController::class, 'update']);
Route::delete('/phieu-kiem-kho/{id}', [PhieuKiemKhoController::class, 'destroy']);

Route::post('/phieu-kiem-kho/{id}/add-detail', [PhieuKiemKhoController::class, 'addDetail']);
Route::delete('/phieu-kiem-kho/chi-tiet/{id}', [PhieuKiemKhoController::class, 'deleteDetail']);
Route::post('/phieu-kiem-kho/{id}/can-bang', [PhieuKiemKhoController::class, 'canBang']);


});


