@extends('layouts.app')

@section('title', 'Edit Job Type')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">Edit Job Type</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.job-types.index') }}">Job Types</a></li>
                    <li class="breadcrumb-item active">Edit</li>
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
                @method('PUT')
                <div class="row">
                    <div class="col-md-8">
                        <div class="mb-3">
                            <label for="job_title" class="form-label">Job Title <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="job_title" name="job_title"
                                   value="{{ $jobType->job_title }}" required maxlength="100">
                            <div class="invalid-feedback" id="error-job_title"></div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Slug</label>
                            <input type="text" class="form-control" value="{{ $jobType->slug }}" disabled>
                            <small class="text-muted">Auto-generated from job title</small>
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description"
                                      rows="3" maxlength="500">{{ $jobType->description }}</textarea>
                            <div class="invalid-feedback" id="error-description"></div>
                        </div>

                        <div class="mb-3">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-select" id="status" name="status">
                                <option value="active" {{ $jobType->status === 'active' ? 'selected' : '' }}>Active</option>
                                <option value="inactive" {{ $jobType->status === 'inactive' ? 'selected' : '' }}>Inactive</option>
                            </select>
                            <div class="invalid-feedback" id="error-status"></div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="card bg-light border-0">
                            <div class="card-body">
                                <h6 class="card-title">Info</h6>
                                <p class="mb-1"><small class="text-muted">Created:</small><br>{{ $jobType->created_at->format('d M Y, h:i A') }}</p>
                                <p class="mb-0"><small class="text-muted">Updated:</small><br>{{ $jobType->updated_at->format('d M Y, h:i A') }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <hr>
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary" id="btn-submit">
                        <i class="bi bi-check-lg me-1"></i> Update Job Type
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

        $('.is-invalid').removeClass('is-invalid');

        $.ajax({
            url: '{{ route("admin.job-types.update", $jobType->id) }}',
            type: 'POST',
            data: $(this).serialize(),
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            success: function(response) {
                if (response.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Updated!',
                        text: response.message,
                        timer: 1500,
                        showConfirmButton: false
                    }).then(function() {
                        window.location.href = '{{ route("admin.job-types.index") }}';
                    });
                }
            },
            error: function(xhr) {
                $btn.prop('disabled', false).html('<i class="bi bi-check-lg me-1"></i> Update Job Type');

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
