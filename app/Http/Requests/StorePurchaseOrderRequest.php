<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePurchaseOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'vendor_id' => 'required|exists:vendors,id',
            'po_date' => 'required|date',
            'delivery_date' => 'required|date|after_or_equal:po_date',
            'delivery_address' => 'required|string|max:500',
            'receiving_depot_id' => 'required|exists:depots,id',
            'currency' => 'required|in:MYR,USD,SGD,EUR,GBP',
            'reference' => 'nullable|string|max:100',
            'payment_terms' => 'nullable|string|max:200',
            'terms_conditions' => 'nullable|string',
            'notes' => 'nullable|string',
            'quotation_id' => 'nullable|exists:quotations,id',
            
            // Lines must be array and required
            'lines' => 'required|array|min:1',
            'lines.*.model_id' => 'required|exists:terminal_models,id',
            'lines.*.description' => 'nullable|string|max:500',
            'lines.*.quantity_ordered' => 'required|integer|min:1',
            'lines.*.unit' => 'required|in:pcs,unit,set,box,carton',
            'lines.*.unit_price' => 'required|numeric|min:0',
            'lines.*.tax_rate' => 'nullable|numeric|min:0|max:100',
            'lines.*.line_total' => 'required|numeric|min:0',
        ];
    }

    public function messages(): array
    {
        return [
            'vendor_id.required' => 'Please select a vendor',
            'delivery_date.after_or_equal' => 'Delivery date must be on or after PO date',
            'lines.required' => 'Please add at least one line item',
            'lines.array' => 'Lines data is invalid',
            'lines.min' => 'Please add at least one line item',
            'lines.*.model_id.required' => 'Model is required for all lines',
            'lines.*.quantity_ordered.required' => 'Quantity is required for all lines',
            'lines.*.quantity_ordered.min' => 'Quantity must be at least 1',
            'lines.*.unit_price.required' => 'Unit price is required for all lines',
        ];
    }
}
