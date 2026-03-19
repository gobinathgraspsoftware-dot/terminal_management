@extends('layouts.app')

@section('title', 'Submit Other Claim')

@section('content')
<div class="container-fluid">
    {{-- Page Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1">Submit Other Claim</h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('technician.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('technician.claims.index') }}">Claims</a></li>
                    <li class="breadcrumb-item active">Submit Other Claim</li>
                </ol>
            </nav>
        </div>
        <a href="{{ route('technician.claims.other-claims') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Back to Other Claims
        </a>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white">
            <h5 class="card-title mb-0"><i class="bi bi-file-earmark-plus me-2"></i>Claim Information</h5>
        </div>
        <div class="card-body">
            <form id="claimForm" enctype="multipart/form-data">
                @csrf
                <div class="row g-3">
                    {{-- Claim Type --}}
                    <div class="col-md-6">
                        <label for="claim_type_label" class="form-label">Claim Type <span class="text-danger">*</span></label>
                        <select id="claim_type_label" name="claim_type_label" class="form-select" required>
                            <option value="">-- Select Claim Type --</option>
                            @foreach($claimTypes as $value => $label)
                                <option value="{{ $label }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        <div class="invalid-feedback"></div>
                    </div>

                    {{-- Linked Ticket (Optional) --}}
                    <div class="col-md-6">
                        <label for="ticket_id" class="form-label">Related Ticket (Optional)</label>
                        <select id="ticket_id" name="ticket_id" class="form-select select2-field">
                            <option value="">-- None --</option>
                            @foreach($tickets as $ticket)
                                <option value="{{ $ticket->id }}">{{ $ticket->ticket_no }} - {{ $ticket->merchant_name }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Description --}}
                    <div class="col-12">
                        <label for="description" class="form-label">Description <span class="text-danger">*</span></label>
                        <textarea id="description" name="description" class="form-control" rows="3" required
                                  placeholder="Describe the claim..."></textarea>
                        <div class="invalid-feedback"></div>
                    </div>

                    {{-- Claim Amount --}}
                    <div class="col-md-4">
                        <label for="claim_amount" class="form-label">Claim Amount (RM) <span class="text-danger">*</span></label>
                        <input type="number" id="claim_amount" name="claim_amount" class="form-control"
                               step="0.01" min="0.01" required placeholder="0.00">
                        <div class="invalid-feedback"></div>
                    </div>

                    {{-- Remarks --}}
                    <div class="col-md-8">
                        <label for="remarks" class="form-label">Remarks</label>
                        <textarea id="remarks" name="remarks" class="form-control" rows="2"
                                  placeholder="Additional remarks (optional)"></textarea>
                    </div>

                    {{-- Attachments --}}
                    <div class="col-12">
                        <label for="attachments" class="form-label">Attachments (Max 5 files, PDF/PNG/JPG, 5MB each)</label>
                        <input type="file" id="attachments" name="attachments[]" class="form-control"
                               multiple accept=".pdf,.png,.jpg,.jpeg">
                        <div class="form-text">Upload supporting documents such as receipts, invoices, or photos.</div>
                    </div>
                </div>

                <hr>

                <div class="d-flex justify-content-end gap-2">
                    <a href="{{ route('technician.claims.other-claims') }}" class="btn btn-outline-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary" id="submitBtn">
                        <i class="bi bi-send me-1"></i> Submit Claim
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(function() {
    // Initialize Select2
    $('#ticket_id').select2({ theme: 'bootstrap-5', placeholder: '-- None --', allowClear: true });

    // Form submission
    $('#claimForm').on('submit', function(e) {
        e.preventDefault();

        const btn = $('#submitBtn');
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Submitting...');

        // Reset validation
        $(this).find('.is-invalid').removeClass('is-invalid');

        const formData = new FormData(this);

        $.ajax({
            url: '{{ route("technician.claims.store") }}',
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            success: function(res) {
                if (res.success) {
                    showToast('success', res.message);
                    setTimeout(() => window.location.href = '{{ route("technician.claims.other-claims") }}', 1500);
                } else {
                    showToast('error', res.message || 'An error occurred.');
                    btn.prop('disabled', false).html('<i class="bi bi-send me-1"></i> Submit Claim');
                }
            },
            error: function(xhr) {
                if (xhr.status === 422) {
                    const errors = xhr.responseJSON.errors;
                    $.each(errors, function(field, messages) {
                        const input = $('[name="' + field + '"]');
                        input.addClass('is-invalid');
                        input.siblings('.invalid-feedback').text(messages[0]);
                    });
                } else {
                    showToast('error', xhr.responseJSON?.message || 'Server error. Please try again.');
                }
                btn.prop('disabled', false).html('<i class="bi bi-send me-1"></i> Submit Claim');
            }
        });
    });
});
</script>
@endpush
