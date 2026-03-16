<?php

namespace App\Http\Requests;

use App\Models\Claim;
use Illuminate\Foundation\Http\FormRequest;

class StoreOtherClaimRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'claim_type_label' => 'required|string|max:100',
            'technician_id'    => 'required|integer|exists:users,id',
            'description'      => 'required|string|max:2000',
            'claim_amount'     => 'required|numeric|min:0.01',
            'ticket_id'        => 'nullable|integer|exists:tickets,id',
            'remarks'          => 'nullable|string|max:2000',
            'attachments'      => 'nullable|array|max:5',
            'attachments.*'    => 'file|mimes:pdf,png,jpg,jpeg|max:5120',
        ];
    }

    public function messages(): array
    {
        return [
            'claim_type_label.required' => 'Please select a claim type.',
            'technician_id.required'    => 'Please select a technician.',
            'description.required'      => 'Please enter a description.',
            'claim_amount.required'     => 'Please enter the claim amount.',
            'claim_amount.min'          => 'Claim amount must be at least RM 0.01.',
            'attachments.*.mimes'       => 'Attachments must be PDF, PNG, or JPG files.',
            'attachments.*.max'         => 'Each attachment must not exceed 5MB.',
        ];
    }
}
