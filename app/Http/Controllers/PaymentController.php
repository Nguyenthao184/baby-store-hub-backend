<?php

namespace App\Http\Controllers;

use App\Models\ThanhToan;

class PaymentController extends Controller
{
    public function status(string $orderId)
    {
        $tt = ThanhToan::where('ma_tham_chieu', $orderId)->latest()->first();

        if (!$tt) {
            return response()->json([
                'orderId' => $orderId,
                'status'  => 'NOT_FOUND',
                'message' => 'Không tìm thấy thanh toán'
            ], 404);
        }

        $map = [
            'CHO_XU_LY'      => 'PENDING',
            'DA_THANH_TOAN'  => 'PAID',
            'THAT_BAI'       => 'FAILED',
        ];

        return response()->json([
            'orderId' => $orderId,
            'gateway' => $tt->kenh,
            'status'  => $map[$tt->trang_thai] ?? $tt->trang_thai,
            'amount'  => (float)$tt->so_tien,
            'message' => $tt->thong_diep ?? null,
            'updated' => optional($tt->updated_at)->toIso8601String(),
        ]);
    }
}
