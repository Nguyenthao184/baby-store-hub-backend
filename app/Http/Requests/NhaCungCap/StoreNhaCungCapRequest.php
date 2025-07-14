<?php

namespace App\Http\Requests\NhaCungCap;

use Illuminate\Foundation\Http\FormRequest;

class StoreNhaCungCapRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tenNhaCungCap' => 'required|string|max:255',
            'maSoThue' => 'nullable|string|max:50',
            'sdt' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:100',
            'diaChi' => 'nullable|string',
        ];
    }

    public function messages()
    {
        return [
            'tenNhaCungCap.required' => 'Tên nhà cung cấp không được để trống',
            'email.email' => 'Email không đúng định dạng',
        ];
    }
}
