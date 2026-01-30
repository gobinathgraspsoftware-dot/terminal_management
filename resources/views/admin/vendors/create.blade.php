@extends('layouts.app')

@section('title', 'Create Vendor - TMS')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-0">
                <i class="bi bi-truck me-2"></i>Create New Vendor
            </h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.vendors.index') }}">Vendors</a></li>
                    <li class="breadcrumb-item active">Create</li>
                </ol>
            </nav>
        </div>
    </div>

    <form id="vendorForm" autocomplete="off">
        @csrf
        
        <div class="row">
            <div class="col-lg-8">
                <!-- Basic Information -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white">
                        <h5 class="mb-0"><i class="bi bi-info-circle me-2"></i>Basic Information</h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Vendor Code</label>
                                <input type="text" class="form-control" name="vendor_code" value="{{ $nextCode }}" readonly>
                                <small class="text-muted">Auto-generated</small>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Vendor Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="vendor_name" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Vendor Type <span class="text-danger">*</span></label>
                                <select class="form-select" name="vendor_type" required>
                                    <option value="">Select Type</option>
                                    @foreach($vendorTypes as $key => $label)
                                        <option value="{{ $key }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Company Name</label>
                                <input type="text" class="form-control" name="company_name">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Registration No</label>
                                <input type="text" class="form-control" name="registration_no">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Tax ID</label>
                                <input type="text" class="form-control" name="tax_id">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Address Information -->
                @include('components.address-form')

                <!-- Contact Information -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white">
                        <h5 class="mb-0"><i class="bi bi-person me-2"></i>Contact Information</h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">PIC Name</label>
                                <input type="text" class="form-control" name="pic_name">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">PIC Email</label>
                                <input type="email" class="form-control" name="pic_email">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">PIC Phone</label>
                                <input type="text" class="form-control" name="pic_phone">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Bank Details -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white">
                        <h5 class="mb-0"><i class="bi bi-bank me-2"></i>Bank Details</h5>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle me-2"></i>
                            <small>If providing bank details, all fields are required.</small>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">Bank Name</label>
                                <input type="text" class="form-control" name="bank_name">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Account Number</label>
                                <input type="text" class="form-control" name="bank_account_no">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Account Name</label>
                                <input type="text" class="form-control" name="bank_account_name">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <!-- Payment Terms -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white">
                        <h5 class="mb-0"><i class="bi bi-calendar me-2"></i>Payment Terms</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Payment Terms (Days) <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" name="payment_terms" value="30" min="0" max="365" required>
                            <small class="text-muted">Default: 30 days</small>
                        </div>
                    </div>
                </div>

                <!-- Status & Notes -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white">
                        <h5 class="mb-0"><i class="bi bi-gear me-2"></i>Settings</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Status <span class="text-danger">*</span></label>
                            <select class="form-select" name="status" required>
                                <option value="active" selected>Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Notes</label>
                            <textarea class="form-control" name="notes" rows="4"></textarea>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-save me-2"></i>Create Vendor
                            </button>
                            <a href="{{ route('admin.vendors.index') }}" class="btn btn-outline-secondary">
                                <i class="bi bi-x-circle me-2"></i>Cancel
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

@endsection

@push('scripts')
<script>
$(document).ready(function() {
    $('#vendorForm').submit(function(e) {
        e.preventDefault();
        
        const submitBtn = $(this).find('button[type="submit"]');
        const originalText = submitBtn.html();
        submitBtn.prop('disabled', true).html('<i class="spinner-border spinner-border-sm me-2"></i>Creating...');
        
        $.ajax({
            url: '{{ route("admin.vendors.store") }}',
            type: 'POST',
            data: $(this).serialize(),
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                console.log('Success response:', response);
                
                if (response.success) {
                    // Show success message
                    if (typeof toastr !== 'undefined') {
                        toastr.success(response.message || 'Vendor created successfully');
                    } else {
                        alert(response.message || 'Vendor created successfully');
                    }
                    
                    // Redirect after a short delay to ensure message is seen
                    setTimeout(function() {
                        window.location.href = response.redirect || '{{ route("admin.vendors.index") }}';
                    }, 500);
                } else {
                    // Show error message
                    if (typeof toastr !== 'undefined') {
                        toastr.error(response.message || 'Failed to create vendor');
                    } else {
                        alert(response.message || 'Failed to create vendor');
                    }
                    submitBtn.prop('disabled', false).html(originalText);
                }
            },
            error: function(xhr) {
                console.error('Error response:', xhr);
                
                let errorMessage = 'Failed to create vendor';
                
                if (xhr.status === 422) {
                    // Validation errors
                    const errors = xhr.responseJSON?.errors || {};
                    for (let field in errors) {
                        if (typeof toastr !== 'undefined') {
                            toastr.error(errors[field][0]);
                        } else {
                            alert(errors[field][0]);
                        }
                    }
                } else {
                    // Other errors
                    errorMessage = xhr.responseJSON?.message || 'Failed to create vendor. Please try again.';
                    if (typeof toastr !== 'undefined') {
                        toastr.error(errorMessage);
                    } else {
                        alert(errorMessage);
                    }
                }
                
                submitBtn.prop('disabled', false).html(originalText);
            }
        });
    });
});
</script>
@endpush
