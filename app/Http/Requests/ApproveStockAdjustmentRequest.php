<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ApproveStockAdjustmentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $stockAdjustment = $this->route('stock_adjustment');
        
        if ($this->input('action') === 'approve') {
            return $this->user()->can('approve', $stockAdjustment);
        }
        
        if ($this->input('action') === 'reject') {
            return $this->user()->can('reject', $stockAdjustment);
        }
        
        return false;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'action' => ['required', 'in:approve,reject'],
            'remarks' => ['required_if:action,reject', 'nullable', 'string', 'max:500'],
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'remarks' => 'rejection remarks',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'remarks.required_if' => 'Rejection remarks are required when rejecting an adjustment.',
        ];
    }
}
