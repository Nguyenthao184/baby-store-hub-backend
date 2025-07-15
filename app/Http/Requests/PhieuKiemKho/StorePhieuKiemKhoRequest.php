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
            'ma_phieu_kiem' => 'required|unique:phieu_kiem_kho,ma_phieu_kiem',
            'ngay_kiem' => 'required|date',
            'ghi_chu' => 'nullable|string',
        ];

    } 
    public function messages()
    {
        return [
            'ma_phieu_kiem.required' => 'Mã phiếu kiểm kho không được để trống.',
            'ma_phieu_kiem.unique' => 'Mã phiếu kiểm kho đã tồn tại.',
            'ma_phieu_kiem.max' => 'Mã phiếu kiểm kho không được vượt quá 50 ký tự.',
            'ngay_kiem.required' => 'Ngày kiểm kho không được để trống.',
            'ngay_kiem.date' => 'Ngày kiểm kho không hợp lệ.',
            'ghi_chu.string' => 'Ghi chú phải là chuỗi ký tự.',
        ];
    }
}
