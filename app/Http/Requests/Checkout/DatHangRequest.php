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
            'khach_hang_id' => 'required|integer|exists:KhachHang,id',
            'ten_nguoi_nhan' => 'required|string|max:150',
            'so_dien_thoai'  => 'required|string|max:20',
            'dia_chi'        => 'nullable|string|max:255',
            'ghi_chu'        => 'nullable|string|max:255',
            'voucher_id'     => 'nullable|uuid',
            'phuong_thuc_thanh_toan' => 'required|in:vnpay,momo,cod',

            'items' => 'required|array|min:1',
            'items.*.san_pham_id'  => 'required|string|max:36',
            'items.*.ten_san_pham' => 'required|string|max:255',
            'items.*.gia'          => 'required|numeric|min:0',
            'items.*.vat'          => 'nullable|numeric|min:0|max:100',
            'items.*.giam_gia'     => 'nullable|numeric|min:0',
            'items.*.so_luong'     => 'required|integer|min:1',

            'phi_van_chuyen' => 'nullable|numeric|min:0',
            'giam_voucher'   => 'nullable|numeric|min:0',
            'giam_diem'      => 'nullable|numeric|min:0',
        ];
    }

    public function messages(): array
    {
        return [
            'items.required' => 'Giỏ hàng trống.',
        ];
    }
}
