<?php

namespace App\Http\Requests\Kho;

use Illuminate\Foundation\Http\FormRequest;

class UpdateKhoRequest extends FormRequest
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
            'tenKho' => 'sometimes|required|string|max:255',
            'diaChi' => 'nullable|string',
            'moTa' => 'nullable|string',
            'trangThai' => 'nullable|boolean',
            'soLuongSanPham' => 'nullable|integer|min:0',
            'nguoiQuanLy' => 'nullable|string|max:255',
            'dienTich' => 'nullable|numeric|min:0'
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
            'tenKho.required' => 'Tên kho không được để trống',
            'tenKho.string' => 'Tên kho phải là chuỗi ký tự',
            'tenKho.max' => 'Tên kho không được vượt quá 255 ký tự',
            'diaChi.string' => 'Địa chỉ phải là chuỗi ký tự',
            'moTa.string' => 'Mô tả phải là chuỗi ký tự',
            'trangThai.boolean' => 'Trạng thái phải là true hoặc false',
            'soLuongSanPham.integer' => 'Số lượng sản phẩm phải là số nguyên',
            'soLuongSanPham.min' => 'Số lượng sản phẩm không được âm',
            'nguoiQuanLy.string' => 'Người quản lý phải là chuỗi ký tự',
            'nguoiQuanLy.max' => 'Tên người quản lý không được vượt quá 255 ký tự',
            'dienTich.numeric' => 'Diện tích phải là số',
            'dienTich.min' => 'Diện tích không được âm'
        ];
    }
}
