<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreStockAdjustmentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create_stock_adjustments');
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'adjustment_date' => ['required', 'date', 'before_or_equal:today'],
            'adjustment_type' => ['required', 'in:count,correction,write_off,other'],
            'depot_id' => ['required', 'exists:depots,id'],
            'reason' => ['required', 'string', 'max:1000'],
            'status' => ['sometimes', 'in:draft,pending_approval'],
            
            // Lines
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.model_id' => ['required', 'exists:terminal_models,id'],
            'lines.*.serial_id' => ['nullable', 'exists:inventory_serials,id'],
            'lines.*.serial_no' => ['nullable', 'string', 'max:100'],
            'lines.*.system_quantity' => ['required', 'numeric', 'min:0'],
            'lines.*.physical_quantity' => ['required', 'numeric', 'min:0'],
            'lines.*.unit_cost' => ['nullable', 'numeric', 'min:0'],
            'lines.*.variance_value' => ['nullable', 'numeric'],
            'lines.*.remarks' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'adjustment_date' => 'adjustment date',
            'adjustment_type' => 'adjustment type',
            'depot_id' => 'depot',
            'lines.*.model_id' => 'terminal model',
            'lines.*.serial_id' => 'serial number',
            'lines.*.system_quantity' => 'system quantity',
            'lines.*.physical_quantity' => 'physical quantity',
            'lines.*.unit_cost' => 'unit cost',
            'lines.*.variance_value' => 'variance value',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'lines.required' => 'At least one adjustment line is required.',
            'lines.min' => 'At least one adjustment line is required.',
            'adjustment_date.before_or_equal' => 'Adjustment date cannot be in the future.',
        ];
    }
}
