<?php

namespace App\Http\Requests\HoaDon;

use Illuminate\Foundation\Http\FormRequest;

class UpdateHoaDonRequest extends FormRequest
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
            'tongTienHang' => 'required|numeric|min:0',
            'giamGiaSanPham' => 'nullable|numeric|min:0',
            'thueVAT' => 'nullable|numeric|min:0',
            'tongThanhToan' => 'required|numeric|min:0',
            'phuongThucThanhToan' => 'required|string|in:TienMat,ChuyenKhoan,The',
            'ghiChu' => 'nullable|string',
            'trangThai' => 'nullable|string|in:completed,cancelled,pending',

            'xoaSanPhamIds' => 'nullable|array',
            'xoaSanPhamIds.*' => 'string|exists:SanPham,id',

            'sanPhams' => 'nullable|array',
            'sanPhams.*.id' => 'required_with:sanPhams|string|exists:SanPham,id',
            'sanPhams.*.soLuong' => 'required_with:sanPhams|integer|min:1',
            'sanPhams.*.giaBan' => 'required_with:sanPhams|numeric|min:0',
            'sanPhams.*.giamGia' => 'nullable|numeric|min:0',
            'sanPhams.*.tongTien' => 'required_with:sanPhams|numeric|min:0',
        ];
    }

    public function messages(): array
    {
        return [
            'tongTienHang.required' => 'Tổng tiền hàng là bắt buộc.',
            'tongThanhToan.required' => 'Tổng thanh toán là bắt buộc.',
            'phuongThucThanhToan.in' => 'Phương thức thanh toán không hợp lệ.',
            'sanPhams.*.id.exists' => 'Sản phẩm không tồn tại.',
            'sanPhams.*.soLuong.min' => 'Số lượng sản phẩm phải lớn hơn 0.',
        ];
    }
}
