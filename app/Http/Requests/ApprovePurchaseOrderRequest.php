<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ApprovePurchaseOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('approve_purchase_orders');
    }

    public function rules(): array
    {
        return [
            'approval_notes' => 'nullable|string|max:500',
        ];
    }
}
