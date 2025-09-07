<?php

namespace App\Http\Requests\KhachHang;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\KhachHang;
use Illuminate\Validation\Rule;


class UpdateKhachHangRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check();;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        // Cập nhật hồ sơ: POST /api/khach-hang/profile
        if ($this->is('api/khach-hang/profile') && $this->isMethod('post')) {
            return $this->profileRules();
        }

        // Thay avatar: POST /api/khach-hang/profile/avatar
        if ($this->is('api/khach-hang/profile/avatar') && $this->isMethod('post')) {
            return $this->avatarRules();
        }

        // Đổi mật khẩu: POST /api/khach-hang/profile/password
        if ($this->is('api/khach-hang/profile/password') && $this->isMethod('post')) {
            return $this->passwordRules();
        }
        return [];
    }

    /**
     * Rule cho cập nhật hồ sơ
     */
    protected function profileRules(): array
    {
        $kh = KhachHang::where('taiKhoan_id', auth()->id())->first();
        $ignoreId = $kh?->id;

        return [
            'hoTen'    => ['required','string','max:255'],
            'email'    => ['required','email','max:255',
                Rule::unique('KhachHang','email')->ignore($ignoreId)],
            'diaChi'   => ['required','string','max:255'],
            'ngaySinh' => ['required','date','before:today'],
            'sdt'      => ['required','regex:/^(0|\+84)[0-9]{8,12}$/'],
        ];
    }

    /**
     * Rule cho thay avatar
     */
    protected function avatarRules(): array
    {
        return [
            'avatar' => ['required','image','mimes:jpg,jpeg,png,webp','max:2048'], // 2MB
        ];
    }

    /**
     * Rule cho đổi mật khẩu
     */
    protected function passwordRules(): array
    {
        return [
            'current_password' => ['required', 'current_password:sanctum'],
            'password'         => ['required','string','min:8','confirmed'],

        ];
    }
    
    /**
     * Thông báo lỗi chung
     */
    public function messages(): array
    {
        return [
            // Hồ sơ
            'hoTen.required'    => 'Vui lòng nhập họ và tên.',
            'hoTen.max'         => 'Họ và tên không được vượt quá 255 ký tự.',
            'email.required'    => 'Vui lòng nhập email.',
            'email.email'       => 'Email không hợp lệ.',
            'email.unique'      => 'Email này đã tồn tại, vui lòng chọn email khác.',
            'diaChi.required'   => 'Vui lòng nhập địa chỉ.',
            'diaChi.max'        => 'Địa chỉ không được vượt quá 255 ký tự.',
            'ngaySinh.required' => 'Vui lòng nhập ngày sinh.',
            'ngaySinh.date'     => 'Ngày sinh không hợp lệ.',
            'ngaySinh.before'   => 'Ngày sinh phải nhỏ hơn ngày hiện tại.',
            'sdt.required'      => 'Vui lòng nhập số điện thoại.',
            'sdt.regex'         => 'Số điện thoại không đúng định dạng.',

            // Avatar
            'avatar.required'  => 'Vui lòng chọn ảnh để tải lên.',
            'avatar.image'     => 'Tệp tải lên phải là ảnh.',
            'avatar.mimes'     => 'Ảnh phải có định dạng: jpg, jpeg, png hoặc webp.',
            'avatar.max'       => 'Ảnh không được lớn hơn 2MB.',

            // Mật khẩu
            'current_password.required'          => 'Vui lòng nhập mật khẩu hiện tại.',
            'current_password.current_password'  => 'Mật khẩu hiện tại không đúng.',
            'password.required'   => 'Vui lòng nhập mật khẩu mới.',
            'password.min'        => 'Mật khẩu mới phải có ít nhất 8 ký tự.',
            'password.confirmed'  => 'Xác nhận mật khẩu mới không khớp.',
        ];
    }
}
