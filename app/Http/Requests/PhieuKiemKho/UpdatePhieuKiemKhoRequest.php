<?php

namespace App\Http\Requests\PhieuKiemKho;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePhieuKiemKhoRequest extends FormRequest
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
    public function rules()
    {
        return [
            'ngay_kiem' => 'nullable|date',
            'ghi_chu' => 'nullable|string',
        ];
    }
}
