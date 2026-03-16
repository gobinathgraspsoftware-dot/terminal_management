<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BulkPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'claim_ids'   => 'required|array|min:1',
            'claim_ids.*' => 'integer|exists:claims,id',
        ];
    }

    public function messages(): array
    {
        return [
            'claim_ids.required' => 'Please select at least one claim.',
            'claim_ids.min'      => 'Please select at least one claim.',
        ];
    }
}
