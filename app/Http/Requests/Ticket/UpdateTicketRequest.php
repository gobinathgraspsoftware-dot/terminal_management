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
            'vendor_id'            => 'required|exists:vendors,id',
            'vendor_ticket_ref_no' => 'required|string|max:100',
            'vendor_branch_id'     => 'required|exists:vendor_branches,id',
            'state_id'             => 'required|exists:states,id',
            'city_id'              => 'required|exists:cities,id',
            'tid'                  => 'required|string|max:100',
            'merchant_name'        => 'required|string|max:255',
            'merchant_address'     => 'required|string|max:2000',
            'contact_number'       => 'required|string|max:50',
            'job_type_id'          => 'required|exists:job_types,id',
            'charge_id'            => 'nullable|exists:charge_catalog,id',
            'supervisor_id'        => 'required|exists:users,id',
            'technician_id'        => 'nullable|exists:users,id',
            'priority'             => 'required|in:low,normal,high,urgent',
            'sla_hours'            => 'nullable|integer|min:1|max:720',
            'description'          => 'required|string|max:5000',
            // Claim
            'mileage'              => 'nullable|numeric|min:0',
            'mileage_remarks'      => 'nullable|string|max:500',
            'toll'                 => 'nullable|numeric|min:0',
            'standby_meal'         => 'nullable|numeric|min:0',
        ];
    }
}
