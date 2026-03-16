@extends('layouts.app')

@section('title', 'Create Ticket Claim')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1"><i class="bi bi-ticket-detailed me-2"></i>Create Ticket Claim</h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('technician.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('technician.claims.index', ['tab' => 'ticket']) }}">Ticket Claims</a></li>
                    <li class="breadcrumb-item active">Create</li>
                </ol>
            </nav>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Select Completed Ticket</h5>
        </div>
        <div class="card-body">
            <form id="ticketClaimForm">
                @csrf
                <div class="row g-3">
                    {{-- Ticket Select --}}
                    <div class="col-md-12">
                        <label for="ticket_id" class="form-label">Ticket <span class="text-danger">*</span></label>
                        <select id="ticket_id" name="ticket_id" class="form-select select2" required>
                            <option value="">-- Select Ticket --</option>
                            @foreach($tickets as $ticket)
                                <option value="{{ $ticket->id }}">
                                    {{ $ticket->ticket_no }} — {{ $ticket->vendor->company_name ?? '' }} — {{ $ticket->merchant_name ?? '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                {{-- Ticket Details (populated via AJAX) --}}
                <div id="ticketDetailsPanel" class="mt-4" style="display:none;">
                    <div class="card bg-light">
                        <div class="card-body">
                            <h6 class="card-title"><i class="bi bi-info-circle me-1"></i> Ticket Details</h6>
                            <div class="row g-2">
                                <div class="col-md-3"><strong>Ticket No:</strong> <span id="det_ticket_no">-</span></div>
                                <div class="col-md-3"><strong>Vendor:</strong> <span id="det_vendor">-</span></div>
                                <div class="col-md-3"><strong>Merchant:</strong> <span id="det_merchant">-</span></div>
                                <div class="col-md-3"><strong>Job Type:</strong> <span id="det_job_type">-</span></div>
                            </div>
                            <hr>
                            <div class="row g-3">
                                <div class="col-md-3">
                                    <label class="form-label">Mileage (KM)</label>
                                    <input type="number" name="mileage" id="mileage" class="form-control" step="0.01" min="0">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Mileage Amount (RM)</label>
                                    <input type="number" name="mileage_amount" id="mileage_amount" class="form-control" step="0.01" min="0">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Toll (RM)</label>
                                    <input type="number" name="toll" id="toll" class="form-control" step="0.01" min="0">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Standby/Meal (RM)</label>
                                    <input type="number" name="standby_meal" id="standby_meal" class="form-control" step="0.01" min="0">
                                </div>
                            </div>
                            <div class="row g-3 mt-1">
                                <div class="col-md-4">
                                    <label class="form-label fw-bold">Total Claim Amount (RM) <span class="text-danger">*</span></label>
                                    <input type="number" name="total_claim_amount" id="total_claim_amount" class="form-control form-control-lg fw-bold text-success" step="0.01" min="0" required readonly>
                                </div>
                                <div class="col-md-8">
                                    <label class="form-label">Remarks</label>
                                    <textarea name="remarks" id="remarks" class="form-control" rows="2" maxlength="2000"></textarea>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-3 d-flex gap-2">
                        <button type="submit" class="btn btn-primary" id="submitBtn">
                            <i class="bi bi-check-circle me-1"></i> Submit Ticket Claim
                        </button>
                        <a href="{{ route('technician.claims.index', ['tab' => 'ticket']) }}" class="btn btn-secondary">Cancel</a>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(function() {
    $('.select2').select2({ theme: 'bootstrap-5', width: '100%', placeholder: '-- Select Ticket --' });

    // Fetch ticket details on select
    $('#ticket_id').on('change', function() {
        let ticketId = $(this).val();
        if (!ticketId) { $('#ticketDetailsPanel').hide(); return; }

        $.get('{{ url("technician/claims/ajax/ticket-details") }}/' + ticketId, function(res) {
            if (res.success) {
                $('#det_ticket_no').text(res.ticket_no);
                $('#det_vendor').text(res.vendor);
                $('#det_merchant').text(res.merchant_name);
                $('#det_job_type').text(res.job_type);
                $('#mileage').val(res.mileage);
                $('#mileage_amount').val(res.mileage_amount);
                $('#toll').val(res.toll);
                $('#standby_meal').val(res.standby_meal);
                $('#total_claim_amount').val(res.total_claim);
                $('#remarks').val(res.mileage_remarks);
                $('#ticketDetailsPanel').slideDown();
                recalcTotal();
            }
        });
    });

    // Recalculate total
    function recalcTotal() {
        let m = parseFloat($('#mileage_amount').val()) || 0;
        let t = parseFloat($('#toll').val()) || 0;
        let s = parseFloat($('#standby_meal').val()) || 0;
        $('#total_claim_amount').val((m + t + s).toFixed(2));
    }
    $('#mileage_amount, #toll, #standby_meal').on('input', recalcTotal);

    // Submit
    $('#ticketClaimForm').on('submit', function(e) {
        e.preventDefault();
        let $btn = $('#submitBtn');
        $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Submitting...');

        $.ajax({
            url: '{{ route("technician.claims.store-ticket-claim") }}',
            method: 'POST',
            data: $(this).serialize(),
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
            success: function(res) {
                if (res.success) {
                    showToast(res.message, 'success');
                    setTimeout(() => window.location.href = '{{ route("technician.claims.index", ["tab" => "ticket"]) }}', 1500);
                } else {
                    showToast(res.message || 'Failed.', 'error');
                    $btn.prop('disabled', false).html('<i class="bi bi-check-circle me-1"></i> Submit Ticket Claim');
                }
            },
            error: function(xhr) {
                let msg = xhr.responseJSON?.message || 'An error occurred.';
                if (xhr.responseJSON?.errors) msg = Object.values(xhr.responseJSON.errors).flat().join('<br>');
                showToast(msg, 'error');
                $btn.prop('disabled', false).html('<i class="bi bi-check-circle me-1"></i> Submit Ticket Claim');
            }
        });
    });
});
</script>
@endpush
