@extends('layouts.app')

@section('title', 'Create Job Type')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">Create Job Type</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.job-types.index') }}">Job Types</a></li>
                    <li class="breadcrumb-item active">Create</li>
                </ol>
            </nav>
        </div>
        <a href="{{ route('admin.job-types.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Back
        </a>
    </div>

    <!-- Form -->
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <form id="job-type-form">
                @csrf
                <div class="row">
                    <div class="col-md-8">
                        <div class="mb-3">
                            <label for="job_title" class="form-label">Job Title <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="job_title" name="job_title"
                                   placeholder="e.g. Installation, Service, Repair" required maxlength="100">
                            <div class="invalid-feedback" id="error-job_title"></div>
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description"
                                      rows="3" placeholder="Brief description of this job type" maxlength="500"></textarea>
                            <div class="invalid-feedback" id="error-description"></div>
                        </div>

                        <div class="mb-3">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-select" id="status" name="status">
                                <option value="active" selected>Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                            <div class="invalid-feedback" id="error-status"></div>
                        </div>
                    </div>
                </div>

                <hr>
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary" id="btn-submit">
                        <i class="bi bi-check-lg me-1"></i> Create Job Type
                    </button>
                    <a href="{{ route('admin.job-types.index') }}" class="btn btn-outline-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(function() {
    $('#job-type-form').on('submit', function(e) {
        e.preventDefault();

        var $btn = $('#btn-submit');
        $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Saving...');

        // Clear previous errors
        $('.is-invalid').removeClass('is-invalid');

        $.ajax({
            url: '{{ route("admin.job-types.store") }}',
            type: 'POST',
            data: $(this).serialize(),
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            success: function(response) {
                if (response.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Created!',
                        text: response.message,
                        timer: 1500,
                        showConfirmButton: false
                    }).then(function() {
                        window.location.href = '{{ route("admin.job-types.index") }}';
                    });
                }
            },
            error: function(xhr) {
                $btn.prop('disabled', false).html('<i class="bi bi-check-lg me-1"></i> Create Job Type');

                if (xhr.status === 422) {
                    var errors = xhr.responseJSON.errors;
                    $.each(errors, function(field, messages) {
                        $('#' + field).addClass('is-invalid');
                        $('#error-' + field).text(messages[0]);
                    });
                } else {
                    showToast('error', xhr.responseJSON?.message || 'An error occurred');
                }
            }
        });
    });
});
</script>
@endpush
