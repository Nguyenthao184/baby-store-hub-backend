<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator;
use Carbon\Carbon;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    // Chuẩn hóa dữ liệu trước khi validate
    protected function prepareForValidation(): void
    {
        // Trim tất cả field text
        $this->merge([
            'email'   => $this->email ? trim($this->email) : null,
            'hoTen'   => $this->hoTen ? trim($this->hoTen) : null,
            'sdt'     => $this->sdt ? preg_replace('/\s+/', '', $this->sdt) : null,
            'diaChi'  => $this->diaChi ? trim($this->diaChi) : null,
        ]);

        // Nếu người dùng gửi dd/mm/YYYY thì convert về YYYY-mm-dd
        if ($this->filled('ngaySinh')) {
            $v = trim($this->ngaySinh);
            try {
                if (preg_match('~^\d{2}/\d{2}/\d{4}$~', $v)) {
                    $this->merge(['ngaySinh' => Carbon::createFromFormat('d/m/Y', $v)->format('Y-m-d')]);
                }
            } catch (\Throwable $e) { /* để validate bắt lỗi tiếp */ }
        }
    }

    public function rules(): array
    {
        return [
            'email'     => 'bail|required|email:filter|unique:TaiKhoan,email',
            'password'  => 'required|string|min:6|max:64|confirmed',
            'hoTen'     => 'required|string|max:255',
            // VN phone: bắt đầu 0 hoặc +84, tổng 10 số (linh hoạt hơn)
            'sdt'       => ['nullable','string','regex:/^(0|\+84)\d{9}$/'],
            'diaChi'    => 'nullable|string|max:255',
            'ngaySinh'  => 'nullable|date|before:today|after:1900-01-01',
        ];
    }

    // Trả JSON 422 khi fail (khỏi bị redirect)
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => 'Validation error',
            'errors'  => $validator->errors(),
        ], 422));
    }

    public function messages(): array
    {
        return [
            'email.required'    => 'Email không được để trống',
            'email.email'       => 'Email không đúng định dạng',
            'email.unique'      => 'Email đã tồn tại',
            'password.required' => 'Mật khẩu không được để trống',
            'password.min'      => 'Mật khẩu ít nhất 6 ký tự',
            'password.confirmed'=> 'Mật khẩu xác nhận không đúng',
            'hoTen.required'    => 'Họ tên không được để trống',
            'sdt.regex'         => 'Số điện thoại phải bắt đầu bằng 0 hoặc +84 và đủ 10 số',
            'ngaySinh.date'     => 'Ngày sinh không hợp lệ (định dạng YYYY-MM-DD hoặc dd/mm/YYYY)',
            'ngaySinh.before'   => 'Ngày sinh phải trước ngày hôm nay',
            'ngaySinh.after'    => 'Ngày sinh phải sau 1900-01-01',
        ];
    }

    public function attributes(): array
    {
        return ['ngaySinh' => 'ngày sinh'];
    }
}
