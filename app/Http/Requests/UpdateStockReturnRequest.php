<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\StockIssueLine;

class UpdateStockReturnRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $stockReturn = $this->route('stock_return');
        return $this->user()->can('update', $stockReturn);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'issue_date' => 'required|date|before_or_equal:today',
            'from_technician_id' => 'required|exists:users,id',
            'to_depot_id' => 'required|exists:depots,id',
            'remarks' => 'nullable|string|max:500',
            
            'lines' => 'required|array|min:1',
            'lines.*.model_id' => 'required|exists:terminal_models,id',
            'lines.*.serial_id' => 'nullable|exists:inventory_serials,id',
            'lines.*.serial_no' => 'nullable|string|max:100',
            'lines.*.quantity' => 'required|numeric|min:0.0001|max:9999.9999',
            'lines.*.condition' => [
                'required',
                'in:' . implode(',', [
                    StockIssueLine::CONDITION_GOOD,
                    StockIssueLine::CONDITION_DAMAGED,
                    StockIssueLine::CONDITION_DEFECTIVE
                ])
            ],
            'lines.*.remarks' => 'nullable|string|max:255',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'issue_date' => 'return date',
            'from_technician_id' => 'technician',
            'to_depot_id' => 'receiving depot',
            'lines.*.model_id' => 'model',
            'lines.*.serial_id' => 'serial number',
            'lines.*.quantity' => 'quantity',
            'lines.*.condition' => 'condition',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'lines.required' => 'At least one item must be added to the return',
            'lines.*.condition.required' => 'Condition is required for each item',
            'lines.*.condition.in' => 'Invalid condition selected',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Ensure lines is an array even if empty
        if (!$this->has('lines')) {
            $this->merge(['lines' => []]);
        }
    }
}
