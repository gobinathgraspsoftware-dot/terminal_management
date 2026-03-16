@extends('layouts.app')

@section('title', 'Create Ticket Claim')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1">Create Ticket Claim</h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('technician.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('technician.claims.index') }}">My Claims</a></li>
                    <li class="breadcrumb-item active">Create Ticket Claim</li>
                </ol>
            </nav>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">
                    <h6 class="mb-0"><i class="bi bi-ticket-detailed me-1"></i> New Ticket Claim</h6>
                </div>
                <div class="card-body">
                    <form id="ticket-claim-form">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label">Select Completed Ticket <span class="text-danger">*</span></label>
                            <select name="ticket_id" id="ticket-select" class="form-select select2-single" required>
                                <option value="">-- Select Ticket --</option>
                                @foreach($tickets as $ticket)
                                    <option value="{{ $ticket->id }}">
                                        {{ $ticket->ticket_no }} — {{ $ticket->merchant_name }}
                                        ({{ $ticket->vendor->company_name ?? 'N/A' }})
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Ticket Details (populated via AJAX) --}}
                        <div id="ticket-details" class="d-none">
                            <hr>
                            <h6 class="text-muted mb-3"><i class="bi bi-info-circle me-1"></i> Ticket Details</h6>
                            <div class="row g-2 mb-3">
                                <div class="col-md-4">
                                    <small class="text-muted">Ticket No</small>
                                    <div class="fw-semibold" id="td-ticket-no">-</div>
                                </div>
                                <div class="col-md-4">
                                    <small class="text-muted">Vendor</small>
                                    <div class="fw-semibold" id="td-vendor">-</div>
                                </div>
                                <div class="col-md-4">
                                    <small class="text-muted">Merchant</small>
                                    <div class="fw-semibold" id="td-merchant">-</div>
                                </div>
                                <div class="col-md-4">
                                    <small class="text-muted">Supervisor</small>
                                    <div class="fw-semibold" id="td-supervisor">-</div>
                                </div>
                                <div class="col-md-4">
                                    <small class="text-muted">Technician</small>
                                    <div class="fw-semibold" id="td-technician">-</div>
                                </div>
                                <div class="col-md-4">
                                    <small class="text-muted">Job Type</small>
                                    <div class="fw-semibold" id="td-jobtype">-</div>
                                </div>
                            </div>

                            <hr>
                            <h6 class="text-muted mb-3"><i class="bi bi-calculator me-1"></i> Claim Amounts</h6>
                            <input type="hidden" name="technician_id" id="input-technician-id">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Mileage (KM)</label>
                                    <input type="number" name="mileage" id="input-mileage" class="form-control" step="0.01" min="0" readonly>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Mileage Amount (RM)</label>
                                    <input type="number" name="mileage_amount" id="input-mileage-amount" class="form-control calc-field" step="0.01" min="0">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Toll (RM)</label>
                                    <input type="number" name="toll" id="input-toll" class="form-control calc-field" step="0.01" min="0">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Standby/Meal (RM)</label>
                                    <input type="number" name="standby_meal" id="input-meal" class="form-control calc-field" step="0.01" min="0">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold">Total Claim Amount (RM) <span class="text-danger">*</span></label>
                                    <input type="number" name="total_claim_amount" id="input-total" class="form-control fw-bold bg-light" step="0.01" min="0" required>
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Remarks</label>
                                    <textarea name="remarks" id="input-remarks" class="form-control" rows="2" placeholder="Optional remarks..."></textarea>
                                </div>
                            </div>

                            <hr>
                            <div class="d-flex justify-content-end gap-2">
                                <a href="{{ route('technician.claims.index', ['tab' => 'ticket']) }}" class="btn btn-secondary">Cancel</a>
                                <button type="submit" class="btn btn-primary" id="btn-submit">
                                    <i class="bi bi-check-circle me-1"></i> Create Ticket Claim
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">
                    <h6 class="mb-0"><i class="bi bi-question-circle me-1"></i> Help</h6>
                </div>
                <div class="card-body">
                    <ol class="mb-0 ps-3">
                        <li class="mb-2">Select a completed ticket from the dropdown.</li>
                        <li class="mb-2">Review the auto-populated claim amounts from the ticket.</li>
                        <li class="mb-2">Adjust amounts if needed (mileage amount, toll, meal).</li>
                        <li class="mb-2">Total is auto-calculated. Override if needed.</li>
                        <li class="mb-0">Only your tickets without an existing claim are shown.</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(function() {
    $('.select2-single').select2({ theme: 'bootstrap-5', width: '100%', placeholder: '-- Select Ticket --', allowClear: true });

    // Load ticket details on selection
    $('#ticket-select').on('change', function() {
        var ticketId = $(this).val();
        if (!ticketId) {
            $('#ticket-details').addClass('d-none');
            return;
        }

        $.ajax({
            url: '{{ url("technician/claims/ajax/ticket-details") }}/' + ticketId,
            method: 'GET',
            success: function(res) {
                if (res.success) {
                    $('#td-ticket-no').text(res.ticket_no);
                    $('#td-vendor').text(res.vendor);
                    $('#td-merchant').text(res.merchant_name);
                    $('#td-supervisor').text(res.supervisor);
                    $('#td-technician').text(res.technician);
                    $('#td-jobtype').text(res.job_type);
                    $('#input-mileage').val(res.mileage);
                    $('#input-mileage-amount').val(res.mileage_amount);
                    $('#input-toll').val(res.toll);
                    $('#input-meal').val(res.standby_meal);
                    $('#input-total').val(res.total_claim);
                    $('#input-remarks').val(res.mileage_remarks);
                    $('#input-technician-id').val(res.technician_id);
                    $('#ticket-details').removeClass('d-none');
                }
            },
            error: function() {
                showToast('Failed to load ticket details.', 'error');
            }
        });
    });

    // Recalculate total on amount change
    $('.calc-field').on('input', function() {
        var total = (parseFloat($('#input-mileage-amount').val()) || 0)
                  + (parseFloat($('#input-toll').val()) || 0)
                  + (parseFloat($('#input-meal').val()) || 0);
        $('#input-total').val(total.toFixed(2));
    });

    // Submit
    $('#ticket-claim-form').on('submit', function(e) {
        e.preventDefault();
        var $btn = $('#btn-submit');
        $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Creating...');

        $.ajax({
            url: '{{ route("technician.claims.store-ticket-claim") }}',
            method: 'POST',
            data: $(this).serialize(),
            success: function(res) {
                if (res.success) {
                    showToast(res.message, 'success');
                    setTimeout(function() { window.location.href = '{{ route("technician.claims.index", ["tab" => "ticket"]) }}'; }, 1500);
                } else {
                    showToast(res.message || 'Error occurred.', 'error');
                    $btn.prop('disabled', false).html('<i class="bi bi-check-circle me-1"></i> Create Ticket Claim');
                }
            },
            error: function(xhr) {
                var errors = xhr.responseJSON?.errors;
                if (errors) {
                    showToast(Object.values(errors).flat().join('<br>'), 'error');
                } else {
                    showToast(xhr.responseJSON?.message || 'Error occurred.', 'error');
                }
                $btn.prop('disabled', false).html('<i class="bi bi-check-circle me-1"></i> Create Ticket Claim');
            }
        });
    });
});
</script>
@endpush
