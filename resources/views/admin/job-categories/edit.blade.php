@extends('layouts.app')

@section('title', 'Edit Job Category')

@section('content')
<div class="container-fluid">
    {{-- Page Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">Edit Job Category</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.job-categories.index') }}">Job Categories</a></li>
                    <li class="breadcrumb-item active">Edit</li>
                </ol>
            </nav>
        </div>
        <a href="{{ route('admin.job-categories.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Back
        </a>
    </div>

    {{-- Form --}}
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <form id="editJobCategoryForm">
                @csrf
                @method('PUT')
                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="category_name" class="form-label">Category Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="category_name" name="category_name"
                               maxlength="100" required value="{{ $jobCategory->category_name }}"
                               placeholder="Enter category name">
                        <div class="invalid-feedback" id="error-category_name"></div>
                    </div>
                    <div class="col-md-6">
                        <label for="status" class="form-label">Status</label>
                        <select class="form-select" id="status" name="status">
                            <option value="active" {{ $jobCategory->status === 'active' ? 'selected' : '' }}>Active</option>
                            <option value="inactive" {{ $jobCategory->status === 'inactive' ? 'selected' : '' }}>Inactive</option>
                        </select>
                        <div class="invalid-feedback" id="error-status"></div>
                    </div>
                    <div class="col-12">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description"
                                  rows="3" maxlength="500" placeholder="Optional description">{{ $jobCategory->description }}</textarea>
                        <div class="invalid-feedback" id="error-description"></div>
                    </div>
                </div>

                <div class="mt-4 d-flex align-items-center">
                    <button type="submit" class="btn btn-primary" id="btnSubmit">
                        <i class="bi bi-check-lg me-1"></i> Update Job Category
                    </button>
                    <a href="{{ route('admin.job-categories.index') }}" class="btn btn-light ms-2">Cancel</a>
                    <small class="text-muted ms-auto">
                        Last updated: {{ $jobCategory->updated_at->format('d M Y, h:i A') }}
                    </small>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    $('#editJobCategoryForm').on('submit', function(e) {
        e.preventDefault();

        // Clear previous errors
        $('.is-invalid').removeClass('is-invalid');
        $('.invalid-feedback').text('');

        var btn = $('#btnSubmit');
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Updating...');

        $.ajax({
            url: '{{ route("admin.job-categories.update", $jobCategory->id) }}',
            type: 'PUT',
            data: $(this).serialize(),
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            success: function(response) {
                if (response.success) {
                    showToast(response.message, 'success');
                    window.location.href = '{{ route("admin.job-categories.index") }}';
                } else {
                    showToast(response.message || 'Something went wrong.', 'error');
                    btn.prop('disabled', false).html('<i class="bi bi-check-lg me-1"></i> Update Job Category');
                }
            },
            error: function(xhr) {
                btn.prop('disabled', false).html('<i class="bi bi-check-lg me-1"></i> Update Job Category');
                if (xhr.status === 422) {
                    var errors = xhr.responseJSON.errors;
                    $.each(errors, function(field, messages) {
                        $('#' + field).addClass('is-invalid');
                        $('#error-' + field).text(messages[0]);
                    });
                } else {
                    showToast(xhr.responseJSON?.message || 'An error occurred.', 'error');
                }
            }
        });
    });
});
</script>
@endpush
