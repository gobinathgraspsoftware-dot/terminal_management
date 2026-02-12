<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ClosePurchaseOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('close_purchase_orders');
    }

    public function rules(): array
    {
        return [
            'closure_reason' => 'required|string|max:500',
        ];
    }
}
