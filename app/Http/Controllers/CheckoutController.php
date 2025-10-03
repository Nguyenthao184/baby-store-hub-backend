<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SanPham;
use App\Models\ThanhToan;
use App\Models\DonHang;
use App\Models\ChiTietDonHang;
use Illuminate\Support\Facades\Log;
use App\Http\Requests\Checkout\DatHangRequest;
use App\Http\Requests\Checkout\MuaNgayRequest;
use App\Mail\OrderPaidMail;
use App\Models\KhachHang;
use App\Services\GhnService;
use App\Services\GioHangService;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class CheckoutController extends Controller
{
    public function __construct(private GioHangService $gioHang) {}

    private function vnpVerifySignature(array $params, string $hashSecret): bool
    {
        $secureHash = $params['vnp_SecureHash'] ?? null;
        unset($params['vnp_SecureHash'], $params['vnp_SecureHashType']);
        ksort($params);
        $hashData = [];
        foreach ($params as $k => $v) {
            $hashData[] = urlencode($k) . '=' . urlencode($v);
        }
        $hashStr = implode('&', $hashData);
        $calc = hash_hmac('sha512', $hashStr, $hashSecret);
        return $secureHash && hash_equals($calc, (string)$secureHash);
    }

    public function vnpayReturn(Request $request)
    {
        // 1) Payload & verify chữ ký
        $params = $request->query();
        Log::info('VNPAY RETURN payload', $params);

        $hashSecret = config('services.vnpay.hash_secret');
        if (!$this->vnpVerifySignature($params, $hashSecret)) {
            return response()->json(['message' => 'Sai chữ ký (vnp_SecureHash)'], 400);
        }

        // 2) Lấy trường chính
        $txnRef  = trim((string)($params['vnp_TxnRef'] ?? ''));         // = don_hang_id
        $resp    = $params['vnp_ResponseCode']      ?? null;            // 00
        $txnStat = $params['vnp_TransactionStatus'] ?? null;            // 00
        $payId   = $params['vnp_TransactionNo']     ?? null;            // mã GD trên VNPAY

        if ($txnRef === '') {
            return response()->json(['message' => 'Thiếu vnp_TxnRef'], 400);
        }

        // 3) Map trạng thái
        $isSuccess = ($resp === '00' && $txnStat === '00');
        $newStatus = $isSuccess ? 'DA_THANH_TOAN' : 'THAT_BAI';

        // 4) Tìm bản ghi thanh toán theo ma_tham_chieu
        $tt = ThanhToan::query()
            ->where('ma_tham_chieu', $txnRef)           // CHỦ ĐÍCH: trùng với lúc tạo
            ->whereRaw('LOWER(kenh) = ?', ['vnpay'])
            ->orderByDesc('id')
            ->first();

        if (!$tt) {
            Log::warning('Không tìm thấy thanhtoan', ['vnp_TxnRef' => $txnRef]);
            return response()->json(['message' => 'Không tìm thấy bản ghi thanh toán'], 404);
        }

        if ($tt->trang_thai === 'DA_THANH_TOAN') {
            return response()->json(['message' => 'Đã ghi nhận thanh toán trước đó'], 200);
        }

        // 5) Cập nhật thanhtoan (Observer sẽ tự tạo Hóa đơn)
        $tt->update([
            'trang_thai'   => $newStatus,
            'ma_ket_qua'   => $resp,
            'ma_giao_dich' => $payId,
            'thong_diep'     => ($isSuccess
                ? 'Thanh toán thành công'
                : (($resp === '00' && $txnStat === '01')
                    ? 'Chờ ngân hàng xác nhận'
                    : 'Thanh toán thất bại')),
            'raw_return'   => $params,
        ]);


        $fe = config('app.frontend_url');
        return redirect()->away($fe . '/orderSuccess?orderId=' . urlencode($txnRef) . '&gw=vnpay');

        $order = DonHang::with(['chiTiet', 'thanhToan', 'khachHang'])->find($txnRef);
        $items = $order?->chiTiet ?? collect();
        $invoice = null;

        $fe   = rtrim(config('app.frontend_url'), '/');
        $link = $fe . '/orderSuccess?orderId=' . urlencode($txnRef) . '&gw=vnpay';

        // Ưu tiên email KH; nếu không có, tạm gửi về email shop để kiểm tra
        $toEmail = $order?->khachHang?->email ?: config('mail.from.address');

        try {
            if ($toEmail) {
                Mail::to($toEmail)->send(new OrderPaidMail($order, $invoice, $items, $link));
            } else {
                Log::warning('OrderPaidMail: thiếu email khách hàng', ['order_id' => $txnRef]);
            }
        } catch (\Throwable $e) {
            Log::error('Gửi mail thất bại', ['error' => $e->getMessage()]);
        }

        // Redirect về FE
        return redirect()->away($link);
    }

    private function buildVnpayUrl(DonHang $donhang): string
    {
        $vnp_Url        = config('services.vnpay.url');
        $vnp_Returnurl  = config('services.vnpay.return_url');
        $vnp_TmnCode    = config('services.vnpay.tmn_code');
        $vnp_HashSecret = config('services.vnpay.hash_secret');

        // TxnRef = UUID đơn hàng (khớp thanhtoan.don_hang_id)
        $vnp_TxnRef     = (string) $donhang->id;
        $vnp_OrderInfo  = 'Thanh toan don hang ' . $donhang->ma_don_hang;
        $vnp_OrderType  = 'billpayment';
        $vnp_Amount     = ((int) round((float) $donhang->tong_thanh_toan)) * 100;
        $vnp_IpAddr     = request()->ip();
        $vnp_Locale     = 'vn';

        $inputData = [
            "vnp_Version"   => "2.1.0",
            "vnp_TmnCode"   => $vnp_TmnCode,
            "vnp_Amount"    => $vnp_Amount,
            "vnp_Command"   => "pay",
            "vnp_CreateDate" => date('YmdHis'),
            "vnp_CurrCode"  => "VND",
            "vnp_IpAddr"    => $vnp_IpAddr,
            "vnp_Locale"    => $vnp_Locale,
            "vnp_OrderInfo" => $vnp_OrderInfo,
            "vnp_OrderType" => $vnp_OrderType,
            "vnp_ReturnUrl" => $vnp_Returnurl,
            "vnp_TxnRef"    => $vnp_TxnRef,
        ];

        ksort($inputData);
        $query = '';
        $hashdata = '';
        $i = 0;
        foreach ($inputData as $key => $value) {
            if ($i == 1) {
                $hashdata .= '&';
            }
            $hashdata .= urlencode($key) . "=" . urlencode($value);
            $query    .= urlencode($key) . "=" . urlencode($value) . '&';
            $i = 1;
        }
        $vnp_Url = $vnp_Url . "?" . $query;
        if (!empty($vnp_HashSecret)) {
            $vnpSecureHash = hash_hmac('sha512', $hashdata, $vnp_HashSecret);
            $vnp_Url .= 'vnp_SecureHash=' . $vnpSecureHash;
        }
        return $vnp_Url;
    }

    private function genMaDonHang(): string
    {
        // Ví dụ: DH-2025-20250918-000123
        $today = now()->toDateString();           // YYYY-MM-DD
        $ymd   = now()->format('Ymd');            // YYYYMMDD
        $year  = now()->format('Y');

        // Đếm theo ngày có khóa để tránh race (hàm này nên được gọi bên trong 1 transaction)
        $seq = DonHang::whereDate('ngay_tao', $today)
            ->lockForUpdate()
            ->count() + 1;

        return 'DH-' . $year . '-' . $ymd . '-' . str_pad((string)$seq, 6, '0', STR_PAD_LEFT);
    }

    /* ================= MOMO ================= */

    private function buildMomoUrl(DonHang $donhang): ?string
    {
        $endpoint    = 'https://test-payment.momo.vn/v2/gateway/api/create';
        $partnerCode = config('services.momo.partner_code');
        $accessKey   = config('services.momo.access_key');
        $secretKey   = config('services.momo.secret_key');
        $redirectUrl = config('services.momo.redirect_url', route('momo.return'));
        $ipnUrl      = config('services.momo.ipn_url', route('momo.ipn'));

        $amount      = (int) $donhang->tong_thanh_toan;
        $orderId     = (string) $donhang->id;
        $requestId   = (string) now()->timestamp;
        $orderInfo   = 'Thanh toán đơn hàng ' . $donhang->ma_don_hang;
        $requestType = 'payWithATM';
        $extraData   = '';

        $raw = "accessKey={$accessKey}"
            . "&amount={$amount}"
            . "&extraData={$extraData}"
            . "&ipnUrl={$ipnUrl}"
            . "&orderId={$orderId}"
            . "&orderInfo={$orderInfo}"
            . "&partnerCode={$partnerCode}"
            . "&redirectUrl={$redirectUrl}"
            . "&requestId={$requestId}"
            . "&requestType={$requestType}";

        $signature = hash_hmac('sha256', $raw, $secretKey);

        $payload = [
            'partnerCode' => $partnerCode,
            'partnerName' => 'MoMoTest',
            'storeId'     => 'MomoStore',
            'requestId'   => $requestId,
            'amount'      => $amount,
            'orderId'     => $orderId,
            'orderInfo'   => $orderInfo,
            'redirectUrl' => $redirectUrl,
            'ipnUrl'      => $ipnUrl,
            'lang'        => 'vi',
            'extraData'   => $extraData,
            'requestType' => $requestType,
            'signature'   => $signature,
        ];

        $ch = curl_init($endpoint);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
            CURLOPT_POSTFIELDS     => json_encode($payload),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 35,
            CURLOPT_CONNECTTIMEOUT => 15,
        ]);
        $res  = curl_exec($ch);
        curl_close($ch);

        $json = json_decode($res, true);
        if (!is_array($json) || empty($json['payUrl'])) {
            Log::error('MoMo create fail', ['response' => $res]);
            return null;
        }
        return $json['payUrl'];
    }

    public function momoReturn(Request $request)
    {
        $p = $request->all();
        Log::info('MoMo RETURN', $p);

        $orderId    = $p['orderId'] ?? null;
        $resultCode = (int)($p['resultCode'] ?? -1);
        $transId    = $p['transId'] ?? null;

        if ($orderId) {
            $tt = ThanhToan::where('ma_tham_chieu', $orderId)
                ->whereRaw('LOWER(kenh) = ?', ['momo'])
                ->orderByDesc('id')
                ->first();

            if ($tt && $tt->trang_thai !== 'DA_THANH_TOAN') {
                $tt->update([
                    'trang_thai'   => $resultCode === 0 ? 'DA_THANH_TOAN' : 'THAT_BAI',
                    'ma_giao_dich' => $transId,
                    'ma_ket_qua'   => (string)$resultCode,
                    'raw_return'   => $p,
                ]);
            }
        }

        $fe = config('app.frontend_url');
        return redirect()->away($fe . '/orderSuccess?orderId=' . urlencode($orderId) . '&gw=momo');
         $order = DonHang::with(['chiTiet', 'thanhToan', 'khachHang'])->find($txnRef);
        $items = $order?->chiTiet ?? collect();
        $invoice = null;

        $fe   = rtrim(config('app.frontend_url'), '/');
        $link = $fe . '/orderSuccess?orderId=' . urlencode($txnRef) . '&gw=momo';

        // Ưu tiên email KH; nếu không có, tạm gửi về email shop để kiểm tra
        $toEmail = $order?->khachHang?->email ?: config('mail.from.address');

        try {
            if ($toEmail) {
                Mail::to($toEmail)->send(new OrderPaidMail($order, $invoice, $items, $link));
            } else {
                Log::warning('OrderPaidMail: thiếu email khách hàng', ['order_id' => $txnRef]);
            }
        } catch (\Throwable $e) {
            Log::error('Gửi mail thất bại', ['error' => $e->getMessage()]);
        }

        // Redirect về FE
        return redirect()->away($link);
    }

    public function momoIpn(Request $request)
    {
        $p = $request->all();
        Log::info('MoMo IPN', $p);

        $orderId    = $p['orderId'] ?? null;
        $resultCode = (int)($p['resultCode'] ?? -1);
        $transId    = $p['transId'] ?? null;

        if ($orderId) {
            $tt = ThanhToan::where('ma_tham_chieu', $orderId)
                ->whereRaw('LOWER(kenh) = ?', ['momo'])
                ->orderByDesc('id')
                ->first();

            if ($tt && $tt->trang_thai !== 'DA_THANH_TOAN') {
                $tt->update([
                    'trang_thai'   => $resultCode === 0 ? 'DA_THANH_TOAN' : 'THAT_BAI',
                    'ma_giao_dich' => $transId,
                    'ma_ket_qua'   => (string)$resultCode,
                    'raw_ipn'      => $p,
                ]);
            } else {
                Log::warning('MoMo IPN: ThanhToan not found or already paid', ['orderId' => $orderId]);
            }
        }

        return response()->json(['resultCode' => 0, 'message' => 'Confirm Success']);
    }

    public function muaNgay(MuaNgayRequest $request, GhnService $ghn)
    {
        return DB::transaction(function () use ($request, $ghn) {
            $data   = $request->validated();
            $userId = $request->user()->id;
            $kh     = KhachHang::where('taiKhoan_id', $userId)->first();

            // 1) Lấy sản phẩm
            $sp = SanPham::lockForUpdate()->find($data['san_pham_id']);
            if (!$sp) {
                return response()->json(['message' => 'Không tìm thấy sản phẩm'], 404);
            }

            $soLuong = (int) $data['so_luong'];
            if ((int)$sp->soLuongTon < $soLuong) {
                return response()->json(['message' => 'Sản phẩm không đủ tồn kho'], 422);
            }

            // 2) Tính giá theo nghiệp vụ (VAT mặc định 0.08)
            $giaGoc   = (float) ($sp->giaBan ?? 0); // giá gốc = giá bán
            $vatRaw   = $sp->VAT ?? 0.08;
            $vat      = (float) ($vatRaw > 1 ? $vatRaw / 100 : $vatRaw); // 0..1
            $flashRaw = $sp->flash_sale ?? 0;
            $flash    = (float) ($flashRaw > 1 ? $flashRaw / 100 : $flashRaw); // 0..1

            // Đơn giá đã VAT & sau flash — làm tròn theo đồng
            $giaSauVAT      = $this->money($giaGoc * (1 + $vat));
            $donGiaSauFlash = $this->money($giaSauVAT * (1 - $flash));

            // Thành tiền (1 dòng) = đơn giá sau flash * số lượng — là số nguyên đồng
            $thanhTien = $this->money($donGiaSauFlash * $soLuong);

            // Breakdown hiển thị (cũng làm tròn đồng)
            $tongVAT       = $this->money($giaGoc * $soLuong * $vat);
            $giamFlashSale = $this->money($giaSauVAT * $flash * $soLuong);

            // Giảm thêm cấp đơn hàng (voucher/điểm) — làm tròn đồng
            $giamVoucher = $this->money((float)($data['giam_voucher'] ?? 0));
            $giamDiem    = $this->money((float)($data['giam_diem'] ?? 0));

            // Phương thức & phí VC (VNPay/MoMo: 20k, COD: 40k) — số nguyên
            $method = strtolower((string)$data['phuong_thuc_thanh_toan']);
            if (!in_array($method, ['vnpay', 'momo', 'cod'], true)) {
                return response()->json(['message' => 'Phương thức thanh toán không hợp lệ'], 422);
            }
            $phiVC = ($method === 'cod') ? 40000 : 20000;

            // Tạm tính = tổng thành_tiền từ chi tiết
            $tamTinh = $thanhTien; // 1 dòng nên = $thanhTien (số nguyên)

            // Tổng thanh toán = tạm tính - voucher - điểm + phí VC — số nguyên
            $tongThanhToan = $this->money($tamTinh - $giamVoucher - $giamDiem + $phiVC);

            // 3) Tạo Đơn hàng
            $donhang = DonHang::create([
                'id'                      => (string) Str::uuid(),
                'ma_don_hang'             => $this->genMaDonHang(),
                'khach_hang_id'           => $kh?->id,
                'ten_nguoi_nhan'          => $data['ten_nguoi_nhan'],
                'so_dien_thoai'           => $data['so_dien_thoai'],
                'dia_chi'                 => $data['dia_chi'],
                'ghi_chu'                 => $data['ghi_chu'] ?? null,

                'tam_tinh'                => $tamTinh,        // ✅ tổng từ chi tiết (đồng)
                'giam_voucher'            => $giamVoucher,    // ✅ đồng
                'giam_diem'               => $giamDiem,       // ✅ đồng
                'phi_van_chuyen'          => $phiVC,          // ✅ 20000 | 40000
                'tong_thanh_toan'         => $tongThanhToan,  // ✅ đồng
                'voucher_id'              => $data['voucher_id'] ?? null,

                'trang_thai'              => 'CHO_XU_LY',
                'phuong_thuc_thanh_toan'  => $method,
                'ngay_tao'                => now(),
            ]);

            // 4) Snapshot chi tiết — giam_gia để trống (voucher là cấp đơn)
            ChiTietDonHang::create([
                'id'           => (string) Str::uuid(),
                'don_hang_id'  => $donhang->id,
                'san_pham_id'  => $sp->id,
                'ten_san_pham' => $sp->tenSanPham,

                'gia'          => $giaSauVAT,   // ✅ đơn giá đã VAT / 1 sp (đồng)
                'vat'          => $vat,         // 0.08
                'flash_sale'   => $flash,       // 0..1
                'giam_gia'     => 0,         // ✅ để trống vì voucher ở cấp đơn
                'so_luong'     => $soLuong,
                'thanh_tien'   => $thanhTien,   // ✅ đồng
            ]);

            ThanhToan::create([
                'don_hang_id'   => $donhang->id,
                'kenh'          => $method,
                'so_tien'       => $tongThanhToan, // ✅ đồng
                'don_vi_tien'   => 'VND',
                'trang_thai'    => 'CHO_XU_LY',
                'ma_tham_chieu' => $donhang->id,
            ]);

            // 5) GHN
            $resolved       = $ghn->resolveFullAddress((string) $data['dia_chi']);
            $toDistrictId   = (int) ($resolved['to_district_id'] ?? 0);
            $toWardCode     = (string) ($resolved['to_ward_code'] ?? '');
            if ($toDistrictId <= 0 || $toWardCode === '') {
                return response()->json(['message' => 'Không xác định được quận/huyện hoặc phường/xã từ địa chỉ.'], 422);
            }

            $fromDistrictId = (int) env('GHN_FROM_DISTRICT_ID');
            $serviceId      = $ghn->getServiceId($fromDistrictId, $toDistrictId, 2);
            $codAmount      = ($method === 'cod') ? (int) $tongThanhToan : 0; // đã là số nguyên

            $payload = [
                'to_name'           => $data['ten_nguoi_nhan'],
                'to_phone'          => $data['so_dien_thoai'],
                'to_address'        => $data['dia_chi'],
                'to_ward_code'      => $toWardCode,
                'to_district_id'    => $toDistrictId,
                'service_type_id'   => 2,
                'service_id'        => $serviceId,
                'payment_type_id'   => 2,
                'required_note'     => 'KHONGCHOXEMHANG',
                'weight'            => max(500, 500 * $soLuong),
                'cod_amount'        => $codAmount,
                'client_order_code' => $donhang->ma_don_hang,
                'items' => [[
                    'name'     => $sp->tenSanPham,
                    'quantity' => $soLuong,
                    'price'    => (int) $this->money($giaGoc), // đơn giá tham chiếu
                    'weight'   => 500,
                ]],
            ];

            $res = $ghn->createOrder($payload);
            if (($res['code'] ?? 0) !== 200) {
                return response()->json(['message' => 'GHN tạo đơn thất bại: ' . ($res['message'] ?? 'Unknown')], 502);
            }

            $orderCode = data_get($res, 'data.order_code');
            $donhang->update([
                'don_vi_van_chuyen' => 'GHN',
                'ma_van_don'        => $orderCode,
                'ngay_cap_nhat'     => now(),
            ]);

            $paymentUrl = null;
            if ($method === 'vnpay') $paymentUrl = $this->buildVnpayUrl($donhang);
            if ($method === 'momo')  $paymentUrl = $this->buildMomoUrl($donhang);

            return response()->json([
                'message'          => 'Tạo đơn mua ngay thành công',
                'don_hang_id'      => $donhang->id,
                'ma_don_hang'      => $donhang->ma_don_hang,
                'ma_van_don'       => $donhang->ma_van_don,
                'tong_vat'         => $tongVAT,        // ✅ đã làm tròn đồng
                'giam_flash_sale'  => $giamFlashSale,  // ✅ đã làm tròn đồng
                'tong_thanh_toan'  => $tongThanhToan,  // ✅ đã làm tròn đồng
                'payment_url'      => $paymentUrl,
            ], 201);
        });
    }

    public function datHang(DatHangRequest $request, GhnService $ghn)
    {
        $data   = $request->validated();
        $userId = $request->user()->id;

        // 1) Map KhachHang theo user (có thể null nếu chưa link)
        $kh = KhachHang::where('taiKhoan_id', $userId)->first();
        $khachHangId = $kh?->id;

        // 2) Lấy giỏ của chính user
        $gio = $this->gioHang->layGio($userId);
        if (empty($gio['san_pham'])) {
            return response()->json([
                'message' => 'Giỏ hàng trống.',
                'errors'  => ['items' => ['Giỏ hàng trống.']]
            ], 422);
        }

        return DB::transaction(function () use ($data, $userId, $gio, $khachHangId, $ghn) {
            $items   = $gio['san_pham'];                        // [{id,ten,gia,VAT,giamGia,soLuong,thanh_tien,weight?}]
            $tamTinh = (float) ($gio['tam_tinh'] ?? 0);

            $giamVoucher  = (float)($data['giam_voucher']   ?? 0);
            $giamDiem     = (float)($data['giam_diem']      ?? 0);
            $phiVC        = 20000.0;                         // phí ship cố định theo yêu cầu

            // 3) Xác định phương thức & trạng thái ban đầu
            $method = strtolower((string)$data['phuong_thuc_thanh_toan']); // 'vnpay' | 'momo' | 'cod'
            if (!in_array($method, ['vnpay', 'momo', 'cod'], true)) {
                return response()->json(['message' => 'Phương thức thanh toán không hợp lệ'], 422);
            }
            $trangThaiDon = $method === 'cod' ? 'CHO_XU_LY' : 'CHO_THANH_TOAN';
            if ($method === 'cod') {
                $phiVC += 20000.0; // cộng thêm phí COD nếu thanh toán COD
            }

            $tongThanhToan = round($tamTinh - $giamVoucher - $giamDiem + $phiVC, 2);

            // 4) Tạo Đơn hàng
            $donhang = DonHang::create([
                'id'                      => (string) Str::uuid(),
                'ma_don_hang'             => $this->genMaDonHang(),
                'khach_hang_id'           => $khachHangId,
                'ten_nguoi_nhan'          => $data['ten_nguoi_nhan'],
                'so_dien_thoai'           => $data['so_dien_thoai'],
                'dia_chi'                 => $data['dia_chi'] ?? null,
                'ghi_chu'                 => $data['ghi_chu'] ?? null,

                'tam_tinh'                => $tamTinh,
                'giam_voucher'            => $giamVoucher,
                'giam_diem'               => $giamDiem,
                'phi_van_chuyen'          => $phiVC,
                'tong_thanh_toan'         => $tongThanhToan,
                'voucher_id'              => $data['voucher_id'] ?? null,

                'trang_thai'              => $trangThaiDon,
                'phuong_thuc_thanh_toan'  => $method,
                'ngay_tao'                => now(),
            ]);

            // 5) Snapshot chi tiết đơn hàng từ giỏ
            foreach ($items as $it) {
                ChiTietDonHang::create([
                    'id'           => (string) Str::uuid(),
                    'don_hang_id'  => $donhang->id,
                    'san_pham_id'  => $it['id'],
                    'ten_san_pham' => $it['ten'],
                    'gia'          => (float)($it['gia'] ?? 0),
                    'vat'          => (float)($it['VAT'] ?? 0),
                    'flash_sale'   => (float)($it['flash_sale'] ?? 0),
                    'giam_gia'     => (float)($it['giamGia'] ?? 0),
                    'so_luong'     => (int)($it['soLuong'] ?? 1),
                    'thanh_tien'   => (float)($it['thanh_tien'] ?? 0),
                ]);
            }

            // 6) Tạo bản ghi Thanh toán (đồng nhất mọi phương thức)
            $tt = ThanhToan::create([
                'don_hang_id'   => $donhang->id,
                'kenh'          => $method,                 // 'vnpay' | 'momo' | 'cod'
                'so_tien'       => $donhang->tong_thanh_toan,
                'don_vi_tien'   => 'VND',
                'trang_thai'    => 'CHO_XU_LY',
                'ma_tham_chieu' => $donhang->id,            // online dùng luôn; COD sẽ cập nhật sau = orderCode GHN
                'thong_diep'    => $method === 'cod' ? 'Thanh toán khi nhận hàng' : null,
            ]);

            // 7) Tính weight tổng (gram)
            $totalWeight = collect($items)->reduce(function ($sum, $it) {
                $w  = (int)($it['weight'] ?? 100);
                $sl = (int)($it['soLuong'] ?? 1);
                return $sum + $w * $sl;
            }, 0);
            if ($totalWeight <= 0) $totalWeight = (int)($data['total_weight'] ?? 500);

            // 8) Resolve địa chỉ -> mã GHN
            $resolved     = $ghn->resolveFullAddress((string)($data['dia_chi'] ?? ''));
            $toDistrictId = (int)($resolved['to_district_id'] ?? 0);
            $toWardCode   = (string)($resolved['to_ward_code'] ?? '');
            if ($toDistrictId <= 0 || $toWardCode === '') {
                throw new \RuntimeException('Không xác định được quận/huyện hoặc phường/xã từ địa chỉ.');
            }

            $fromDistrictId = (int) env('GHN_FROM_DISTRICT_ID');
            $serviceId      = $ghn->getServiceId($fromDistrictId, $toDistrictId, 2); // Chuẩn

            $codAmount = ($method === 'cod') ? (int) round($donhang->tong_thanh_toan) : 0;

            $payload = [
                'to_name'          => $data['ten_nguoi_nhan'],
                'to_phone'         => $data['so_dien_thoai'],
                'to_address'       => $data['dia_chi'],
                'to_ward_code'     => $toWardCode,
                'to_district_id'   => $toDistrictId,

                'service_type_id'  => 2,
                'service_id'       => $serviceId,
                'payment_type_id'  => 2, // người nhận trả phí; nếu shop trả phí thì = 1
                'required_note'    => 'KHONGCHOXEMHANG',

                'weight'           => (int) $totalWeight,
                'cod_amount'       => $codAmount,
                'client_order_code' => $donhang->ma_don_hang,

                'items' => collect($items)->map(fn($i) => [
                    'name'     => $i['ten'],
                    'quantity' => (int)($i['soLuong'] ?? 1),
                    'price'    => (int) round($i['gia'] ?? 0),
                    'weight'   => (int)($i['weight'] ?? 100),
                ])->values()->all(),
            ];

            $res = $ghn->createOrder($payload);
            if (($res['code'] ?? 0) !== 200) {
                throw new \RuntimeException('GHN tạo đơn thất bại: ' . ($res['message'] ?? ''));
            }

            $orderCode = data_get($res, 'data.order_code');
            $donhang->update([
                'don_vi_van_chuyen' => 'GHN',
                'ma_van_don'        => $orderCode,
                'ngay_cap_nhat'     => now(),
            ]);

            // Với COD: cập nhật ma_tham_chieu = mã vận đơn để đối soát theo GHN
            if ($method === 'cod') {
                $tt->update(['ma_tham_chieu' => $orderCode]);

                // Xoá giỏ luôn khi đặt COD
                $this->gioHang->xoaHet($userId);
            }

            // 9) Trả URL thanh toán nếu online
            $paymentUrl = null;
            if ($method === 'vnpay') {
                $paymentUrl = $this->buildVnpayUrl($donhang);
            } elseif ($method === 'momo') {
                $paymentUrl = $this->buildMomoUrl($donhang);
            } else {
                // COD: không có paymentUrl, nhưng vẫn OK
            }

            return response()->json([
                'message'      => $method === 'cod'
                    ? 'Đặt hàng COD thành công. GHN đã tạo vận đơn.'
                    : 'Tạo đơn hàng thành công, chờ thanh toán',
                'don_hang_id'  => $donhang->id,
                'ma_don_hang'  => $donhang->ma_don_hang,
                'ma_van_don'   => $donhang->ma_van_don,
                'payment_url'  => $paymentUrl,
            ], 201);
        });
    }
    public function showById(string $id)
    {
        $order = DonHang::with(['chiTietDonHang', 'thanhToan' => fn($q) => $q->latest()])
            ->where('id', $id)
            ->firstOrFail();

        return response()->json([
            'id'          => $order->id,
            'ma_don_hang' => $order->ma_don_hang,
            'status'      => $order->trang_thai,
            'total'       => $order->tong_thanh_toan,
            'paid'        => optional($order->thanhToan)->trang_thai === 'DA_THANH_TOAN',
            'gateway'     => optional($order->thanhToan)->kenh,
        ]);
    }

    private function money(float|int $v): int
    {
        // Làm tròn 0 chữ số thập phân, half up — 223095.60 => 223096
        return (int) round((float) $v, 0, PHP_ROUND_HALF_UP);
    }
}
