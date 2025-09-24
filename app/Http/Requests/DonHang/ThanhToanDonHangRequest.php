<?php

namespace App\Http\Requests\DonHang;

use Illuminate\Foundation\Http\FormRequest;

class ThanhToanDonHangRequest extends FormRequest
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
     */
    public function rules(): array
    {
        return [
            'khachHang_id' => 'required|string|exists:KhachHang,id',
            'phuongThuc' => 'required|string|in:cod,bank,card',
            'tenNguoiNhan' => 'required|string|max:150',
            'soDienThoai' => 'required|string|regex:/^0[0-9]{9,10}$/',
            'sanPhams' => 'required|array|min:1',
            'sanPhams.*.id' => 'required|string|exists:SanPham,id',
            'sanPhams.*.tenSanPham' => ['required','string','max:255'],
            'sanPhams.*.soLuong' => 'required|integer|min:1',
            'sanPhams.*.giaBan' => 'required|numeric|min:0',
            'sanPhams.*.giamGia' => 'nullable|numeric|min:0',
        ];
    }

    /**
     * Custom error messages for validation rules.
     */
    public function messages(): array
    {
        return [
            // Khách hàng
            'khachHang_id.required' => 'Vui lòng chọn khách hàng.',
            'khachHang_id.exists' => 'Khách hàng không tồn tại trong hệ thống.',

            // Phương thức thanh toán
            'phuongThuc.required' => 'Vui lòng chọn phương thức thanh toán.',
            'phuongThuc.in' => 'Phương thức thanh toán không hợp lệ.',

            // Tên người nhận
            'tenNguoiNhan.required' => 'Vui lòng nhập tên người nhận.',
            'tenNguoiNhan.max' => 'Tên người nhận không được vượt quá 150 ký tự.',

            // Số điện thoại
            'soDienThoai.required' => 'Vui lòng nhập số điện thoại.',
            'soDienThoai.regex' => 'Số điện thoại không đúng định dạng.',

            // Sản phẩm
            'sanPhams.required' => 'Danh sách sản phẩm không được trống.',
            'sanPhams.min' => 'Danh sách sản phẩm phải có ít nhất 1 sản phẩm.',
            'sanPhams.*.id.required' => 'Mã sản phẩm là bắt buộc.',
            'sanPhams.*.id.exists' => 'Sản phẩm không tồn tại trong hệ thống.',
            'sanPhams.*.soLuong.required' => 'Số lượng sản phẩm là bắt buộc.',
            'sanPhams.*.soLuong.min' => 'Số lượng sản phẩm phải lớn hơn 0.',
            'sanPhams.*.giaBan.required' => 'Giá bán sản phẩm là bắt buộc.',
            'sanPhams.*.giaBan.min' => 'Giá bán sản phẩm không được nhỏ hơn 0.',
            'sanPhams.*.giamGia.min' => 'Giảm giá không được nhỏ hơn 0.',
        ];
    }
}
