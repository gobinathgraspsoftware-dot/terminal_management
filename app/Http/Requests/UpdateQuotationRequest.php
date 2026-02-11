<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Quotation;

class UpdateQuotationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $quotation = $this->route('quotation');
        return $this->user()->can('update', $quotation);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'quotation_date' => ['required', 'date'],
            'quotation_type' => ['required', 'in:customer,vendor'],
            'client_id' => ['required_if:quotation_type,customer', 'nullable', 'exists:clients,id'],
            'vendor_id' => ['required_if:quotation_type,vendor', 'nullable', 'exists:vendors,id'],
            'reference' => ['nullable', 'string', 'max:255'],
            'valid_until' => ['required', 'date', 'after:quotation_date'],
            'currency' => ['required', 'string', 'max:10'],
            'terms_conditions' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
            'discount_amount' => ['nullable', 'numeric', 'min:0'],

            // Line items validation
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.item_type' => ['required', 'in:model,charge,custom'],
            'lines.*.model_id' => ['required_if:lines.*.item_type,model', 'nullable', 'exists:terminal_models,id'],
            'lines.*.charge_id' => ['required_if:lines.*.item_type,charge', 'nullable', 'exists:charge_catalog,id'],
            'lines.*.description' => ['required', 'string', 'max:500'],
            'lines.*.quantity' => ['required', 'numeric', 'min:0.0001'],
            'lines.*.unit' => ['nullable', 'string', 'max:50'],
            'lines.*.unit_price' => ['required', 'numeric', 'min:0'],
            'lines.*.discount_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'lines.*.discount_amount' => ['nullable', 'numeric', 'min:0'],
            'lines.*.tax_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'lines.*.remarks' => ['nullable', 'string'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'quotation_date.required' => 'Quotation date is required.',
            'quotation_type.required' => 'Quotation type is required.',
            'quotation_type.in' => 'Invalid quotation type selected.',
            'client_id.required_if' => 'Client is required for customer quotations.',
            'vendor_id.required_if' => 'Vendor is required for vendor quotations.',
            'valid_until.required' => 'Valid until date is required.',
            'valid_until.after' => 'Valid until date must be after quotation date.',
            'lines.required' => 'At least one line item is required.',
            'lines.min' => 'At least one line item is required.',
            'lines.*.item_type.required' => 'Item type is required for each line.',
            'lines.*.model_id.required_if' => 'Model is required when item type is model.',
            'lines.*.charge_id.required_if' => 'Charge is required when item type is charge.',
            'lines.*.description.required' => 'Description is required for each line.',
            'lines.*.quantity.required' => 'Quantity is required for each line.',
            'lines.*.quantity.min' => 'Quantity must be greater than zero.',
            'lines.*.unit_price.required' => 'Unit price is required for each line.',
            'lines.*.discount_percent.max' => 'Discount percent cannot exceed 100%.',
            'lines.*.tax_rate.max' => 'Tax rate cannot exceed 100%.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'quotation_date' => 'quotation date',
            'quotation_type' => 'quotation type',
            'client_id' => 'client',
            'vendor_id' => 'vendor',
            'valid_until' => 'valid until date',
            'currency' => 'currency',
            'discount_amount' => 'header discount',
            'lines.*.item_type' => 'item type',
            'lines.*.quantity' => 'quantity',
            'lines.*.unit_price' => 'unit price',
            'lines.*.discount_percent' => 'discount percent',
            'lines.*.tax_rate' => 'tax rate',
        ];
    }

    /**
     * Handle a failed authorization attempt.
     */
    protected function failedAuthorization(): void
    {
        $quotation = $this->route('quotation');
        
        if ($quotation->isConvertedToPO()) {
            throw new \Illuminate\Auth\Access\AuthorizationException(
                'Cannot edit quotation that has been converted to Purchase Order.'
            );
        }

        if (!$quotation->isEditable()) {
            throw new \Illuminate\Auth\Access\AuthorizationException(
                'Quotation can only be edited when in Draft or Pending Approval status.'
            );
        }

        throw new \Illuminate\Auth\Access\AuthorizationException(
            'You are not authorized to edit this quotation.'
        );
    }
}
