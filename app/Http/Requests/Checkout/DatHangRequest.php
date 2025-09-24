<?php

namespace App\Http\Requests\Checkout;

use Illuminate\Foundation\Http\FormRequest;

class DatHangRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // hoặc auth()->check();
    }

    public function rules(): array
    {
        return [
            'ten_nguoi_nhan' => 'required|string|max:150',
            'so_dien_thoai'  => 'required|string|max:20',
            'dia_chi'        => 'nullable|string|max:255',
            'ghi_chu'        => 'nullable|string|max:255',
            'voucher_id'     => 'nullable|uuid',
            'phuong_thuc_thanh_toan' => 'required|in:vnpay,momo,cod',

            'giam_voucher'   => 'nullable|numeric|min:0',
            'giam_diem'      => 'nullable|numeric|min:0',


        ];
    }

    public function messages(): array
    {
        return [
            'ten_nguoi_nhan.required' => 'Vui lòng nhập tên người nhận.',
            'ten_nguoi_nhan.string'   => 'Tên người nhận phải là chuỗi ký tự.',
            'ten_nguoi_nhan.max'      => 'Tên người nhận không được vượt quá 150 ký tự.',

            'so_dien_thoai.required' => 'Vui lòng nhập số điện thoại.',
            'so_dien_thoai.string'   => 'Số điện thoại phải là chuỗi ký tự.',
            'so_dien_thoai.max'      => 'Số điện thoại không được vượt quá 20 ký tự.',

            'dia_chi.required' => 'Vui lòng nhập địa chỉ giao hàng.',
            'dia_chi.string'   => 'Địa chỉ phải là chuỗi ký tự.',
            'dia_chi.max'      => 'Địa chỉ không được vượt quá 255 ký tự.',

            'ghi_chu.string' => 'Ghi chú phải là chuỗi ký tự.',
            'ghi_chu.max'    => 'Ghi chú không được vượt quá 255 ký tự.',

            'voucher_id.uuid' => 'Voucher không hợp lệ.',

            'phuong_thuc_thanh_toan.required' => 'Vui lòng chọn phương thức thanh toán.',
            'phuong_thuc_thanh_toan.in'       => 'Phương thức thanh toán không hợp lệ (chỉ hỗ trợ: vnpay, momo, cod).',

            'giam_voucher.numeric' => 'Giảm voucher phải là số.',
            'giam_voucher.min'     => 'Giảm voucher không được nhỏ hơn 0.',

            'giam_diem.numeric' => 'Giảm điểm phải là số.',
            'giam_diem.min'     => 'Giảm điểm không được nhỏ hơn 0.',

            'items.required' => 'Giỏ hàng trống.',
        ];
    }
}
