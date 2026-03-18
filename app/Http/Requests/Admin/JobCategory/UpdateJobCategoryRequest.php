<?php

namespace App\Http\Requests\Admin\JobCategory;

use Illuminate\Foundation\Http\FormRequest;

class UpdateJobCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('job_category'));
    }

    public function rules(): array
    {
        $id = $this->route('job_category')->id;

        return [
            'category_name' => 'required|string|max:100|unique:job_categories,category_name,' . $id,
            'description'   => 'nullable|string|max:500',
            'status'        => 'nullable|in:active,inactive',
        ];
    }
}
