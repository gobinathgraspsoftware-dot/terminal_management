<?php

namespace App\Http\Requests\Ticket;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('edit_tickets');
    }

    public function rules(): array
    {
        return [
            'vendor_id' => 'required|exists:vendors,id',
            'vendor_branch_id' => 'nullable|exists:vendor_branches,id',
            'state_id' => 'nullable|exists:states,id',
            'city_id' => 'nullable|exists:cities,id',
            'supervisor_id' => 'required|exists:users,id',
            'technician_id' => 'nullable|exists:users,id',
            'job_type_id' => 'required|exists:job_types,id',
            'priority' => 'required|in:low,normal,high,urgent',
            'description' => 'required|string|max:5000',
            'sla_hours' => 'nullable|integer|min:1|max:720',
        ];
    }
}
