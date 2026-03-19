<?php

namespace App\Http\Requests\Ticket;

use App\Models\JobCategory;
use Illuminate\Foundation\Http\FormRequest;

class UpdateTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('edit_tickets');
    }

    public function rules(): array
    {
        $rules = [
            'vendor_id'            => 'required|exists:vendors,id',
            'vendor_ticket_ref_no' => 'nullable|string|max:100',
            'vendor_branch_id'     => 'required|exists:vendor_branches,id',
            'state_id'             => 'required|exists:states,id',
            'city_id'              => 'required|exists:cities,id',
            'tid'                  => 'required|string|max:100',
            'merchant_name'        => 'required|string|max:255',
            'merchant_address'     => 'required|string|max:2000',
            'contact_number'       => 'required|string|max:50',
            'job_category_id'      => 'required|exists:job_categories,id',
            'job_type_id'          => 'required|exists:job_types,id',
            'price'                => 'nullable|numeric|min:0',
            'supervisor_id'        => 'required|exists:users,id',
            'technician_id'        => 'nullable|exists:users,id',
            'priority'             => 'required|in:low,normal,high,urgent',
            'sla_hours'            => 'nullable|integer|min:1|max:720',
            'description'          => 'required|string|max:5000',
            'expected_start_date'  => 'nullable|date',
            'expected_end_date'    => 'nullable|date|after_or_equal:expected_start_date',
            // Claim
            'mileage'              => 'nullable|numeric|min:0',
            'mileage_remarks'      => 'nullable|string|max:500',
            'toll'                 => 'nullable|numeric|min:0',
            'standby_meal'         => 'nullable|numeric|min:0',
        ];

        // Dynamic validation based on job_category slug
        $categoryId = $this->input('job_category_id');
        if ($categoryId) {
            $category = JobCategory::find($categoryId);
            if ($category) {
                switch ($category->slug) {
                    case JobCategory::SLUG_TERMINAL:
                        $rules['terminal_id'] = 'required|string|max:100';
                        $rules['router_id']   = 'nullable';
                        break;
                    case JobCategory::SLUG_ROUTER:
                        $rules['router_id']   = 'required|string|max:100';
                        $rules['terminal_id'] = 'nullable';
                        break;
                    case JobCategory::SLUG_PROJECT:
                        $rules['terminal_id'] = 'nullable|string|max:100';
                        $rules['router_id']   = 'nullable|string|max:100';
                        break;
                    case JobCategory::SLUG_ACCESSORIES:
                    default:
                        $rules['terminal_id'] = 'nullable';
                        $rules['router_id']   = 'nullable';
                        break;
                }
            }
        }

        $rules['old_terminal_id'] = 'nullable|string|max:100';

        return $rules;
    }

    public function messages(): array
    {
        return [
            'terminal_id.required'  => 'Terminal ID is required for this job category.',
            'router_id.required'    => 'Router ID is required for this job category.',
            'expected_end_date.after_or_equal' => 'Expected end date must be after or equal to start date.',
        ];
    }
}
