<?php

namespace App\Http\Requests\PhieuKiemKho;

use Illuminate\Foundation\Http\FormRequest;

class StoreChiTietPhieuKiemKhoRequest extends FormRequest
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
            'san_pham_id' => 'required|integer',
            'so_luong_ly_thuyet' => 'required|integer|min:0',
            'so_luong_thuc_te' => 'required|integer|min:0',
        ];
    }
     public function messages()
    {
        return [
            'san_pham_id.required' => 'Vui lòng chọn sản phẩm.',
            'san_pham_id.exists' => 'Sản phẩm không tồn tại.',
            'so_luong_ly_thuyet.required' => 'Số lượng lý thuyết không được để trống.',
            'so_luong_ly_thuyet.numeric' => 'Số lượng lý thuyết phải là số.',
            'so_luong_ly_thuyet.min' => 'Số lượng lý thuyết không được nhỏ hơn 0.',
            'so_luong_thuc_te.required' => 'Số lượng thực tế không được để trống.',
            'so_luong_thuc_te.numeric' => 'Số lượng thực tế phải là số.',
            'so_luong_thuc_te.min' => 'Số lượng thực tế không được nhỏ hơn 0.',
        ];
    }
}
