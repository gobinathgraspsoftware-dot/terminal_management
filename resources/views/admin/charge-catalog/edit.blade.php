@extends('layouts.app')

@section('title', 'Edit Job')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="mb-4">
        <h1 class="h3 mb-0">Edit Job</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.charge-catalog.index') }}">Job Catalog</a></li>
                <li class="breadcrumb-item active">Edit</li>
            </ol>
        </nav>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0">Job Information</h5>
                </div>
                <div class="card-body">
                    <form id="editChargeForm">
                        @csrf
                        @method('PUT')

                        <!-- Charge Code -->
                        <div class="mb-3">
                            <label for="charge_code" class="form-label">Job Code</label>
                            <input type="text"
                                   class="form-control"
                                   id="charge_code"
                                   name="charge_code"
                                   value="{{ $charge->charge_code }}"
                                   readonly>
                            <small class="form-text text-muted">Job code cannot be changed</small>
                        </div>

                        <!-- Charge Name -->
                        <div class="mb-3">
                            <label for="charge_name" class="form-label">
                                Job Name <span class="text-danger">*</span>
                            </label>
                            <input type="text"
                                   class="form-control"
                                   id="charge_name"
                                   name="charge_name"
                                   value="{{ $charge->charge_name }}"
                                   required
                                   placeholder="Enter charge name">
                            <div class="invalid-feedback"></div>
                        </div>

                        <!-- Charge Type -->
                        <div class="mb-3">
                            <label for="job_type_id" class="form-label">Job Type <span class="text-danger">*</span></label>
                            <select class="form-select" id="job_type_id" name="job_type_id" required style="width: 100%;">
                                @if($charge->jobType)
                                    <option value="{{ $charge->job_type_id }}" selected>{{ $charge->jobType->job_title }}</option>
                                @endif
                            </select>
                            <div class="invalid-feedback" id="error-job_type_id"></div>
                        </div>

                        <!-- Description -->
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control"
                                      id="description"
                                      name="description"
                                      rows="3"
                                      placeholder="Enter Job description">{{ $charge->description }}</textarea>
                            <div class="invalid-feedback"></div>
                        </div>

                        <div class="row">
                            <!-- Default Price -->
                            <div class="col-md-6 mb-3">
                                <label for="default_price" class="form-label">
                                    Default Price (RM) <span class="text-danger">*</span>
                                </label>
                                <input type="number"
                                       class="form-control"
                                       id="default_price"
                                       name="default_price"
                                       step="0.01"
                                       min="0"
                                       value="{{ $charge->default_price }}"
                                       required
                                       placeholder="0.00">
                                <div class="invalid-feedback"></div>
                            </div>

                            <!-- Unit -->
                            <div class="col-md-6 mb-3">
                                <label for="unit" class="form-label">Unit</label>
                                <input type="text"
                                       class="form-control"
                                       id="unit"
                                       name="unit"
                                       value="{{ $charge->unit }}"
                                       placeholder="per unit, per hour, per terminal, etc.">
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>

                        <!-- Tax Configuration -->
                        <div class="card bg-light mb-3">
                            <div class="card-body">
                                <h6 class="mb-3">Tax Configuration</h6>

                                <!-- Is Taxable -->
                                <div class="form-check mb-3">
                                    <input type="hidden" name="is_taxable" value="0">
                                    <input class="form-check-input"
                                           type="checkbox"
                                           id="is_taxable"
                                           name="is_taxable"
                                           value="1"
                                           {{ $charge->is_taxable ? 'checked' : '' }}>
                                    <label class="form-check-label" for="is_taxable">
                                        This Job is taxable
                                    </label>
                                </div>

                                <!-- Tax Rate -->
                                <div id="taxRateSection" style="{{ $charge->is_taxable ? '' : 'display: none;' }}">
                                    <label for="tax_rate" class="form-label">
                                        Tax Rate (%) <span class="text-danger">*</span>
                                    </label>
                                    <input type="number"
                                           class="form-control"
                                           id="tax_rate"
                                           name="tax_rate"
                                           step="0.01"
                                           min="0"
                                           max="100"
                                           value="{{ $charge->tax_rate }}"
                                           {{ $charge->is_taxable ? 'required' : '' }}>
                                    <div class="invalid-feedback"></div>
                                </div>
                            </div>
                        </div>

                        <!-- Status -->
                        <div class="mb-3">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-select" id="status" name="status">
                                <option value="active" {{ $charge->status == 'active' ? 'selected' : '' }}>Active</option>
                                <option value="inactive" {{ $charge->status == 'inactive' ? 'selected' : '' }}>Inactive</option>
                            </select>
                            <div class="invalid-feedback"></div>
                        </div>

                        <!-- Form Actions -->
                        <div class="d-flex justify-content-between pt-3 border-top">
                            <a href="{{ route('admin.charge-catalog.index') }}" class="btn btn-secondary">
                                <i class="bi bi-x-circle me-1"></i> Cancel
                            </a>
                            <button type="submit" class="btn btn-primary" id="submitBtn">
                                <i class="bi bi-check-circle me-1"></i> Update Job
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Info Card -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0">Job Details</h5>
                </div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-5">Created At:</dt>
                        <dd class="col-sm-7">{{ $charge->created_at->format('Y-m-d H:i') }}</dd>

                        <dt class="col-sm-5">Updated At:</dt>
                        <dd class="col-sm-7">{{ $charge->updated_at->format('Y-m-d H:i') }}</dd>

                        <dt class="col-sm-5">Current Status:</dt>
                        <dd class="col-sm-7">
                            <span class="badge bg-{{ $charge->status == 'active' ? 'success' : 'danger' }}">
                                {{ ucfirst($charge->status) }}
                            </span>
                        </dd>
                    </dl>
                </div>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0">Quick Tips</h5>
                </div>
                <div class="card-body">
                    <ul class="list-unstyled mb-0">
                        <li class="mb-2">
                            <i class="bi bi-info-circle text-primary me-2"></i>
                            Changes to price and tax will only affect new quotations and invoices
                        </li>
                        <li class="mb-2">
                            <i class="bi bi-info-circle text-primary me-2"></i>
                            Setting status to "Inactive" will hide this Job from selection
                        </li>
                        <li>
                            <i class="bi bi-info-circle text-primary me-2"></i>
                            Standard SST rate in Malaysia is 6%
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {

    /* Select2 for Job Type */
    $('#job_type_id').select2({
        theme: 'bootstrap-5',
        placeholder: '-- Select Type --',
        allowClear: true,
        width: '100%',
        ajax: {
            url: '{{ route("admin.charge-catalog.ajax.job-types") }}',
            dataType: 'json',
            delay: 250,
            data: function(params) {
                return { q: params.term || '' };
            },
            processResults: function(data) {
                return { results: data.results };
            },
            cache: true
        },
        minimumInputLength: 0
    });

    // Toggle tax rate field based on is_taxable checkbox
    $('#is_taxable').on('change', function() {
        if ($(this).is(':checked')) {
            $('#taxRateSection').show();
            $('#tax_rate').prop('required', true);
        } else {
            $('#taxRateSection').hide();
            $('#tax_rate').prop('required', false).val('0');
        }
    });

    // Form submission
    $('#editChargeForm').on('submit', function(e) {
        e.preventDefault();

        // Clear previous errors
        $('.is-invalid').removeClass('is-invalid');
        $('.invalid-feedback').text('');

        var submitBtn = $('#submitBtn');
        var originalBtnText = submitBtn.html();
        var formData = $('#editChargeForm').serialize();

        submitBtn.prop('disabled', true)
                 .html('<span class="spinner-border spinner-border-sm me-1"></span> Updating...');

        $.ajax({
            url: '{{ route("admin.charge-catalog.update", $charge->id) }}',
            type: 'POST',
            data: formData,
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    showToast(response.message, 'success');
                    setTimeout(function() {
                        window.location.href = '{{ route("admin.charge-catalog.index") }}';
                    }, 1000);
                } else {
                    showToast(response.message || 'Something went wrong', 'error');
                    submitBtn.prop('disabled', false).html(originalBtnText);
                }
            },
            error: function(xhr) {
                submitBtn.prop('disabled', false).html(originalBtnText);

                if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                    var errors = xhr.responseJSON.errors;
                    $.each(errors, function(field, messages) {
                        var input = $('[name="' + field + '"]');
                        input.addClass('is-invalid');
                        input.siblings('.invalid-feedback').text(messages[0]);
                    });
                    showToast('Please correct the errors in the form', 'error');
                } else {
                    var msg = (xhr.responseJSON && xhr.responseJSON.message)
                              ? xhr.responseJSON.message
                              : 'An error occurred. Please try again.';
                    showToast(msg, 'error');
                }
            }
        });
    });
});
</script>
@endpush
