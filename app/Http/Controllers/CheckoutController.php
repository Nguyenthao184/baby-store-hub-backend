<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ThanhToan;
use App\Models\HoaDon;
use App\Models\DonHang;
use App\Models\ChiTietDonHang; 
use Illuminate\Support\Facades\Log;
use App\Http\Requests\Checkout\DatHangRequest;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class CheckoutController extends Controller
{
    private function vnpVerifySignature(array $params, string $hashSecret): bool
    {
        $secureHash = $params['vnp_SecureHash'] ?? null;
        unset($params['vnp_SecureHash'], $params['vnp_SecureHashType']);
        ksort($params);
        $hashData = [];
        foreach ($params as $k => $v) {
            $hashData[] = urlencode($k).'='.urlencode($v);
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

    public function datHangOnline(DatHangRequest $request)
    {
        $data = $request->validated();

        if (!in_array($data['phuong_thuc_thanh_toan'], ['vnpay', 'momo'])) {
            return response()->json(['message' => 'Chỉ hỗ trợ vnpay/momo trong API này'], 422);
        }

        return DB::transaction(function () use ($data) {

            // ===== 1) TÍNH THEO CÔNG THỨC MỚI =====
            $tamTinh = 0.0;

            foreach ($data['items'] as &$it) {
                $gia     = (float) ($it['gia'] ?? 0);
                $vatPct  = (float) ($it['vat'] ?? 0);
                $giamRaw = (float) ($it['giam_gia'] ?? 0);   // có thể là % (0..1) hoặc VND/sp
                $sl      = (int)   ($it['so_luong'] ?? 0);
                $noiBat  = (bool)  ($it['noi_bat'] ?? false); // nếu FE không gửi, sẽ là false

                if ($giamRaw >= 0 && $giamRaw <= 1) {
                    // giảm theo TỶ LỆ
                    $discFactor = $noiBat ? 1.0 : max(0.0, 1.0 - $giamRaw);
                    $giaCuoi1sp = round($gia * (1 + $vatPct/100) * $discFactor, 2);
                } else {
                    // fallback: giảm theo SỐ TIỀN VND/sp
                    $giaSauGiam = max(0.0, $gia - $giamRaw);
                    $giaCuoi1sp = round($giaSauGiam * (1 + $vatPct/100), 2);
                }

                $thanhTien = round($giaCuoi1sp * $sl, 2);
                $it['_gia_cuoi_1sp'] = $giaCuoi1sp;   // lưu tạm để ghi snapshot
                $it['_thanh_tien']   = $thanhTien;

                $tamTinh += $thanhTien;
            }
            unset($it);

            $giamVoucher   = (float)($data['giam_voucher'] ?? 0);
            $giamDiem      = (float)($data['giam_diem'] ?? 0);
            $phiVC         = (float)($data['phi_van_chuyen'] ?? 0);
            $phiCOD        = (float)($data['phi_cod'] ?? 0); // nếu không dùng COD thì luôn = 0

            $tongThanhToan = round($tamTinh - $giamVoucher - $giamDiem + $phiVC + $phiCOD, 2);

            // ===== 2) TẠO ĐƠN HÀNG =====
            $donHangId = (string) Str::uuid();
            $maDonHang = $this->genMaDonHang();

            $donhang = DonHang::create([
                'id'                         => $donHangId,
                'ma_don_hang'                => $maDonHang,
                'khach_hang_id'              => $data['khach_hang_id'],
                'ten_nguoi_nhan'             => $data['ten_nguoi_nhan'],
                'so_dien_thoai'              => $data['so_dien_thoai'],
                'dia_chi'                    => $data['dia_chi'] ?? null,
                'ghi_chu'                    => $data['ghi_chu'] ?? null,

                'tam_tinh'                   => $tamTinh,          
                'giam_voucher'               => $giamVoucher,
                'giam_diem'                  => $giamDiem,
                'phi_van_chuyen'             => $phiVC,
                // nếu có cột phi_cod thì lưu, không thì bỏ ra
                'tong_thanh_toan'            => $tongThanhToan,    
                'voucher_id'                 => $data['voucher_id'] ?? null,

                'trang_thai'                 => 'awaiting_payment',
                'phuong_thuc_thanh_toan'     => $data['phuong_thuc_thanh_toan'],
                'ngay_tao'                   => now(),
            ]);

            // ===== 3) SNAPSHOT CHI TIẾT =====
            foreach ($data['items'] as $it) {
                ChiTietDonHang::create([
                    'id'            => (string) Str::uuid(),
                    'don_hang_id'   => $donhang->id,
                    'san_pham_id'   => $it['san_pham_id'],
                    'ten_san_pham'  => $it['ten_san_pham'],
                    'gia'           => (float)$it['gia'],
                    'vat'           => (float)($it['vat'] ?? 0),
                    'giam_gia'      => (float)($it['giam_gia'] ?? 0),
                    'so_luong'      => (int)$it['so_luong'],
                    'thanh_tien'    => (float)$it['_thanh_tien'],    // lưu đúng theo công thức mới
                    // nếu bảng có cột 'noi_bat' thì thêm:
                    // 'noi_bat'    => (bool)($it['noi_bat'] ?? false),
                ]);
            }

            // ===== 4) THANH TOÁN PENDING =====
            $txnRef = (string) $donhang->id; // trùng vnp_TxnRef
            ThanhToan::create([
                'don_hang_id'   => $donhang->id,
                'kenh'          => 'vnpay',
                'so_tien'       => $donhang->tong_thanh_toan,
                'don_vi_tien'   => 'VND',
                'trang_thai'    => 'CHO_XU_LY',
                'ma_tham_chieu' => $txnRef,           
                'ma_giao_dich'  => null,
                'ma_ket_qua'    => null,
                'noi_dung'      => 'Chờ khách thanh toán',
                'raw_return'    => null,
            ]);

            // ===== 5) URL THANH TOÁN =====
            $paymentUrl = $donhang->phuong_thuc_thanh_toan === 'vnpay'
                ? $this->buildVnpayUrl($donhang)
                : null; // TODO: momo

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
            "vnp_CreateDate"=> date('YmdHis'),
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
            if ($i == 1) { $hashdata .= '&'; }
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

}
