<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DanhMucController;
use App\Http\Controllers\SanPhamController;
use App\Http\Controllers\DonHangController;
use App\Http\Controllers\GioHangController;
use App\Http\Controllers\KhachHangController;
use App\Http\Controllers\HoaDonController;
use App\Http\Controllers\NhaCungCapController;
use App\Http\Controllers\PhieuKiemKhoController;
use App\Http\Controllers\PhieuNhapKhoController;
use App\Http\Controllers\KhachHangProfileController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\GhnWebhookController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\DonMuaController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

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
Route::post('/thanh-toan', [DonHangController::class, 'thanhToan']); //Thanh toán
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

//PhieuNhapKho
Route::prefix('phieu-nhap-kho')->group(function () {    
    Route::get('/', [PhieuNhapKhoController::class, 'index']); // Danh sách tất cả phiếu nhập (kèm chi tiết)    
    Route::get('/loc', [PhieuNhapKhoController::class, 'loc']); //lọc
    Route::post('/', [PhieuNhapKhoController::class, 'store']); // Tạo mới phiếu nhập (phiếu tạm hoặc đã nhập)
    Route::get('/{id}', [PhieuNhapKhoController::class, 'show']); // Lấy chi tiết 1 phiếu nhập
    Route::put('/{id}', [PhieuNhapKhoController::class, 'update']); // Cập nhật phiếu nhập (chỉ khi là phiếu tạm)
    Route::post('/{id}/xac-nhan', [PhieuNhapKhoController::class, 'xacNhanNhapKho']); // Xác nhận nhập kho (cộng vào tồn kho, chuyển trạng thái)
    Route::put('/{id}/huy', [PhieuNhapKhoController::class, 'huyPhieuNhap']); // Hủy phiếu nhập
});

Route::post('/orders/{id}/to-shipping', [DonHangController::class, 'moveToReadyForPickup']); // Chuyển trạng thái CHO_XU_LY -> CHO_LAY_HANG
Route::post('/orders/{id}/to-shipping', [DonHangController::class, 'moveToShipping']); // Chuyển trạng thái CHO_LAY_HANG -> DANG_GIAO_HANG

});

Route::middleware(['auth:sanctum','authRole:KhachHang'])->group(function () { 
    // Profile routes for KhachHang 
    Route::get('/khach-hang/profile', [KhachHangProfileController::class, 'show']); // Lấy hồ sơ cá nhân 
    Route::post('/khach-hang/profile', [KhachHangProfileController::class, 'update']); // Cập nhật hồ sơ 
    Route::post('/khach-hang/profile/avatar', [KhachHangProfileController::class, 'updateAvatar']); // Thay avatar 
    Route::delete('/khach-hang/profile/avatar', [KhachHangProfileController::class, 'destroyAvatar']); // Xoá avatar 
    Route::post('/khach-hang/profile/password', [KhachHangProfileController::class, 'updatePassword']); // Đổi mật khẩu 

    Route::get('/gio-hang', [GioHangController::class, 'xem']);
    Route::post('/gio-hang/them', [GioHangController::class, 'them']);
    Route::post('/gio-hang/cap-nhat/{sanPhamId}', [GioHangController::class, 'capNhat']);
    Route::delete('/gio-hang/xoa/{sanPhamId}', [GioHangController::class, 'xoa']);
    Route::delete('/gio-hang/xoa-het', [GioHangController::class, 'xoaHet']);

    Route::post('/gio-hang/tinh-tong', [GioHangController::class, 'tinhTong']);
    Route::post('/checkout/dat-hang', [CheckoutController::class, 'datHang']);
    Route::get('/thanh-toan/{donhangid}/trang-thai', [PaymentController::class, 'status']);
    Route::post('/checkout/mua-ngay', [CheckoutController::class, 'muaNgay']);

    // Đơn mua (của khách)
    Route::post('/don-mua/{id}/cancel', [DonMuaController::class, 'cancelByCustomer']); // Khách hàng hủy đơn hàng
    Route::get('/don-mua',           [DonMuaController::class, 'index']);       // lọc 
    Route::post('/don-mua/{id}/reorder', [DonMuaController::class, 'reorder']); // mua lại
});
// Webhook GHN (public)
Route::post('/webhooks/ghn', [GhnWebhookController::class, 'handle']);
Route::get('/vnpay/return', [CheckoutController::class, 'vnpayReturn']); 
// Route::post('/momo_payment', [CheckoutController::class, 'momoPayment']);
Route::match(['GET','POST'], '/momo/return', [CheckoutController::class, 'momoReturn'])->name('momo.return'); 
Route::match(['GET','POST'], '/momo/ipn',    [CheckoutController::class, 'momoIpn'])->name('momo.ipn');       
Route::get('/orders/{id}', [CheckoutController::class, 'showById']);             

