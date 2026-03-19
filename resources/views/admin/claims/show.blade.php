@extends('layouts.app')

@section('title', 'Claim Details - ' . $claim->claim_no)

@section('content')
<div class="container-fluid">
    {{-- Page Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1">Claim Details</h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.claims.index') }}">Claims</a></li>
                    <li class="breadcrumb-item active">{{ $claim->claim_no }}</li>
                </ol>
            </nav>
        </div>
        <a href="{{ $claim->claim_category === 'ticket' ? route('admin.claims.ticket-claims') : route('admin.claims.other-claims') }}"
           class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Back
        </a>
    </div>

    <div class="row g-4">
        {{-- Claim Information --}}
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-file-earmark-text me-2"></i>{{ $claim->claim_no }}
                    </h5>
                    {!! \App\Models\Claim::getStatusBadge($claim->status) !!}
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <p class="mb-1 text-muted small">Category</p>
                            <p class="fw-medium">{{ ucfirst($claim->claim_category) }} Claim</p>
                        </div>
                        @if($claim->claim_type_label)
                        <div class="col-md-6">
                            <p class="mb-1 text-muted small">Claim Type</p>
                            <p class="fw-medium">{{ $claim->claim_type_label }}</p>
                        </div>
                        @endif
                        <div class="col-md-6">
                            <p class="mb-1 text-muted small">Technician / Claimant</p>
                            <p class="fw-medium">{{ $claim->technician->name ?? '-' }}</p>
                        </div>
                        <div class="col-md-6">
                            <p class="mb-1 text-muted small">Submitted By</p>
                            <p class="fw-medium">{{ $claim->submitter->name ?? '-' }}</p>
                        </div>
                        @if($claim->ticket)
                        <div class="col-md-6">
                            <p class="mb-1 text-muted small">Ticket No</p>
                            <p class="fw-medium">
                                <a href="{{ route('admin.tickets.show', $claim->ticket_id) }}">{{ $claim->ticket->ticket_no }}</a>
                            </p>
                        </div>
                        <div class="col-md-6">
                            <p class="mb-1 text-muted small">Merchant</p>
                            <p class="fw-medium">{{ $claim->ticket->merchant_name ?? '-' }}</p>
                        </div>
                        <div class="col-md-6">
                            <p class="mb-1 text-muted small">Vendor</p>
                            <p class="fw-medium">{{ $claim->ticket->vendor->company_name ?? '-' }}</p>
                        </div>
                        <div class="col-md-6">
                            <p class="mb-1 text-muted small">Supervisor</p>
                            <p class="fw-medium">{{ $claim->ticket->supervisor->name ?? '-' }}</p>
                        </div>
                        @endif
                        <div class="col-12">
                            <p class="mb-1 text-muted small">Description</p>
                            <p class="fw-medium">{{ $claim->description ?? '-' }}</p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Attachments --}}
            @if($claim->attachments->isNotEmpty())
            <div class="card border-0 shadow-sm mt-3">
                <div class="card-header bg-white">
                    <h6 class="card-title mb-0"><i class="bi bi-paperclip me-2"></i>Attachments</h6>
                </div>
                <div class="card-body">
                    <div class="list-group list-group-flush">
                        @foreach($claim->attachments as $attachment)
                        <div class="list-group-item d-flex justify-content-between align-items-center px-0">
                            <div>
                                <i class="bi bi-file-earmark me-2"></i>
                                <span>{{ $attachment->file_name }}</span>
                                <small class="text-muted ms-2">({{ number_format(($attachment->file_size ?? 0) / 1024, 1) }} KB)</small>
                                @if($attachment->uploadedBy)
                                <small class="text-muted ms-1">by {{ $attachment->uploadedBy->name }}</small>
                                @endif
                            </div>
                            <a href="{{ asset('storage/' . $attachment->file_path) }}" class="btn btn-sm btn-outline-primary" target="_blank">
                                <i class="bi bi-download"></i>
                            </a>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
            @endif

            {{-- Admin Actions (Verify / Non-Claimable) --}}
            @if($claim->canBeVerified())
            <div class="card border-0 shadow-sm mt-3">
                <div class="card-header bg-white">
                    <h6 class="card-title mb-0"><i class="bi bi-shield-check me-2"></i>Admin Actions</h6>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label for="verify_amount" class="form-label">Claim Amount (RM)</label>
                            <input type="number" id="verify_amount" class="form-control" step="0.01" min="0"
                                   value="{{ $claim->total_amount }}">
                        </div>
                        <div class="col-md-8">
                            <label for="verify_remarks" class="form-label">Admin Remarks</label>
                            <textarea id="verify_remarks" class="form-control" rows="2" placeholder="Optional remarks..."></textarea>
                        </div>
                    </div>
                    <div class="d-flex gap-2 mt-3">
                        <button class="btn btn-success" id="btnVerify">
                            <i class="bi bi-check-circle me-1"></i> Verify Claim
                        </button>
                        <button class="btn btn-danger" id="btnNonClaimable">
                            <i class="bi bi-x-circle me-1"></i> Mark Non-Claimable
                        </button>
                    </div>
                </div>
            </div>
            @endif
        </div>

        {{-- Amounts & Timeline --}}
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">
                    <h6 class="card-title mb-0"><i class="bi bi-cash-stack me-2"></i>Amounts</h6>
                </div>
                <div class="card-body">
                    @if($claim->claim_category === 'ticket')
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Mileage ({{ number_format((float)$claim->total_mileage_km, 2) }} KM)</span>
                        <span>RM {{ number_format((float)$claim->total_mileage_amount, 2) }}</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Allowances</span>
                        <span>RM {{ number_format((float)$claim->total_allowance_amount, 2) }}</span>
                    </div>
                    <hr>
                    @endif
                    @if($claim->original_amount && $claim->original_amount != $claim->total_amount)
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Original Amount</span>
                        <span class="text-decoration-line-through">RM {{ number_format((float)$claim->original_amount, 2) }}</span>
                    </div>
                    @endif
                    <div class="d-flex justify-content-between">
                        <span class="fw-bold">Total Claim</span>
                        <span class="fw-bold text-primary fs-5">RM {{ number_format((float)$claim->total_amount, 2) }}</span>
                    </div>
                </div>
            </div>

            {{-- Remarks --}}
            @if($claim->remarks || $claim->admin_remarks)
            <div class="card border-0 shadow-sm mt-3">
                <div class="card-header bg-white">
                    <h6 class="card-title mb-0"><i class="bi bi-chat-text me-2"></i>Remarks</h6>
                </div>
                <div class="card-body">
                    @if($claim->remarks)
                    <p class="mb-2"><strong>Claim Remarks:</strong><br>{{ $claim->remarks }}</p>
                    @endif
                    @if($claim->admin_remarks)
                    <p class="mb-0"><strong>Admin Remarks:</strong><br>{{ $claim->admin_remarks }}</p>
                    @endif
                </div>
            </div>
            @endif

            {{-- Timeline --}}
            <div class="card border-0 shadow-sm mt-3">
                <div class="card-header bg-white">
                    <h6 class="card-title mb-0"><i class="bi bi-clock-history me-2"></i>Timeline</h6>
                </div>
                <div class="card-body">
                    <ul class="list-unstyled mb-0">
                        <li class="mb-2">
                            <small class="text-muted d-block">Submitted</small>
                            <span>{{ $claim->submitted_at ? $claim->submitted_at->format('d/m/Y H:i') : '-' }}</span>
                            <small class="text-muted d-block">by {{ $claim->submitter->name ?? '-' }}</small>
                        </li>
                        @if($claim->verified_at)
                        <li class="mb-2">
                            <small class="text-muted d-block">Verified</small>
                            <span>{{ $claim->verified_at->format('d/m/Y H:i') }}</span>
                            <small class="text-muted d-block">by {{ $claim->verifier->name ?? '-' }}</small>
                        </li>
                        @endif
                        @if($claim->paid_at)
                        <li class="mb-2">
                            <small class="text-muted d-block">Paid</small>
                            <span>{{ $claim->paid_at->format('d/m/Y H:i') }}</span>
                            <small class="text-muted d-block">by {{ $claim->payer->name ?? '-' }}</small>
                        </li>
                        @endif
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(function() {
    // Verify Claim
    $('#btnVerify').on('click', function() {
        const btn = $(this);
        const amount = $('#verify_amount').val();
        const remarks = $('#verify_remarks').val();

        Swal.fire({
            title: 'Verify this claim?',
            text: 'Amount: RM ' + parseFloat(amount).toFixed(2),
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#198754',
            confirmButtonText: 'Yes, Verify',
        }).then((result) => {
            if (result.isConfirmed) {
                btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Verifying...');
                $.ajax({
                    url: '{{ route("admin.claims.verify", $claim->id) }}',
                    method: 'POST',
                    data: { total_amount: amount, admin_remarks: remarks },
                    headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                    success: function(res) {
                        if (res.success) {
                            showToast('success', res.message);
                            setTimeout(() => location.reload(), 1500);
                        }
                    },
                    error: function(xhr) {
                        showToast('error', xhr.responseJSON?.message || 'Failed to verify claim.');
                        btn.prop('disabled', false).html('<i class="bi bi-check-circle me-1"></i> Verify Claim');
                    }
                });
            }
        });
    });

    // Mark Non-Claimable
    $('#btnNonClaimable').on('click', function() {
        const btn = $(this);
        const remarks = $('#verify_remarks').val();

        if (!remarks.trim()) {
            showToast('warning', 'Please provide admin remarks when marking as non-claimable.');
            $('#verify_remarks').focus();
            return;
        }

        Swal.fire({
            title: 'Mark as Non-Claimable?',
            text: 'This claim will be rejected.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            confirmButtonText: 'Yes, Mark Non-Claimable',
        }).then((result) => {
            if (result.isConfirmed) {
                btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Processing...');
                $.ajax({
                    url: '{{ route("admin.claims.non-claimable", $claim->id) }}',
                    method: 'POST',
                    data: { admin_remarks: remarks },
                    headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                    success: function(res) {
                        if (res.success) {
                            showToast('success', res.message);
                            setTimeout(() => location.reload(), 1500);
                        }
                    },
                    error: function(xhr) {
                        showToast('error', xhr.responseJSON?.message || 'Failed to mark as non-claimable.');
                        btn.prop('disabled', false).html('<i class="bi bi-x-circle me-1"></i> Mark Non-Claimable');
                    }
                });
            }
        });
    });
});
</script>
@endpush
