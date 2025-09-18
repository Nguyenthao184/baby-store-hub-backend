<?php

namespace App\Http\Requests\KhachHang;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\KhachHang;

class StoreKhachHangRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'hoTen' => 'required|string|max:255',
            'sdt'   => 'required|string|max:10|unique:KhachHang,sdt',
        ];
    }

    public function messages(): array
    {
        return [
            'hoTen.required' => 'Vui lòng nhập họ tên.',
            'sdt.required'   => 'Vui lòng nhập số điện thoại.',
            'sdt.max'        => 'Số điện thoại tối đa 10 ký tự.',
            'sdt.unique'     => 'Số điện thoại đã tồn tại.',
        ];
    }
}
