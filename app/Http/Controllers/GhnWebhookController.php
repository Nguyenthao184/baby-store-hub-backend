<?php
namespace App\Http\Controllers;

use App\Models\DonHang;
use App\Models\HoaDon;
use App\Models\ThanhToan;
use Illuminate\Http\Request;

class GhnWebhookController extends Controller
{
    public function handle(Request $req)
    {
        // 1) (Tùy chọn) Xác thực chữ ký nếu bạn tự thêm HMAC + shared secret
        // if (! $this->verifySignature($req)) { return response()->json([], 401); }

        $type      = (string) $req->input('Type', '');
        $data      = (array)  $req->input('Data', []);
        $status    = (string) data_get($data, 'Status', '');      // vd: 'delivered'
        $orderCode = (string) data_get($data, 'OrderCode', '');
        $codAmount = (int) ($data['CodAmount'] ?? $data['CODAmount'] ?? 0);

        if ($orderCode === '') {
            return response()->json(['ok' => true]);
        }

        // 2) Tìm đơn theo mã vận đơn GHN
        $donHang = DonHang::where('ma_van_don', $orderCode)->first();
        if (!$donHang) {
            return response()->json(['ok' => true]); // idempotent
        }

        // 3) Map các trạng thái GHN KHÁC 'delivered' sang trạng thái nội bộ
        //    (Không ghi đè khi 'delivered' vì xử lý riêng ở bước 4)
        $map = [
            'ready_to_pick' => 'DANG_XU_LY',
            'picking'       => 'DANG_XU_LY',
            'storing'       => 'DANG_XU_LY',
            'transporting'  => 'DANG_VAN_CHUYEN',
            'delivering'    => 'DANG_VAN_CHUYEN',
            // 'delivered'    => 'DA_GIAO_HANG', // xử lý riêng ở dưới
        ];
        if ($status !== '' && isset($map[$status])) {
            $donHang->update(['trang_thai' => $map[$status]]);
        }

        // 4) Khi GHN báo delivered
        if ($type === 'Switch_status' && $status === 'delivered') {
            // 4.1: Set trạng thái ĐƠN HÀNG = DA_GIAO_HANG (theo yêu cầu)
            $donHang->update(['trang_thai' => 'DA_GIAO_HANG']);

            // 4.2: Nếu là COD và đã thu đủ tiền -> chốt thanh toán + tạo hóa đơn (nếu chưa có)
            $tt = ThanhToan::where('don_hang_id', $donHang->id)
                    ->whereRaw('LOWER(kenh) = ?', ['cod'])
                    ->latest('id')
                    ->first();

            if ($tt && $tt->trang_thai !== 'DA_THANH_TOAN' && $codAmount >= (int) $tt->so_tien) {
                $tt->update([
                    'trang_thai'   => 'DA_THANH_TOAN',
                    'ma_giao_dich' => $orderCode,
                    'ma_ket_qua'   => $status,
                    'raw_return'   => $data,
                ]);

                if (!HoaDon::where('don_hang_id', $donHang->id)->exists()) {
                    $this->createInvoiceForCod($donHang, $tt);
                }
            }
        }

        \Log::info('GHN Webhook', [
            'type' => $type, 'status' => $status,
            'orderCode' => $orderCode, 'cod' => $codAmount
        ]);

        return response()->json(['ok' => true]);
    }


    protected function createInvoiceForCod($donHang, $thanhToan)
    {
        // Tùy cấu trúc model của bạn
        HoaDon::create([
            'don_hang_id' => $donHang->id,
            'so_tien'     => $thanhToan->so_tien,
            'hinh_thuc'   => 'cod',
            'ghi_chu'     => 'Xuất hóa đơn sau khi GHN giao hàng & thu COD thành công',
        ]);
    }
}