<?php

namespace App\Http\Requests\Checkout;

use Illuminate\Foundation\Http\FormRequest;

class MuaNgayRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'san_pham_id'            => 'required|uuid', // hoặc integer tùy DB của bạn
            'so_luong'               => 'required|integer|min:1',

            'ten_nguoi_nhan'         => 'required|string|max:150',
            'so_dien_thoai'          => 'required|string|max:20',
            'dia_chi'                => 'required|string|max:255',
            'ghi_chu'                => 'nullable|string|max:255',
            'phuong_thuc_thanh_toan' => 'required|in:vnpay,momo,cod',

            'giam_voucher'           => 'nullable|numeric|min:0',
            'giam_diem'              => 'nullable|numeric|min:0',
            'voucher_id'             => 'nullable|uuid',
        ];
    }

    public function messages(): array
    {
        return [
            'san_pham_id.required' => 'Thiếu sản phẩm.',
            'san_pham_id.uuid'     => 'Mã sản phẩm không hợp lệ.',
            'so_luong.required'    => 'Vui lòng chọn số lượng.',
            'so_luong.integer'     => 'Số lượng phải là số nguyên.',
            'so_luong.min'         => 'Số lượng tối thiểu là 1.',

            'ten_nguoi_nhan.required' => 'Vui lòng nhập tên người nhận.',
            'so_dien_thoai.required'  => 'Vui lòng nhập số điện thoại.',
            'dia_chi.required'        => 'Vui lòng nhập địa chỉ giao hàng.',
            'phuong_thuc_thanh_toan.required' => 'Vui lòng chọn phương thức thanh toán.',
            'phuong_thuc_thanh_toan.in'       => 'Phương thức thanh toán không hợp lệ.',
        ];
    }
}
