<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ThanhToan;
use App\Models\HoaDon;
use App\Models\DonHang;
use App\Models\ChiTietDonHang;
use Illuminate\Support\Facades\Log;
use App\Http\Requests\Checkout\DatHangRequest;
use App\Models\KhachHang;
use App\Services\GioHangService;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

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

        return $isSuccess
            ? response()->json(['message' => 'Thanh toán thành công'], 200)
            : response()->json(['message' => 'Thanh toán thất bại'], 400);
    }

    // public function datHangOnline(DatHangRequest $request)
    // {
    //      $data   = $request->validated();
    // $userId = $request->user()->id; // Thêm dòng này

    // $khachHang = KhachHang::where('taiKhoan_id', $userId)->first();
    // if ($khachHang) {
    //     $khachHangId = $khachHang->id;
    // }

    //     if (!in_array($data['phuong_thuc_thanh_toan'], ['vnpay', 'momo'])) {
    //         return response()->json(['message' => 'Chỉ hỗ trợ vnpay/momo trong API này'], 422);
    //     }

    //     return DB::transaction(function () use ($data, $userId) {

    //         // ===== 1) TÍNH THEO CÔNG THỨC MỚI =====
    //         //$tamTinh = 0.0;

    //         // foreach ($data['items'] as &$it) {
    //         //     $gia     = (float) ($it['gia'] ?? 0);
    //         //     $vatPct  = (float) ($it['vat'] ?? 0);
    //         //     $giamRaw = (float) ($it['giam_gia'] ?? 0);   // có thể là % (0..1) hoặc VND/sp
    //         //     $sl      = (int)   ($it['so_luong'] ?? 0);
    //         //     $noiBat  = (bool)  ($it['noi_bat'] ?? false); // nếu FE không gửi, sẽ là false

    //         //     if ($giamRaw >= 0 && $giamRaw <= 1) {
    //         //         // giảm theo TỶ LỆ
    //         //         $discFactor = $noiBat ? 1.0 : max(0.0, 1.0 - $giamRaw);
    //         //         $giaCuoi1sp = round($gia * (1 + $vatPct/100) * $discFactor, 2);
    //         //     } else {
    //         //         // fallback: giảm theo SỐ TIỀN VND/sp
    //         //         $giaSauGiam = max(0.0, $gia - $giamRaw);
    //         //         $giaCuoi1sp = round($giaSauGiam * (1 + $vatPct/100), 2);
    //         //     }

    //         //     $thanhTien = round($giaCuoi1sp * $sl, 2);
    //         //     $it['_gia_cuoi_1sp'] = $giaCuoi1sp;   // lưu tạm để ghi snapshot
    //         //     $it['_thanh_tien']   = $thanhTien;

    //         //     $tamTinh += $thanhTien;
    //         // }
    //         // unset($it);
    //         $gio = $this->gioHang->layGio($userId);

    //         $items   = $gio['san_pham'];            // danh sách item trong giỏ
    //         $tamTinh = (float) $gio['tam_tinh'];    // tổng tạm tính
    //         $giamVoucher   = (float)($data['giam_voucher'] ?? 0);
    //         $giamDiem      = (float)($data['giam_diem'] ?? 0);
    //         $phiVC         = (float)($data['phi_van_chuyen'] ?? 0);
    //         $phiCOD        = (float)($data['phi_cod'] ?? 0); // nếu không dùng COD thì luôn = 0

    //         $tongThanhToan = round($tamTinh - $giamVoucher - $giamDiem + $phiVC + $phiCOD, 2);

    //         // ===== 2) TẠO ĐƠN HÀNG =====
    //         $donHangId = (string) Str::uuid();
    //         $maDonHang = $this->genMaDonHang();

    //         $donhang = DonHang::create([
    //             'id'                         => $donHangId,
    //             'ma_don_hang'                => $maDonHang,
    //             'khach_hang_id'              => $data['khach_hang_id'],
    //             'ten_nguoi_nhan'             => $data['ten_nguoi_nhan'],
    //             'so_dien_thoai'              => $data['so_dien_thoai'],
    //             'dia_chi'                    => $data['dia_chi'] ?? null,
    //             'ghi_chu'                    => $data['ghi_chu'] ?? null,

    //             'tam_tinh'                   => $tamTinh,
    //             'giam_voucher'               => $giamVoucher,
    //             'giam_diem'                  => $giamDiem,
    //             'phi_van_chuyen'             => $phiVC,
    //             // nếu có cột phi_cod thì lưu, không thì bỏ ra
    //             'tong_thanh_toan'            => $tongThanhToan,
    //             'voucher_id'                 => $data['voucher_id'] ?? null,

    //             'trang_thai'                 => 'awaiting_payment',
    //             'phuong_thuc_thanh_toan'     => $data['phuong_thuc_thanh_toan'],
    //             'ngay_tao'                   => now(),
    //         ]);

    //         // ===== 3) SNAPSHOT CHI TIẾT =====
    //         foreach ($items as $it) {
    //             ChiTietDonHang::create([
    //                 'id'           => (string) Str::uuid(),
    //                 'don_hang_id'  => $donhang->id,
    //                 'san_pham_id'  => $it['id'],
    //                 'ten_san_pham' => $it['ten'],
    //                 'gia'          => (float)($it['gia'] ?? 0),     // sau KM nổi bật (chưa VAT)
    //                 'vat'          => (float)($it['VAT'] ?? 0),
    //                 'giam_gia'     => (float)($it['giamGia'] ?? 0), // nếu có
    //                 'so_luong'     => (int)($it['soLuong'] ?? 1),
    //                 'thanh_tien'   => (float)($it['thanh_tien'] ?? 0),
    //             ]);
    //         }

    //         // ===== 4) THANH TOÁN PENDING =====
    //         $txnRef = (string) $donhang->id; // trùng vnp_TxnRef
    //         ThanhToan::create([
    //             'don_hang_id'   => $donhang->id,
    //             'kenh'          => 'vnpay',
    //             'so_tien'       => $donhang->tong_thanh_toan,
    //             'don_vi_tien'   => 'VND',
    //             'trang_thai'    => 'CHO_XU_LY',
    //             'ma_tham_chieu' => $txnRef,
    //             'ma_giao_dich'  => null,
    //             'ma_ket_qua'    => null,
    //             'noi_dung'      => 'Chờ khách thanh toán',
    //             'raw_return'    => null,
    //         ]);

    //         // ===== 5) URL THANH TOÁN =====
    //         if ($donhang->phuong_thuc_thanh_toan === 'vnpay') {
    //             $paymentUrl = $this->buildVnpayUrl($donhang);
    //             // KHÔNG xóa giỏ ở đây
    //         } elseif ($donhang->phuong_thuc_thanh_toan === 'cod') {
    //             // COD thì coi như chốt đơn
    //             $this->gioHang->xoaHet($userId);
    //         } else {
    //             $paymentUrl = ''; // Chưa hỗ trợ Momo trong API này
    //         }


    //         return response()->json([
    //             'message'      => 'Tạo đơn hàng thành công, chờ thanh toán',
    //             'don_hang_id'  => $donhang->id,
    //             'ma_don_hang'  => $donhang->ma_don_hang,
    //             'payment_url'  => $paymentUrl,
    //         ], 201);
    //     });
    //     // Sau khi tạo đơn
    //     $this->gioHang->xoaHet($request->user()->id);
    // }
   public function datHangOnline(DatHangRequest $request)
{
    $data   = $request->validated();
    $userId = $request->user()->id;

    // 1) Map KhachHang theo user
    $kh = KhachHang::where('taiKhoan_id', $userId)->first(); // cột này có trong migration【:contentReference[oaicite:1]{index=1}】
    $khachHangId = $kh?->id; // có thì dùng, chưa có thì để null (nếu schema cho phép)

    // 2) Lấy giỏ của chính user
    $gio = $this->gioHang->layGio($userId); // service đang lấy theo userId【:contentReference[oaicite:2]{index=2}】
    if (empty($gio['san_pham'])) {
        return response()->json([
            'message' => 'Giỏ hàng trống.',
            'errors'  => ['items' => ['Giỏ hàng trống.']]
        ], 422);
    }

    return DB::transaction(function () use ($data, $userId, $gio, $khachHangId) {
        $items   = $gio['san_pham'];
        $tamTinh = (float) $gio['tam_tinh'];

        $giamVoucher  = (float)($data['giam_voucher']   ?? 0);
        $giamDiem     = (float)($data['giam_diem']      ?? 0);
        $phiVC        = (float)($data['phi_van_chuyen'] ?? 0);
        $phiCOD       = (float)($data['phi_cod']        ?? 0);

        $tongThanhToan = round($tamTinh - $giamVoucher - $giamDiem + $phiVC + $phiCOD, 2);

        $donhang = DonHang::create([
            'id'                      => (string) Str::uuid(),
            'ma_don_hang'             => $this->genMaDonHang(),
            'khach_hang_id'           => $khachHangId,      // <-- dùng ID đã map (đừng comment)
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
            'trang_thai'              => 'awaiting_payment',
            'phuong_thuc_thanh_toan'  => $data['phuong_thuc_thanh_toan'],
            'ngay_tao'                => now(),
        ]);

        foreach ($items as $it) {
            ChiTietDonHang::create([
                'id'           => (string) \Illuminate\Support\Str::uuid(),
                'don_hang_id'  => $donhang->id,
                'san_pham_id'  => $it['id'],
                'ten_san_pham' => $it['ten'],
                'gia'          => (float)($it['gia'] ?? 0),
                'vat'          => (float)($it['VAT'] ?? 0),
                'giam_gia'     => (float)($it['giamGia'] ?? 0),
                'so_luong'     => (int)($it['soLuong'] ?? 1),
                'thanh_tien'   => (float)($it['thanh_tien'] ?? 0),
            ]);
        }

        ThanhToan::create([
            'don_hang_id'   => $donhang->id,
            'kenh'          => $data['phuong_thuc_thanh_toan'],
            'so_tien'       => $donhang->tong_thanh_toan,
            'don_vi_tien'   => 'VND',
            'trang_thai'    => 'CHO_XU_LY',
            'ma_tham_chieu' => $donhang->id,
        ]);

        // ... sau khi create DonHang + ThanhToan (kenh = $data['phuong_thuc_thanh_toan'])
        $paymentUrl = null;
        if ($donhang->phuong_thuc_thanh_toan === 'vnpay') {
            $paymentUrl = $this->buildVnpayUrl($donhang);
        } elseif ($donhang->phuong_thuc_thanh_toan === 'momo') {
            $paymentUrl = $this->buildMomoUrl($donhang); // <--- thêm dòng này
        } elseif ($donhang->phuong_thuc_thanh_toan === 'cod') {
            $this->gioHang->xoaHet($userId);
        }


        return response()->json([
            'message'      => 'Tạo đơn hàng thành công, chờ thanh toán',
            'don_hang_id'  => $donhang->id,
            'ma_don_hang'  => $donhang->ma_don_hang,
            'payment_url'  => $paymentUrl,
        ], 201);
    });
}


    private function createHoaDonFromDonHang(DonHang $donhang): HoaDon
    {
        return DB::transaction(function () use ($donhang) {

            // 1) Idempotent + khóa chống race
            $existing = HoaDon::where('don_hang_id', $donhang->id)
                ->lockForUpdate()
                ->first();
            if ($existing) {
                Log::info('HOADON existed (skip creating)', [
                    'don_hang_id' => $donhang->id,
                    'hoa_don_id'  => $existing->id,
                ]);
                return $existing;
            }

            // 2) Lấy chi tiết (snapshot) và tính tổng
            $items = ChiTietDonHang::where('don_hang_id', $donhang->id)->get();

            $tongTienHang = 0.0; // tổng trước VAT, sau giảm (theo snapshot)
            $tongVAT       = 0.0;

            foreach ($items as $it) {
                $gia     = (float) ($it->gia ?? 0);        // giá niêm yết / 1 sp
                $vatPct  = (float) ($it->vat ?? 0);        // %
                $giamGia = (float) ($it->giam_gia ?? 0);   // số tiền giảm / 1 sp
                $sl      = (int)   ($it->so_luong ?? 0);

                $giaSauGiam = max(0, $gia - $giamGia);        // VND / sp
                $truocVAT   = $giaSauGiam * $sl;              // VND
                $tienVAT    = $truocVAT * ($vatPct / 100.0);  // VND

                $tongTienHang += $truocVAT;
                $tongVAT      += $tienVAT;
            }

            // 3) Lấy các khoản từ snapshot đơn hàng
            $giamVoucher   = (float) ($donhang->giam_voucher ?? 0);
            $giamDiem      = (float) ($donhang->giam_diem ?? 0);
            $phiVC         = (float) ($donhang->phi_van_chuyen ?? 0);
            $tongThanhToan = (float) ($donhang->tong_thanh_toan ?? 0);

            // 4) Tạo hoá đơn
            $hoaDon = HoaDon::create([
                'id'                      => (string) Str::uuid(),
                'ma_hoa_don'              => $this->genSoHoaDon(), // tạo số HĐ duy nhất trong ngày
                'don_hang_id'             => $donhang->id,
                'ngay_xuat'               => now(),
                'tong_tien_hang'          => $tongTienHang,
                'tong_vat'                => $tongVAT,
                'giam_voucher'            => $giamVoucher,
                'giam_diem'               => $giamDiem,
                'phi_van_chuyen'          => $phiVC,
                'tong_thanh_toan'         => $tongThanhToan,
                'phuong_thuc_thanh_toan'  => $donhang->phuong_thuc_thanh_toan, // 'vnpay' | 'momo' | 'cod'
            ]);

            Log::info('HOADON created', [
                'don_hang_id' => $donhang->id,
                'hoa_don_id'  => $hoaDon->id,
                'ma_hoa_don'  => $hoaDon->ma_hoa_don,
            ]);

            return $hoaDon;
        });
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
        $ok = ((int)($p['resultCode'] ?? -1)) === 0;

        if ($orderId) {
            ThanhToan::where('ma_tham_chieu', $orderId)
                ->whereRaw('LOWER(kenh) = ?', ['momo'])
                ->update([
                    'trang_thai'   => $resultCode === 0 ? 'DA_THANH_TOAN' : 'THAT_BAI',
                    'ma_giao_dich' => $transId,
                    'ma_ket_qua'   => $resultCode,
                    'raw_return'   => $p,
                ]);
        }

        $fe = config('app.frontend_url'); // http://localhost:8000
    return redirect()->away($fe . '/checkout/result?orderId=' . urlencode($orderId) . '&gw=momo');

    }

    public function momoIpn(Request $request)
    {
        $p = $request->all();
        Log::info('MoMo IPN', $p);

        $orderId    = $p['orderId'] ?? null;
        $resultCode = (int)($p['resultCode'] ?? -1);
        $transId    = $p['transId'] ?? null;

        if ($orderId) {
            ThanhToan::where('ma_tham_chieu', $orderId)
                ->whereRaw('LOWER(kenh) = ?', ['momo'])
                ->update([
                    'trang_thai'   => $resultCode === 0 ? 'DA_THANH_TOAN' : 'THAT_BAI',
                    'ma_giao_dich' => $transId,
                    'ma_ket_qua'   => $resultCode,
                    'raw_ipn'      => $p,
                ]);
        }

        return response()->json(['resultCode' => 0, 'message' => 'Confirm Success']);
    }
}
