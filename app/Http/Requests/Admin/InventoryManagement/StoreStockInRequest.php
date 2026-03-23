<?php

namespace App\Http\Requests\Admin\InventoryManagement;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreStockInRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasPermissionTo('create_stock_in');
    }

    public function rules(): array
    {
        return [
            'stock_in_date'      => 'required|date',
            'depot_id'           => 'required|exists:depots,id',
            'source_type'        => ['required', Rule::in(['manual', 'return_from_site', 'found', 'donation', 'other'])],
            'source_reference'   => 'nullable|string|max:255',
            'remarks'            => 'nullable|string|max:2000',
            'lines'              => 'required|array|min:1',
            'lines.*.model_id'   => 'required|exists:terminal_models,id',
            'lines.*.serial_no'  => 'nullable|string|max:100',
            'lines.*.quantity'   => 'required|numeric|min:0.0001',
            'lines.*.unit_cost'  => 'nullable|numeric|min:0',
            'lines.*.condition'  => ['nullable', Rule::in(['new', 'good', 'damaged', 'defective'])],
            'lines.*.remarks'    => 'nullable|string|max:500',
        ];
    }

    public function attributes(): array
    {
        return [
            'stock_in_date'      => 'stock in date',
            'depot_id'           => 'receiving depot',
            'source_type'        => 'source type',
            'lines.*.model_id'   => 'item model',
            'lines.*.serial_no'  => 'serial number',
            'lines.*.quantity'   => 'quantity',
        ];
    }
}
