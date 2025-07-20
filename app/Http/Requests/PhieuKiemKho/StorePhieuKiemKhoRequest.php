<?php

namespace App\Http\Requests\PhieuKiemKho;


use Illuminate\Foundation\Http\FormRequest;

class StorePhieuKiemKhoRequest extends FormRequest
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
            'ma_phieu_kiem' => 'required|string|max:50|unique:phieu_kiem_kho,ma_phieu_kiem',
            'ngay_kiem' => 'required|date',
            'ghi_chu' => 'nullable|string',
                'chi_tiet_san_pham' => 'nullable|array',
        'chi_tiet_san_pham.*.san_pham_id' => 'required_with:chi_tiet_san_pham|string|exists:SanPham,id',
        'chi_tiet_san_pham.*.so_luong_ly_thuyet' => 'required_with:chi_tiet_san_pham|numeric|min:0',
        'chi_tiet_san_pham.*.so_luong_thuc_te' => 'required_with:chi_tiet_san_pham|numeric|min:0',
        ];
    }

    public function messages()
    {
        return [
            'ma_phieu_kiem.required' => 'Vui lòng nhập mã phiếu kiểm.',
            'ma_phieu_kiem.unique' => 'Mã phiếu kiểm đã tồn tại.',
            'ngay_kiem.required' => 'Vui lòng chọn ngày kiểm.',
            'chi_tiet_san_pham.required' => 'Phải có ít nhất một sản phẩm kiểm kê.',
            'chi_tiet_san_pham.*.san_pham_id.required' => 'Mỗi sản phẩm cần có ID.',
            'chi_tiet_san_pham.*.so_luong_ly_thuyet.required' => 'Phải nhập số lượng lý thuyết.',
            'chi_tiet_san_pham.*.so_luong_thuc_te.required' => 'Phải nhập số lượng thực tế.',
        ];
    }
}
