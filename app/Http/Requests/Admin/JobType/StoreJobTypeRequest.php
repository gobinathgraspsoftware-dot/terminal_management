<?php

namespace App\Http\Requests\Admin\JobType;

use Illuminate\Foundation\Http\FormRequest;

class StoreJobTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', \App\Models\JobType::class);
    }

    public function rules(): array
    {
        return [
            'job_title'   => 'required|string|max:100|unique:job_types,job_title',
            'description' => 'nullable|string|max:500',
            'status'      => 'nullable|in:active,inactive',
        ];
    }
}
