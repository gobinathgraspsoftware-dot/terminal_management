@extends('layouts.app')

@section('title', 'Create Job Category')

@section('content')
<div class="container-fluid">
    {{-- Page Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">Create Job Category</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.job-categories.index') }}">Job Categories</a></li>
                    <li class="breadcrumb-item active">Create</li>
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
            <form id="createJobCategoryForm">
                @csrf
                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="category_name" class="form-label">Category Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="category_name" name="category_name"
                               maxlength="100" required placeholder="Enter category name">
                        <div class="invalid-feedback" id="error-category_name"></div>
                    </div>
                    <div class="col-md-6">
                        <label for="status" class="form-label">Status</label>
                        <select class="form-select" id="status" name="status">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                        <div class="invalid-feedback" id="error-status"></div>
                    </div>
                    <div class="col-12">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description"
                                  rows="3" maxlength="500" placeholder="Optional description"></textarea>
                        <div class="invalid-feedback" id="error-description"></div>
                    </div>
                </div>

                <div class="mt-4">
                    <button type="submit" class="btn btn-primary" id="btnSubmit">
                        <i class="bi bi-check-lg me-1"></i> Create Job Category
                    </button>
                    <a href="{{ route('admin.job-categories.index') }}" class="btn btn-light ms-2">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    $('#createJobCategoryForm').on('submit', function(e) {
        e.preventDefault();

        // Clear previous errors
        $('.is-invalid').removeClass('is-invalid');
        $('.invalid-feedback').text('');

        var btn = $('#btnSubmit');
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Creating...');

        $.ajax({
            url: '{{ route("admin.job-categories.store") }}',
            type: 'POST',
            data: $(this).serialize(),
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            success: function(response) {
                if (response.success) {
                    showToast(response.message, 'success');
                    window.location.href = '{{ route("admin.job-categories.index") }}';
                } else {
                    showToast(response.message || 'Something went wrong.', 'error');
                    btn.prop('disabled', false).html('<i class="bi bi-check-lg me-1"></i> Create Job Category');
                }
            },
            error: function(xhr) {
                btn.prop('disabled', false).html('<i class="bi bi-check-lg me-1"></i> Create Job Category');
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
