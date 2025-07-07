<?php

namespace App\Http\Requests\SanPham;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSanPhamRequest extends FormRequest
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
        $id = $this->route('id'); // Lấy ID từ route parameter

        $rules = [
            'tenSanPham' => 'required|string|max:255',
            'VAT' => 'nullable|numeric|min:0|max:100',
             'giaBan' => 'required|numeric|min:0',
            'soLuongTon' => 'required|integer|min:0',
            'moTa' => 'nullable|string',
            'danhMuc_id' => 'required|string|exists:DanhMuc,id',
            'kho_id' => 'nullable|string|exists:Kho,id',
            'hinhAnh' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048'
        ];
        if ($id) {
            $rules['maSKU'] = [
                'required',
                'string',
                'max:100',
                'unique:SanPham,maSKU,' . $id . ',id'
            ];
        } else {
            $rules['maSKU'] = [
                'required',
                'string',
                'max:100',
                'unique:SanPham,maSKU'
            ];
        }

        return $rules;
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'tenSanPham.required' => 'Tên sản phẩm không được để trống',
            'tenSanPham.string' => 'Tên sản phẩm phải là chuỗi ký tự',
            'tenSanPham.max' => 'Tên sản phẩm không được vượt quá 255 ký tự',
            'maSKU.required' => 'Mã SKU không được để trống',
            'maSKU.string' => 'Mã SKU phải là chuỗi ký tự',
            'maSKU.max' => 'Mã SKU không được vượt quá 100 ký tự',
            'maSKU.unique' => 'Mã SKU đã tồn tại trong hệ thống',
            'VAT.numeric' => 'VAT phải là số',
            'VAT.min' => 'VAT không được âm',
            'VAT.max' => 'VAT không được vượt quá 100%',
            'giaBan.required' => 'Giá bán không được để trống',
            'giaBan.numeric' => 'Giá bán phải là số',
            'giaBan.min' => 'Giá bán không được âm',
            'soLuongTon.required' => 'Số lượng tồn không được để trống',
            'soLuongTon.integer' => 'Số lượng tồn phải là số nguyên',
            'soLuongTon.min' => 'Số lượng tồn không được âm',
            'moTa.string' => 'Mô tả phải là chuỗi ký tự',
            'danhMuc_id.required' => 'Danh mục không được để trống',
            'danhMuc_id.string' => 'ID danh mục phải là chuỗi ký tự',
            'danhMuc_id.exists' => 'Danh mục không tồn tại',
            'kho_id.string' => 'ID kho phải là chuỗi ký tự',
            'kho_id.exists' => 'Kho không tồn tại',
            'hinhAnh.image' => 'File phải là hình ảnh',
            'hinhAnh.mimes' => 'Hình ảnh phải có định dạng: jpeg, png, jpg, gif, svg',
            'hinhAnh.max' => 'Kích thước hình ảnh không được vượt quá 2MB'
        ];
    }
}
