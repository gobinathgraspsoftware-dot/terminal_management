<?php

namespace App\Http\Requests\Admin\JobCategory;

use Illuminate\Foundation\Http\FormRequest;

class StoreJobCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', \App\Models\JobCategory::class);
    }

    public function rules(): array
    {
        return [
            'category_name' => 'required|string|max:100|unique:job_categories,category_name',
            'description'   => 'nullable|string|max:500',
            'status'        => 'nullable|in:active,inactive',
        ];
    }
}
