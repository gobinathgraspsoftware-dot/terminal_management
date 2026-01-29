@extends('layouts.app')

@section('title', 'Edit Client')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">Edit Client</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.clients.index') }}">Clients</a></li>
                    <li class="breadcrumb-item active">Edit: {{ $client->client_name }}</li>
                </ol>
            </nav>
        </div>
        <div>
            <a href="{{ route('admin.clients.show', $client) }}" class="btn btn-info me-2">
                <i class="bi bi-eye me-1"></i> View Details
            </a>
            <a href="{{ route('admin.clients.index') }}" class="btn btn-secondary">
                <i class="bi bi-arrow-left me-1"></i> Back to List
            </a>
        </div>
    </div>

    <!-- Edit Form -->
    <form id="client_form" method="POST">
        @csrf
        @method('PUT')
        <div class="row">
            <!-- Main Information -->
            <div class="col-md-8">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Client Information</h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Client Code</label>
                                <input type="text" class="form-control" name="client_code" value="{{ $client->client_code }}" readonly>
                                <small class="text-muted">Cannot be changed</small>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Status <span class="text-danger">*</span></label>
                                <select class="form-select" name="status" required>
                                    <option value="active" {{ old('status', $client->status) == 'active' ? 'selected' : '' }}>Active</option>
                                    <option value="inactive" {{ old('status', $client->status) == 'inactive' ? 'selected' : '' }}>Inactive</option>
                                    <option value="suspended" {{ old('status', $client->status) == 'suspended' ? 'selected' : '' }}>Suspended</option>
                                </select>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">Client Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="client_name" value="{{ old('client_name', $client->client_name) }}" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Company Name</label>
                                <input type="text" class="form-control" name="company_name" value="{{ old('company_name', $client->company_name) }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Partner (Optional)</label>
                                <select class="form-select" name="partner_id">
                                    <option value="">-- No Partner --</option>
                                    @foreach($partners as $partner)
                                    <option value="{{ $partner->id }}" {{ old('partner_id', $client->partner_id) == $partner->id ? 'selected' : '' }}>
                                        [{{ $partner->partner_code }}] {{ $partner->partner_name }}
                                    </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Registration Number</label>
                                <input type="text" class="form-control" name="registration_no" value="{{ old('registration_no', $client->registration_no) }}" placeholder="e.g., 123456-X">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Tax ID / SST Number</label>
                                <input type="text" class="form-control" name="tax_id" value="{{ old('tax_id', $client->tax_id) }}" placeholder="e.g., A01-1234-56789012">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Billing Address -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Billing Address</h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-12">
                                <label class="form-label">Address</label>
                                <textarea class="form-control" name="billing_address" rows="2" placeholder="Street address">{{ old('billing_address', $client->billing_address) }}</textarea>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">City</label>
                                <input type="text" class="form-control" name="billing_city" value="{{ old('billing_city', $client->billing_city) }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">State</label>
                                <select class="form-select" name="billing_state">
                                    <option value="">-- Select State --</option>
                                    @foreach($states as $state)
                                    <option value="{{ $state }}" {{ old('billing_state', $client->billing_state) == $state ? 'selected' : '' }}>{{ $state }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Postcode</label>
                                <input type="text" class="form-control" name="billing_postcode" value="{{ old('billing_postcode', $client->billing_postcode) }}" maxlength="5" pattern="[0-9]{5}" placeholder="e.g., 50000">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Country</label>
                                <input type="text" class="form-control" name="billing_country" value="{{ old('billing_country', $client->billing_country ?? 'Malaysia') }}" readonly>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Primary Contact -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Primary Contact (PIC)</h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">Contact Name</label>
                                <input type="text" class="form-control" name="pic_name" value="{{ old('pic_name', $client->pic_name) }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Email</label>
                                <input type="email" class="form-control" name="pic_email" value="{{ old('pic_email', $client->pic_email) }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Phone</label>
                                <input type="text" class="form-control" name="pic_phone" value="{{ old('pic_phone', $client->pic_phone) }}" placeholder="e.g., 03-12345678">
                            </div>
                        </div>
                        <div class="alert alert-info mt-3 mb-0">
                            <i class="bi bi-info-circle me-1"></i> 
                            For multiple contacts, please add them in the "View Details" page after saving.
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="col-md-4">
                <!-- Payment Terms -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Payment Terms</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Payment Terms (Days) <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" name="payment_terms" value="{{ old('payment_terms', $client->payment_terms) }}" min="0" max="365" required>
                            <small class="text-muted">Number of days for payment</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Credit Limit (RM)</label>
                            <input type="number" class="form-control" name="credit_limit" value="{{ old('credit_limit', $client->credit_limit) }}" step="0.01" min="0" placeholder="0.00">
                            <small class="text-muted">Leave empty for no limit</small>
                        </div>
                    </div>
                </div>

                <!-- Notes -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Additional Notes</h5>
                    </div>
                    <div class="card-body">
                        <textarea class="form-control" name="notes" rows="4" placeholder="Any additional notes or comments">{{ old('notes', $client->notes) }}</textarea>
                    </div>
                </div>

                <!-- Submit Buttons -->
                <div class="card">
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-circle me-1"></i> Update Client
                            </button>
                            <a href="{{ route('admin.clients.show', $client) }}" class="btn btn-outline-secondary">
                                <i class="bi bi-x-circle me-1"></i> Cancel
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
    // Form submission
    $('#client_form').on('submit', function(e) {
        e.preventDefault();
        
        const form = $(this);
        const submitBtn = form.find('button[type="submit"]');
        const originalText = submitBtn.html();
        
        // Disable submit button
        submitBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Updating...');
        
        $.ajax({
            url: '{{ route("admin.clients.update", $client) }}',
            type: 'PUT',
            data: form.serialize(),
            success: function(response) {
                if (response.success) {
                    alert('Success: ' + response.message);
                    window.location.href = response.redirect;
                } else {
                    alert('Error: ' + response.message);
                    submitBtn.prop('disabled', false).html(originalText);
                }
            },
            error: function(xhr) {
                let message = 'Failed to update client';
                
                if (xhr.responseJSON && xhr.responseJSON.errors) {
                    const errors = xhr.responseJSON.errors;
                    message = Object.values(errors).flat().join('\n');
                } else if (xhr.responseJSON && xhr.responseJSON.message) {
                    message = xhr.responseJSON.message;
                }
                
                alert('Error: ' + message);
                submitBtn.prop('disabled', false).html(originalText);
            }
        });
    });

    // Postcode validation
    $('input[name="billing_postcode"]').on('input', function() {
        this.value = this.value.replace(/[^0-9]/g, '').slice(0, 5);
    });

    // Phone validation
    $('input[name="pic_phone"]').on('input', function() {
        this.value = this.value.replace(/[^0-9\-\+\s\(\)]/g, '');
    });
});
</script>
@endpush
