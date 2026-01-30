@extends('layouts.app')

@section('title', 'Edit Vendor - TMS')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-0"><i class="bi bi-pencil me-2"></i>Edit Vendor</h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.vendors.index') }}">Vendors</a></li>
                    <li class="breadcrumb-item active">Edit</li>
                </ol>
            </nav>
        </div>
    </div>

    <form id="vendorForm">
        @csrf
        @method('PUT')
        
        <div class="row">
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white">
                        <h5 class="mb-0"><i class="bi bi-info-circle me-2"></i>Basic Information</h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Vendor Code</label>
                                <input type="text" class="form-control" name="vendor_code" value="{{ $vendor->vendor_code }}" readonly>
                                <small class="text-muted">Cannot be changed</small>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Vendor Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="vendor_name" value="{{ old('vendor_name', $vendor->vendor_name) }}" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Vendor Type <span class="text-danger">*</span></label>
                                <select class="form-select" name="vendor_type" required>
                                    @foreach($vendorTypes as $key => $label)
                                        <option value="{{ $key }}" {{ old('vendor_type', $vendor->vendor_type) == $key ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Company Name</label>
                                <input type="text" class="form-control" name="company_name" value="{{ old('company_name', $vendor->company_name) }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Registration No</label>
                                <input type="text" class="form-control" name="registration_no" value="{{ old('registration_no', $vendor->registration_no) }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Tax ID</label>
                                <input type="text" class="form-control" name="tax_id" value="{{ old('tax_id', $vendor->tax_id) }}">
                            </div>
                        </div>
                    </div>
                </div>

                @include('components.address-form', ['data' => $vendor])

                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white">
                        <h5 class="mb-0"><i class="bi bi-person me-2"></i>Contact Information</h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">PIC Name</label>
                                <input type="text" class="form-control" name="pic_name" value="{{ old('pic_name', $vendor->pic_name) }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">PIC Email</label>
                                <input type="email" class="form-control" name="pic_email" value="{{ old('pic_email', $vendor->pic_email) }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">PIC Phone</label>
                                <input type="text" class="form-control" name="pic_phone" value="{{ old('pic_phone', $vendor->pic_phone) }}">
                            </div>
                        </div>
                    </div>
                </div>

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
                                <input type="text" class="form-control" name="bank_name" value="{{ old('bank_name', $vendor->bank_name) }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Account Number</label>
                                <input type="text" class="form-control" name="bank_account_no" value="{{ old('bank_account_no', $vendor->bank_account_no) }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Account Name</label>
                                <input type="text" class="form-control" name="bank_account_name" value="{{ old('bank_account_name', $vendor->bank_account_name) }}">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white">
                        <h5 class="mb-0"><i class="bi bi-calendar me-2"></i>Payment Terms</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Payment Terms (Days) <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" name="payment_terms" value="{{ old('payment_terms', $vendor->payment_terms) }}" min="0" max="365" required>
                        </div>
                    </div>
                </div>

                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white">
                        <h5 class="mb-0"><i class="bi bi-gear me-2"></i>Settings</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Status <span class="text-danger">*</span></label>
                            <select class="form-select" name="status" required>
                                <option value="active" {{ old('status', $vendor->status) == 'active' ? 'selected' : '' }}>Active</option>
                                <option value="inactive" {{ old('status', $vendor->status) == 'inactive' ? 'selected' : '' }}>Inactive</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Notes</label>
                            <textarea class="form-control" name="notes" rows="4">{{ old('notes', $vendor->notes) }}</textarea>
                        </div>
                    </div>
                </div>

                @include('components.audit-trail', ['model' => $vendor])

                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-save me-2"></i>Update Vendor
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
        submitBtn.prop('disabled', true).html('<i class="spinner-border spinner-border-sm me-2"></i>Updating...');
        
        $.ajax({
            url: '{{ route('admin.vendors.update', $vendor->id) }}',
            type: 'PUT',
            data: $(this).serialize(),
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message);
                    window.location.href = response.redirect;
                } else {
                    toastr.error(response.message);
                    submitBtn.prop('disabled', false).html(originalText);
                }
            },
            error: function(xhr) {
                if (xhr.status === 422) {
                    const errors = xhr.responseJSON.errors;
                    for (let field in errors) {
                        toastr.error(errors[field][0]);
                    }
                } else {
                    toastr.error(xhr.responseJSON?.message || 'Failed to update vendor');
                }
                submitBtn.prop('disabled', false).html(originalText);
            }
        });
    });
});
</script>
@endpush
