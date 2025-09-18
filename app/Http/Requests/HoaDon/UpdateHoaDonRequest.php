<?php

namespace App\Http\Requests\HoaDon;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateHoaDonRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Chuẩn hóa dữ liệu trước khi validate:
     * - Chuẩn hóa phuongThucThanhToan về: cod|momo|vnpay
     * - Map alias: giamGiaSanPham -> giamVoucher, tongVAT -> thueVAT
     * - Ép kiểu số các trường tổng
     * - Ép kiểu số các trường trong sanPhams[*]
     * - Loại bỏ/không cho xử lý phiVanChuyen (offline)
     */
    protected function prepareForValidation(): void
    {
        $data = $this->all();

        // Map alias giảm giá & VAT tổng
        if (!isset($data['giamVoucher']) && isset($data['giamGiaSanPham'])) {
            $data['giamVoucher'] = $data['giamGiaSanPham'];
        }
        if (!isset($data['thueVAT']) && isset($data['tongVAT'])) {
            $data['thueVAT'] = $data['tongVAT'];
        }

        // Chuẩn hóa phương thức thanh toán
        if (isset($data['phuongThucThanhToan']) && is_string($data['phuongThucThanhToan'])) {
            $v = strtolower(trim($data['phuongThucThanhToan']));
            $map = [
                'cash' => 'cod', 'tiền mặt' => 'cod', 'tm' => 'cod', 'cod' => 'cod',
                'momo' => 'momo', 'mo mo' => 'momo',
                'vnpay' => 'vnpay', 'vn-pay' => 'vnpay', 'vnpay-qr' => 'vnpay', 'qr' => 'vnpay',
            ];
            $data['phuongThucThanhToan'] = $map[$v] ?? $v;
        }

        // Ép kiểu số các tổng
        foreach (['tongTienHang','giamVoucher','giamDiem','thueVAT','tongThanhToan'] as $k) {
            if (isset($data[$k]) && $data[$k] !== '') {
                $data[$k] = (float) $data[$k];
            }
        }

        // Ép kiểu chi tiết sản phẩm (nếu có)
        if (!empty($data['sanPhams']) && is_array($data['sanPhams'])) {
            $data['sanPhams'] = array_map(function ($item) {
                if (isset($item['soLuong']))  $item['soLuong']  = (int) $item['soLuong'];
                if (isset($item['giaBan']))   $item['giaBan']   = (float) $item['giaBan'];
                if (isset($item['giamGia']))  $item['giamGia']  = (float) $item['giamGia'];
                if (isset($item['VAT']))      $item['VAT']      = (float) $item['VAT'];
                return $item;
            }, $data['sanPhams']);
        }

        // Offline: không cho phép truyền phiVanChuyen
        if (array_key_exists('phiVanChuyen', $data)) {
            unset($data['phiVanChuyen']);
        }

        $this->replace($data);
    }

    public function rules(): array
    {
        return [
            'tongTienHang'        => ['required','numeric','min:0'],
            'giamVoucher'         => ['nullable','numeric','min:0'],
            'giamDiem'            => ['nullable','numeric','min:0'],
            'thueVAT'             => ['nullable','numeric','min:0'],
            'tongThanhToan'       => ['required','numeric','min:0'],

            // Offline: chỉ chấp nhận các phương thức nội bộ được cấu hình
            'phuongThucThanhToan' => ['required','string', Rule::in(['cod','momo','vnpay'])],

            'ghiChu'              => ['nullable','string'],
            'trangThai'           => ['nullable','string'],

            // Không cho phép field phí vận chuyển trong offline
            'phiVanChuyen'        => ['prohibited'],

            // Thay đổi chi tiết đơn hàng (tùy chọn)
            'sanPhams'            => ['sometimes','array'],
            'sanPhams.*.id'       => ['required_with:sanPhams','string','max:36'],
            'sanPhams.*.soLuong'  => ['required_with:sanPhams','integer','min:0'],
            'sanPhams.*.giaBan'   => ['nullable','numeric','min:0'],
            'sanPhams.*.giamGia'  => ['nullable','numeric','min:0'],
            'sanPhams.*.VAT'      => ['nullable','numeric','min:0','max:100'],

            'xoaSanPhamIds'       => ['sometimes','array'],
            'xoaSanPhamIds.*'     => ['string','max:36'],
        ];
    }

    public function messages(): array
    {
        return [
            'tongTienHang.required' => 'Vui lòng nhập tổng tiền hàng.',
            'tongTienHang.numeric'  => 'Tổng tiền hàng phải là số.',
            'tongThanhToan.required'=> 'Vui lòng nhập tổng thanh toán.',
            'phuongThucThanhToan.required' => 'Vui lòng chọn phương thức thanh toán.',
            'phuongThucThanhToan.in'       => 'Phương thức thanh toán không hợp lệ.',
            'phiVanChuyen.prohibited'      => 'Giao dịch offline không cho phép phí vận chuyển.',
            'sanPhams.*.id.required_with'  => 'Thiếu mã sản phẩm trong danh sách cập nhật.',
            'sanPhams.*.soLuong.required_with' => 'Thiếu số lượng cho sản phẩm.',
            'sanPhams.*.soLuong.integer'   => 'Số lượng phải là số nguyên.',
            'sanPhams.*.VAT.max'           => 'VAT không được vượt quá 100%.',
        ];
    }

    // Luôn trả JSON khi lỗi
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            response()->json([
                'message' => 'Dữ liệu không hợp lệ.',
                'errors'  => $validator->errors()
            ], 422)
        );
    }
}
