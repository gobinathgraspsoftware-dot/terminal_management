@extends('layouts.app')

@section('title', 'Submit Other Claim')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1"><i class="bi bi-plus-circle me-2"></i>Submit Other Claim</h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('technician.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('technician.claims.index', ['tab' => 'other']) }}">Other Claims</a></li>
                    <li class="breadcrumb-item active">Submit</li>
                </ol>
            </nav>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Claim Details</h5>
        </div>
        <div class="card-body">
            <form id="otherClaimForm" enctype="multipart/form-data">
                @csrf
                <div class="row g-3">
                    {{-- Claim Type --}}
                    <div class="col-md-6">
                        <label for="claim_type_label" class="form-label">Claim Type <span class="text-danger">*</span></label>
                        <select id="claim_type_label" name="claim_type_label" class="form-select" required>
                            <option value="">-- Select Type --</option>
                            @foreach($claimTypes as $key => $label)
                                <option value="{{ $label }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Linked Ticket (optional) --}}
                    <div class="col-md-6">
                        <label for="ticket_id" class="form-label">Linked Ticket (Optional)</label>
                        <select id="ticket_id" name="ticket_id" class="form-select select2">
                            <option value="">-- None --</option>
                            @foreach($tickets as $ticket)
                                <option value="{{ $ticket->id }}">{{ $ticket->ticket_no }} - {{ $ticket->merchant_name }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Claim Amount --}}
                    <div class="col-md-6">
                        <label for="claim_amount" class="form-label">Claim Amount (RM) <span class="text-danger">*</span></label>
                        <input type="number" id="claim_amount" name="claim_amount" class="form-control" step="0.01" min="0.01" required>
                    </div>

                    {{-- Description --}}
                    <div class="col-md-12">
                        <label for="description" class="form-label">Description <span class="text-danger">*</span></label>
                        <textarea id="description" name="description" class="form-control" rows="3" required maxlength="2000"></textarea>
                    </div>

                    {{-- Remarks --}}
                    <div class="col-md-12">
                        <label for="remarks" class="form-label">Remarks</label>
                        <textarea id="remarks" name="remarks" class="form-control" rows="2" maxlength="2000"></textarea>
                    </div>

                    {{-- Attachments --}}
                    <div class="col-md-12">
                        <label for="attachments" class="form-label">Attachments (Max 5 files, PDF/PNG/JPG, 5MB each)</label>
                        <input type="file" id="attachments" name="attachments[]" class="form-control" multiple accept=".pdf,.png,.jpg,.jpeg">
                    </div>
                </div>

                <hr>
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary" id="submitBtn">
                        <i class="bi bi-check-circle me-1"></i> Submit Claim
                    </button>
                    <a href="{{ route('technician.claims.index', ['tab' => 'other']) }}" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(function() {
    $('.select2').select2({ theme: 'bootstrap-5', width: '100%', allowClear: true, placeholder: '-- None --' });

    $('#otherClaimForm').on('submit', function(e) {
        e.preventDefault();
        let $btn = $('#submitBtn');
        $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Submitting...');

        let formData = new FormData(this);

        $.ajax({
            url: '{{ route("technician.claims.store") }}',
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
            success: function(res) {
                if (res.success) {
                    showToast(res.message, 'success');
                    setTimeout(() => window.location.href = '{{ route("technician.claims.index", ["tab" => "other"]) }}', 1500);
                } else {
                    showToast(res.message || 'Failed to submit claim.', 'error');
                    $btn.prop('disabled', false).html('<i class="bi bi-check-circle me-1"></i> Submit Claim');
                }
            },
            error: function(xhr) {
                let msg = xhr.responseJSON?.message || 'An error occurred.';
                if (xhr.responseJSON?.errors) {
                    msg = Object.values(xhr.responseJSON.errors).flat().join('<br>');
                }
                showToast(msg, 'error');
                $btn.prop('disabled', false).html('<i class="bi bi-check-circle me-1"></i> Submit Claim');
            }
        });
    });
});
</script>
@endpush
