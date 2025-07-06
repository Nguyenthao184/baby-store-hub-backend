<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
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
           'email' => 'required|email|unique:TaiKhoan,email',
            'password' => 'required|min:6|confirmed',
            'hoTen' => 'required|string|max:255',
            'sdt' => 'nullable|regex:/^[0-9]{10}$/',
            'diaChi' => 'nullable|string|max:255',
            'ngaySinh' => 'nullable|date',
        ];
    }
    public function messages()
    {
        return [
            'email.required' => 'Email không được để trống',
            'email.email' => 'Email không đúng định dạng',
            'email.unique' => 'Email đã tồn tại',
            'password.required' => 'Mật khẩu không được để trống',
            'password.min' => 'Mật khẩu ít nhất 6 ký tự',
            'password.confirmed' => 'Mật khẩu xác nhận không đúng',
            'hoTen.required' => 'Họ tên không được để trống',
            'sdt.regex' => 'Số điện thoại phải gồm đúng 10 chữ số',
        ];
    }
}
