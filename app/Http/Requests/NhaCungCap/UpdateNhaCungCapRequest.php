<?php

namespace App\Http\Requests\NhaCungCap;

use Illuminate\Foundation\Http\FormRequest;

class UpdateNhaCungCapRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tenNhaCungCap' => 'sometimes|string|max:255',
            'maSoThue' => 'nullable|string|max:50',
            'sdt' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:500',
            'diaChi' => 'nullable|string',
            'trangThai' => 'boolean'
        ];
    }

    public function messages()
    {
        return [
            'email.email' => 'Email không đúng định dạng',
        ];
    }
}
