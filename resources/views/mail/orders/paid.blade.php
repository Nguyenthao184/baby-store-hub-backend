<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8" />
  <meta name="color-scheme" content="light only" />
  <title>Xác nhận thanh toán</title>
</head>
<body style="margin:0;padding:0;background:#EFF2F8;font-family:Arial,Helvetica,sans-serif;color:#1f2937;">
  <table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="background:#EFF2F8;">
    <tr>
      <td style="padding:24px 12px;">

        <table role="presentation" cellpadding="0" cellspacing="0" width="600"
               style="margin:0 auto;max-width:600px;width:100%;background:#ffffff;border-radius:16px;overflow:hidden;box-shadow:0 6px 18px rgba(0,0,0,.06);">

          <!-- Header -->
          <tr>
            <td style="text-align:center;background:#2B92E4;padding:24px 24px;">
              <h1 style="margin:14px 0 0 0;font-size:22px;line-height:1.3;color:#ffffff;text-align:center;">
                CẢM ƠN BẠN ĐÃ THANH TOÁN
              </h1>
            </td>
          </tr>

          <!-- Thông tin đơn hàng -->
          <tr>
            <td style="padding:20px 24px 8px 24px;">
              <div style="display:inline-block;background:#F8D0D2;color:#8a3b45;padding:6px 10px;border-radius:999px;font-weight:700;font-size:12px;">📦 Thông tin đơn hàng</div>

              @php
                $maDon   = $order->ma_don_hang ?? $order->id;
                $tongTT  = (float)($invoice->tong_thanh_toan ?? $order->tong_thanh_toan ?? 0);
                $pttt    = strtoupper($order->phuong_thuc_thanh_toan ?? 'COD');
                $fmtVnd  = fn($n) => number_format((float)$n, 0, ',', '.').' đ';
              @endphp

              <div style="padding:6px 0;font-size:14px;"><strong style="color:#111827;">Mã đơn:</strong> <span style="color:#374151;">{{ $maDon }}</span></div>
              <div style="padding:6px 0;font-size:14px;"><strong style="color:#111827;">Tổng thanh toán:</strong> <span style="color:#374151;">{{ $fmtVnd($tongTT) }}</span></div>
              <div style="padding:6px 0 0;font-size:14px;"><strong style="color:#111827;">Phương thức:</strong> <span style="color:#374151;">{{ $pttt }}</span></div>
            </td>
          </tr>

          <!-- Thông tin hóa đơn -->
          <tr>
            <td style="padding:8px 24px 0 24px;">
              <div style="display:inline-block;background:#F8B5C1;color:#7b2f39;padding:6px 10px;border-radius:999px;font-weight:700;font-size:12px;">🧾 Thông tin hoá đơn</div>
              @if(!empty($invoice))
                <div style="padding:6px 0;font-size:14px;"><strong style="color:#111827;">Mã hoá đơn:</strong> <span style="color:#374151;">{{ $invoice->ma_hoa_don }}</span></div>
                <div style="padding:6px 0 0;font-size:14px;"><strong style="color:#111827;">Ngày xuất:</strong>
                  <span style="color:#374151;">{{ optional($invoice->ngay_xuat)->format('d/m/Y H:i') }}</span>
                </div>
              @else
                <div style="padding:6px 0;font-size:14px;color:#6b7280;">(Hoá đơn sẽ được phát hành sau)</div>
              @endif
            </td>
          </tr>

          <!-- Sản phẩm -->
          @if(!empty($items) && count($items))
          <tr>
            <td style="padding:18px 24px;">
              <div style="display:inline-block;background:#EFF2F8;color:#2B92E4;padding:6px 10px;border-radius:999px;font-weight:700;font-size:12px;">🛒 Sản phẩm</div>
              <table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="margin-top:10px;border-collapse:collapse;">
                <thead>
                  <tr>
                    <th style="text-align:left;font-size:12px;text-transform:uppercase;letter-spacing:.4px;padding:10px;border-bottom:2px solid #F0F0F5;background:#F8F9FD;color:#374151;">Sản phẩm</th>
                    <th style="text-align:center;font-size:12px;text-transform:uppercase;letter-spacing:.4px;padding:10px;border-bottom:2px solid #F0F0F5;background:#F8F9FD;color:#374151;">SL</th>
                    <th style="text-align:right;font-size:12px;text-transform:uppercase;letter-spacing:.4px;padding:10px;border-bottom:2px solid #F0F0F5;background:#F8F9FD;color:#374151;">Thành tiền</th>
                  </tr>
                </thead>
                <tbody>
                @foreach($items as $it)
                  <tr>
                    <td style="padding:12px 10px;border-bottom:1px solid #EEF1F7;color:#111827;">
                      {{ $it->ten_san_pham ?? ('SP-'.$it->san_pham_id) }}
                    </td>
                    <td style="text-align:center;padding:12px 10px;border-bottom:1px solid #EEF1F7;color:#111827;">
                      {{ (int)($it->so_luong ?? 1) }}
                    </td>
                    <td style="text-align:right;padding:12px 10px;border-bottom:1px solid #EEF1F7;color:#111827;">
                      {{ $fmtVnd($it->thanh_tien ?? 0) }}
                    </td>
                  </tr>
                @endforeach
                </tbody>
              </table>
            </td>
          </tr>
          @endif

          <!-- CTA -->
          <tr>
            <td style="text-align:center;padding:6px 24px 22px 24px;">
              <a href="{{ $link ?? (rtrim(config('app.frontend_url'), '/').'/orderSuccess?orderId='.urlencode($order->id).'&gw='.$pttt) }}"
                 style="background:#2B92E4;color:#ffffff;text-decoration:none;display:inline-block;padding:12px 20px;border-radius:10px;font-weight:700;">
                Xem đơn hàng
              </a>
              <div style="font-size:12px;color:#6b7280;margin-top:10px;">
                Nếu nút không hoạt động, hãy truy cập đường dẫn trong tài khoản của bạn.
              </div>
            </td>
          </tr>

          <!-- Footer -->
          <tr>
            <td style="background:#EFF2F8;padding:18px 24px;text-align:center;">
              <p style="margin:0 0 6px 0;color:#374151;font-size:14px;">Cần hỗ trợ, vui lòng phản hồi email này.</p>
              <p style="margin:0;color:#6b7280;font-size:13px;">Cảm ơn bạn,<br><strong style="color:#2B92E4;">{{ config('app.name', 'Baby Hub Store') }}</strong></p>
            </td>
          </tr>

        </table>
      </td>
    </tr>
  </table>
</body>
</html>
