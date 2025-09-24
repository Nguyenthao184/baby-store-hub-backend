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
            'khach_hang_id' => ['nullable','string','max:64'],

            'ten_nguoi_nhan' => 'required|string|max:150',
            'so_dien_thoai'  => 'required|string|max:20',
            'dia_chi'        => 'nullable|string|max:255',
            'ghi_chu'        => 'nullable|string|max:255',
            'voucher_id'     => 'nullable|uuid',
            'phuong_thuc_thanh_toan' => 'required|in:vnpay,momo,cod',

            'phi_van_chuyen' => 'nullable|numeric|min:0',
            'giam_voucher'   => 'nullable|numeric|min:0',
            'giam_diem'      => 'nullable|numeric|min:0',

            'to_district_id' => 'required|integer',
            'to_ward_code'   => 'required|string',
            'items.*.weight' => 'nullable|integer|min:10', // gram, tùy bạn

        ];
    }

    public function messages(): array
    {
        return [
            'items.required' => 'Giỏ hàng trống.',
        ];
    }
}
