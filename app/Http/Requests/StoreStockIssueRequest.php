<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\StockIssue;

class StoreStockIssueRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create_stock_issues');
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $rules = [
            'issue_date' => ['required', 'date', 'before_or_equal:today'],
            'issue_type' => ['required', 'in:' . StockIssue::TYPE_ISSUE_TO_TECH . ',' . StockIssue::TYPE_RETURN_FROM_TECH],
            'remarks' => ['nullable', 'string', 'max:1000'],
            
            // Line items
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.model_id' => ['required', 'exists:terminal_models,id'],
            'lines.*.serial_id' => ['nullable', 'exists:inventory_serials,id'],
            'lines.*.serial_no' => ['nullable', 'string', 'max:100'],
            'lines.*.quantity' => ['required', 'numeric', 'min:0.0001', 'max:9999.9999'],
            'lines.*.remarks' => ['nullable', 'string', 'max:500'],
        ];

        // Issue to Technician validation
        if ($this->issue_type === StockIssue::TYPE_ISSUE_TO_TECH) {
            $rules['from_depot_id'] = ['required', 'exists:depots,id'];
            $rules['to_technician_id'] = ['required', 'exists:users,id'];
        }

        // Return from Technician validation
        if ($this->issue_type === StockIssue::TYPE_RETURN_FROM_TECH) {
            $rules['from_technician_id'] = ['required', 'exists:users,id'];
            $rules['to_depot_id'] = ['required', 'exists:depots,id'];
        }

        return $rules;
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'issue_date' => 'issue date',
            'issue_type' => 'issue type',
            'from_depot_id' => 'from depot',
            'to_technician_id' => 'to technician',
            'from_technician_id' => 'from technician',
            'to_depot_id' => 'to depot',
            'lines' => 'line items',
            'lines.*.model_id' => 'model',
            'lines.*.serial_id' => 'serial',
            'lines.*.quantity' => 'quantity',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'lines.required' => 'At least one line item is required.',
            'lines.min' => 'At least one line item is required.',
            'issue_date.before_or_equal' => 'Issue date cannot be in the future.',
        ];
    }
}
