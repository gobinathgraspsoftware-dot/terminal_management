@extends('layouts.app')

@section('title', 'Create Charge')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="mb-4">
        <h1 class="h3 mb-0">Create New Charge</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.charge-catalog.index') }}">Charge Catalog</a></li>
                <li class="breadcrumb-item active">Create</li>
            </ol>
        </nav>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0">Charge Information</h5>
                </div>
                <div class="card-body">
                    <form id="createChargeForm">
                        @csrf

                        <!-- Charge Code -->
                        <div class="mb-3">
                            <label for="charge_code" class="form-label">
                                Charge Code <span class="text-muted">(Auto-generated if left empty)</span>
                            </label>
                            <input type="text" 
                                   class="form-control" 
                                   id="charge_code" 
                                   name="charge_code" 
                                   value="{{ $nextCode }}"
                                   placeholder="CHG000001">
                            <div class="invalid-feedback"></div>
                        </div>

                        <!-- Charge Name -->
                        <div class="mb-3">
                            <label for="charge_name" class="form-label">
                                Charge Name <span class="text-danger">*</span>
                            </label>
                            <input type="text" 
                                   class="form-control" 
                                   id="charge_name" 
                                   name="charge_name" 
                                   required
                                   placeholder="Enter charge name">
                            <div class="invalid-feedback"></div>
                        </div>

                        <!-- Charge Type -->
                        <div class="mb-3">
                            <label for="charge_type" class="form-label">
                                Charge Type <span class="text-danger">*</span>
                            </label>
                            <select class="form-select" id="charge_type" name="charge_type" required>
                                <option value="">-- Select Type --</option>
                                <option value="installation">Installation</option>
                                <option value="service">Service</option>
                                <option value="hardware">Hardware</option>
                                <option value="accessory">Accessory</option>
                                <option value="labour">Labour</option>
                                <option value="transport">Transport</option>
                                <option value="other">Other</option>
                            </select>
                            <div class="invalid-feedback"></div>
                        </div>

                        <!-- Description -->
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" 
                                      id="description" 
                                      name="description" 
                                      rows="3"
                                      placeholder="Enter charge description"></textarea>
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
                                    <input class="form-check-input" 
                                           type="checkbox" 
                                           id="is_taxable" 
                                           name="is_taxable" 
                                           value="1" 
                                           checked>
                                    <label class="form-check-label" for="is_taxable">
                                        This charge is taxable
                                    </label>
                                </div>

                                <!-- Tax Rate -->
                                <div id="taxRateSection">
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
                                           value="6.00"
                                           required>
                                    <div class="invalid-feedback"></div>
                                </div>
                            </div>
                        </div>

                        <!-- Status -->
                        <div class="mb-3">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-select" id="status" name="status">
                                <option value="active" selected>Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                            <div class="invalid-feedback"></div>
                        </div>

                        <!-- Form Actions -->
                        <div class="d-flex justify-content-between pt-3 border-top">
                            <a href="{{ route('admin.charge-catalog.index') }}" class="btn btn-secondary">
                                <i class="bi bi-x-circle me-1"></i> Cancel
                            </a>
                            <button type="submit" class="btn btn-primary" id="submitBtn">
                                <i class="bi bi-check-circle me-1"></i> Create Charge
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Help Card -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0">Quick Tips</h5>
                </div>
                <div class="card-body">
                    <ul class="list-unstyled mb-0">
                        <li class="mb-2">
                            <i class="bi bi-info-circle text-primary me-2"></i>
                            <strong>Charge Code:</strong> Auto-generated if left empty (CHG000001 format)
                        </li>
                        <li class="mb-2">
                            <i class="bi bi-info-circle text-primary me-2"></i>
                            <strong>Charge Types:</strong>
                            <ul class="mt-1">
                                <li>Installation: Terminal setup charges</li>
                                <li>Service: Maintenance and repair</li>
                                <li>Hardware: Equipment costs</li>
                                <li>Accessory: Cables, adapters, etc.</li>
                                <li>Labour: Technician time charges</li>
                                <li>Transport: Travel costs</li>
                            </ul>
                        </li>
                        <li class="mb-2">
                            <i class="bi bi-info-circle text-primary me-2"></i>
                            <strong>Tax Rate:</strong> Standard SST is 6% in Malaysia
                        </li>
                        <li>
                            <i class="bi bi-info-circle text-primary me-2"></i>
                            <strong>Unit:</strong> Specify pricing unit for clarity (per unit, per hour, per km, etc.)
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
    $('#createChargeForm').on('submit', function(e) {
        e.preventDefault();

        // Clear previous errors
        $('.is-invalid').removeClass('is-invalid');
        $('.invalid-feedback').text('');

        const submitBtn = $('#submitBtn');
        const originalBtnText = submitBtn.html();
        
        submitBtn.prop('disabled', true)
                 .html('<span class="spinner-border spinner-border-sm me-1"></span> Creating...');

        $.ajax({
            url: '{{ route('admin.charge-catalog.store') }}',
            type: 'POST',
            data: $(this).serialize(),
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message);
                    setTimeout(function() {
                        window.location.href = '{{ route('admin.charge-catalog.index') }}';
                    }, 1000);
                } else {
                    toastr.error(response.message);
                    submitBtn.prop('disabled', false).html(originalBtnText);
                }
            },
            error: function(xhr) {
                submitBtn.prop('disabled', false).html(originalBtnText);

                if (xhr.status === 422) {
                    const errors = xhr.responseJSON.errors;
                    $.each(errors, function(field, messages) {
                        const input = $('[name="' + field + '"]');
                        input.addClass('is-invalid');
                        input.siblings('.invalid-feedback').text(messages[0]);
                    });
                    toastr.error('Please correct the errors in the form');
                } else {
                    toastr.error('An error occurred. Please try again.');
                }
            }
        });
    });
});
</script>
@endpush
