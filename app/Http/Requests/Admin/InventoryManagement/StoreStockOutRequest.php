<?php

namespace App\Http\Requests\Admin\InventoryManagement;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreStockOutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasPermissionTo('create_stock_out');
    }

    public function rules(): array
    {
        return [
            'stock_out_date'       => 'required|date',
            'out_type'             => ['required', Rule::in(['direct_to_site', 'wastage', 'return_to_vendor', 'donation', 'other'])],
            'from_depot_id'        => 'required_without:from_technician_id|nullable|exists:depots,id',
            'from_technician_id'   => 'required_without:from_depot_id|nullable|exists:users,id',
            'to_site_id'           => 'required_if:out_type,direct_to_site|nullable|exists:sites,id',
            'to_vendor_id'         => 'required_if:out_type,return_to_vendor|nullable|exists:vendors,id',
            'remarks'              => 'nullable|string|max:2000',
            'lines'                => 'required|array|min:1',
            'lines.*.model_id'     => 'required|exists:terminal_models,id',
            'lines.*.serial_id'    => 'nullable|exists:inventory_serials,id',
            'lines.*.serial_no'    => 'nullable|string|max:100',
            'lines.*.quantity'     => 'required|numeric|min:0.0001',
            'lines.*.condition'    => ['nullable', Rule::in(['new', 'good', 'damaged', 'defective'])],
            'lines.*.remarks'      => 'nullable|string|max:500',
        ];
    }

    public function attributes(): array
    {
        return [
            'stock_out_date'       => 'stock out date',
            'out_type'             => 'out type',
            'from_depot_id'        => 'source depot',
            'from_technician_id'   => 'source technician',
            'to_site_id'           => 'destination site',
            'to_vendor_id'         => 'destination vendor',
            'lines.*.model_id'     => 'item model',
        ];
    }
}
