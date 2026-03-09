<?php

namespace App\Http\Requests\Admin\JobType;

use Illuminate\Foundation\Http\FormRequest;

class UpdateJobTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('job_type'));
    }

    public function rules(): array
    {
        $id = $this->route('job_type')->id;

        return [
            'job_title'   => 'required|string|max:100|unique:job_types,job_title,' . $id,
            'description' => 'nullable|string|max:500',
            'status'      => 'nullable|in:active,inactive',
        ];
    }
}
