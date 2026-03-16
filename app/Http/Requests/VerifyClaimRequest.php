<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class VerifyClaimRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'total_amount'  => 'required|numeric|min:0',
            'admin_remarks' => 'nullable|string|max:2000',
        ];
    }

    public function messages(): array
    {
        return [
            'total_amount.required' => 'Claim amount is required.',
            'total_amount.min'      => 'Claim amount cannot be negative.',
        ];
    }
}
