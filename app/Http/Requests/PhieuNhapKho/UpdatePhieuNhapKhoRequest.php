<?php

namespace App\Http\Requests\PhieuNhapKho;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePhieuNhapKhoRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
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
            'ghi_chu' => 'nullable|string',
            'trang_thai' => 'required|in:phieu_tam,da_nhap,da_huy',
            'chiTiet' => 'required|array|min:1',
            'chiTiet.*.san_pham_id' => 'required|string|exists:SanPham,id',
            'chiTiet.*.so_luong_nhap' => 'required|integer|min:1',
            'chiTiet.*.gia_nhap' => 'required|numeric|min:0',
            'chiTiet.*.thue_nhap' => 'nullable|numeric|min:0',
        ];
    }

    public function messages(): array
    {
        return [
            'ngay_nhap.required' => 'Ngày nhập kho là bắt buộc.',
            'chiTiet.required' => 'Cần nhập ít nhất một sản phẩm.',
        ];
    }
}
