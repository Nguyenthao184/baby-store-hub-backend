<?php

namespace App\Mail;

use App\Models\ChiTietDonHang;
use App\Models\DonHang;
use App\Models\HoaDon;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OrderPaidMail extends Mailable
{
public $order;
public $invoice;
public $items;
public $link;

    use Queueable, SerializesModels;
public function __construct($order, $invoice = null, $items = [], $link = null)
{
    $this->order = $order;
    $this->invoice = $invoice;
    $this->items = $items;
    $this->link = $link;
}

 public function build()
    {
        // Dùng 1 view duy nhất, ví dụ: resources/views/emails/order_paid.blade.php
        return $this->subject('Thanh toán thành công: ' . ($this->order->ma_don_hang ?? $this->order->id))
                    ->markdown('mail.orders.paid')   // <— giữ đúng đường dẫn này
                    ->with([
                        'order'   => $this->order,
                        'invoice' => $this->invoice,
                        'items'   => $this->items,
                        'link'    => $this->link,
                    ]);
    }
//     public function envelope()
//     {
//         return new Envelope(
//             subject: 'Thanh toán thành công: ' . ($this->order->ma_don_hang ?? $this->order->id),
//         );
//     }

//     public function content()
//     {
//         // ĐÚNG tên cột FK ở bảng chi tiết (don_hang_id hay donhang_id tùy migration của bạn)
//         $items = ChiTietDonHang::where('don_hang_id', $this->order->id)->get();
//         $fe = rtrim(config('app.frontend_url', config('app.url')), '/');
//         $be = rtrim(config('app.url'), '/'); // BE

//         $customerLink = $fe . '/orders/' . ($this->order->ma_don_hang ?? $this->order->id);
// // nếu FE dùng slug khác, đổi path cho khớp, ví dụ '/order/' hoặc '/don-hang/'
//         return new Content(
//             markdown: 'mail.orders.paid',
//             with: [
//                 'order'        => $this->order,
//                 'invoice'      => $this->invoice,
//                 'items'        => $items,
//                 'link'         => $customerLink,  // nút “Xem đơn hàng”
//                    // (tuỳ chọn) nút cho nội bộ
//             ],
//         );
 //   }
}
