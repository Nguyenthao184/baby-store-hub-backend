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
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'khachHang_id' => 'required|integer|exists:KhachHang,id',
            'phuongThuc' => 'required|string|in:cash,bank,card,TienMat,ChuyenKhoan,The',
            'sanPhams' => 'required|array|min:1',
            'sanPhams.*.id' => 'required|string|exists:SanPham,id',
            'sanPhams.*.soLuong' => 'required|integer|min:1',
            'sanPhams.*.giaBan' => 'required|numeric|min:0',
            'sanPhams.*.giamGia' => 'nullable|numeric|min:0',
        ];
    }

    public function messages(): array
    {
        return [
            'khachHang_id.required' => 'Vui lòng chọn khách hàng.',
            'phuongThuc.in' => 'Phương thức thanh toán không hợp lệ.',
            'sanPhams.required' => 'Danh sách sản phẩm không được trống.',
            'sanPhams.*.id.exists' => 'Sản phẩm không tồn tại trong hệ thống.',
            'sanPhams.*.soLuong.min' => 'Số lượng phải lớn hơn 0.',
        ];
    }
}
