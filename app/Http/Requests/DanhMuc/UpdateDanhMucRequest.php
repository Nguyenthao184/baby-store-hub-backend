<?php

namespace App\Http\Requests\DanhMuc;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDanhMucRequest extends FormRequest
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
            'tenDanhMuc' => 'sometimes|required|string|max:255',
            'moTa' => 'nullable|string',
            'soLuongSanPham' => 'nullable|integer|min:0',
            'hinhAnh' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'nhaCungCap' => 'nullable|string|max:255',
            'idKho' => 'nullable|string|exists:Kho,id'
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'tenDanhMuc.required' => 'Tên danh mục không được để trống',
            'tenDanhMuc.string' => 'Tên danh mục phải là chuỗi ký tự',
            'tenDanhMuc.max' => 'Tên danh mục không được vượt quá 255 ký tự',
            'moTa.string' => 'Mô tả phải là chuỗi ký tự',
            'soLuongSanPham.integer' => 'Số lượng sản phẩm phải là số nguyên',
            'soLuongSanPham.min' => 'Số lượng sản phẩm không được âm',
            'hinhAnh.image' => 'File phải là hình ảnh',
            'hinhAnh.mimes' => 'Hình ảnh phải có định dạng: jpeg, png, jpg, gif, svg',
            'hinhAnh.max' => 'Kích thước hình ảnh không được vượt quá 2MB',
            'nhaCungCap.string' => 'Nhà cung cấp phải là chuỗi ký tự',
            'nhaCungCap.max' => 'Tên nhà cung cấp không được vượt quá 255 ký tự',
            'idKho.string' => 'ID kho phải là chuỗi ký tự',
            'idKho.exists' => 'Kho không tồn tại'
        ];
    }
}
