<?php

use App\Mail\OrderPaidMail;
use App\Models\DonHang;
use App\Models\HoaDon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Mail;

use function Illuminate\Log\log;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/_dev/test-mail/{id}', function ($id) {
    $order = DonHang::find($id);

    // Nếu DB chưa có đơn, mock dữ liệu để test render mail
    if (!$order) {
        $order = new DonHang();
        $order->id = $id;
        $order->ma_don_hang = 'DH-TEST-0001';
        $order->ten_nguoi_nhan = 'Test User';
        $order->so_dien_thoai = '0900000000';
        $order->tong_thanh_toan = 123456;
        $order->trang_thai = 'paid';
        $order->phuong_thuc_thanh_toan = 'cod';
        $order->ngay_tao = now();
    }

    $invoice = HoaDon::where('don_hang_id', $order->id)->latest()->first(); // có thể null
    Mail::to(request('to', 'khach@example.com'))->send(new OrderPaidMail($order, $invoice));

    return 'OK — mail đã render (xem log nếu MAIL_MAILER=log)';
});