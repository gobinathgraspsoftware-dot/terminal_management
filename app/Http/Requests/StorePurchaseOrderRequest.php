<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePurchaseOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create_purchase_orders');
    }

    public function rules(): array
    {
        return [
            'vendor_id' => 'required|exists:vendors,id',
            'quotation_id' => 'nullable|exists:quotations,id',
            'po_date' => 'required|date',
            'reference' => 'nullable|string|max:100',
            'delivery_address' => 'required|string|max:500',
            'delivery_date' => 'required|date|after_or_equal:po_date',
            'receiving_depot_id' => 'required|exists:depots,id',
            'payment_terms' => 'nullable|string|max:500',
            'terms_conditions' => 'nullable|string|max:2000',
            'notes' => 'nullable|string|max:1000',
            'currency' => 'required|in:MYR,USD,SGD,EUR',
            'discount_amount' => 'nullable|numeric|min:0',
            
            // Lines
            'lines' => 'required|array|min:1',
            'lines.*.line_no' => 'required|integer|min:1',
            'lines.*.model_id' => 'required|exists:terminal_models,id',
            'lines.*.description' => 'nullable|string|max:500',
            'lines.*.quantity_ordered' => 'required|numeric|min:0.01',
            'lines.*.unit' => 'required|string|max:20',
            'lines.*.unit_price' => 'required|numeric|min:0',
            'lines.*.discount_percent' => 'nullable|numeric|min:0|max:100',
            'lines.*.discount_amount' => 'nullable|numeric|min:0',
            'lines.*.tax_rate' => 'nullable|numeric|min:0|max:100',
            'lines.*.tax_amount' => 'nullable|numeric|min:0',
            'lines.*.line_total' => 'required|numeric|min:0',
            'lines.*.remarks' => 'nullable|string|max:500',
        ];
    }

    public function attributes(): array
    {
        return [
            'vendor_id' => 'vendor',
            'receiving_depot_id' => 'receiving depot',
            'delivery_address' => 'delivery address',
            'delivery_date' => 'delivery date',
            'lines.*.model_id' => 'terminal model',
            'lines.*.quantity_ordered' => 'quantity',
            'lines.*.unit_price' => 'unit price',
        ];
    }
}
