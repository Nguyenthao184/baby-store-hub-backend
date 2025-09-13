<?php

namespace App\Http\Requests\GioHang;

use Illuminate\Foundation\Http\FormRequest;

class ThemVaoGioRequest extends FormRequest
{
    public function authorize(): bool 
    { 
        return true; 
    }

    public function rules(): array
    {
        return [
            // Bắt buộc có, kiểu UUID 36 ký tự
            'san_pham_id' => ['required','uuid','exists:SanPham,id'],
            
            // Bắt buộc có, số nguyên >= 1
            'so_luong'    => ['required','integer','min:1'],
        ];
    }

    public function attributes(): array
    {
        return [
            'san_pham_id' => 'sản phẩm',
            'so_luong'    => 'số lượng',
        ];
    }

    public function messages(): array
    {
        return [
            'san_pham_id.required' => 'Vui lòng chọn :attribute.',
            'san_pham_id.uuid'     => ':attribute không đúng định dạng.',
            'san_pham_id.exists'   => ':attribute không tồn tại trong hệ thống.',
            'so_luong.required'    => 'Vui lòng nhập :attribute.',
            'so_luong.integer'     => ':attribute phải là số nguyên.',
            'so_luong.min'         => ':attribute phải lớn hơn hoặc bằng 1.',
        ];
    }
}
