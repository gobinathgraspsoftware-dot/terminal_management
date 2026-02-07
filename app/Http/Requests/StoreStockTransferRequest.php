<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreStockTransferRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create_stock_transfers');
    }

    public function rules(): array
    {
        return [
            'transfer_date' => 'required|date',
            'from_depot_id' => 'required|exists:depots,id|different:to_depot_id',
            'to_depot_id' => 'required|exists:depots,id',
            'remarks' => 'nullable|string|max:1000',
            'lines' => 'required|array|min:1',
            'lines.*.model_id' => 'required|exists:terminal_models,id',
            'lines.*.serial_id' => 'nullable|exists:inventory_serials,id',
            'lines.*.serial_no' => 'nullable|string|max:100',
            'lines.*.quantity_requested' => 'required|numeric|min:0.0001|max:99999.9999',
            'lines.*.remarks' => 'nullable|string|max:500',
        ];
    }

    public function messages(): array
    {
        return [
            'from_depot_id.different' => 'Source and destination depots must be different',
            'lines.required' => 'At least one item is required',
            'lines.*.model_id.required' => 'Model is required for all lines',
            'lines.*.quantity_requested.required' => 'Quantity is required for all lines',
        ];
    }
}
