<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Quotation;

class ApproveQuotationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $quotation = $this->route('quotation');
        
        // Check if user has approve permission
        if (!$this->user()->can('approve', $quotation)) {
            return false;
        }

        // Ensure quotation can be approved
        return $quotation->canBeApproved();
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'action' => ['required', 'in:approve,reject'],
            'reason' => ['required_if:action,reject', 'nullable', 'string', 'max:1000'],
            'notes' => ['nullable', 'string'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'action.required' => 'Approval action is required.',
            'action.in' => 'Invalid approval action. Must be approve or reject.',
            'reason.required_if' => 'Rejection reason is required when rejecting a quotation.',
            'reason.max' => 'Rejection reason cannot exceed 1000 characters.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'action' => 'approval action',
            'reason' => 'rejection reason',
            'notes' => 'notes',
        ];
    }

    /**
     * Handle a failed authorization attempt.
     */
    protected function failedAuthorization(): void
    {
        $quotation = $this->route('quotation');
        
        if (!$quotation->canBeApproved()) {
            throw new \Illuminate\Auth\Access\AuthorizationException(
                'This quotation is not in Pending Approval status and cannot be approved/rejected.'
            );
        }

        if ($quotation->created_by === $this->user()->id) {
            throw new \Illuminate\Auth\Access\AuthorizationException(
                'You cannot approve your own quotation.'
            );
        }

        throw new \Illuminate\Auth\Access\AuthorizationException(
            'You are not authorized to approve/reject this quotation.'
        );
    }
}
