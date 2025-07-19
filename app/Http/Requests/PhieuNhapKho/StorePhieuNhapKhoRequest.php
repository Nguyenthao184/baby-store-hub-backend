<?php

namespace App\Http\Requests\PhieuNhapKho;

use Illuminate\Foundation\Http\FormRequest;

class StorePhieuNhapKhoRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'ngay_nhap' => 'required|date',
            'nha_cung_cap_id' => 'nullable|integer|exists:nha_cung_cap,id',
            'ghi_chu' => 'nullable|string',
            'trang_thai' => 'nullable|in:phieu_tam,da_nhap,da_huy',
            'chiTiet' => 'required|array|min:1',
            'chiTiet.*.san_pham_id' => 'required|integer|exists:san_pham,id',
            'chiTiet.*.so_luong_nhap' => 'required|integer|min:1',
            'chiTiet.*.gia_nhap' => 'required|numeric|min:0',
            'chiTiet.*.thue_nhap' => 'nullable|numeric|min:0',
        ];
    }

    public function messages(): array
    {
        return [
            'ngay_nhap.required' => 'Vui lòng nhập ngày nhập kho.',
            'chiTiet.required' => 'Cần nhập ít nhất một sản phẩm.',
            'chiTiet.*.san_pham_id.required' => 'Thiếu ID sản phẩm.',
            'chiTiet.*.so_luong_nhap.min' => 'Số lượng phải lớn hơn 0.',
        ];
    }
}
