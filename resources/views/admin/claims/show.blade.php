@php use App\Models\Claim; @endphp
@extends('layouts.app')

@section('title', 'Claim Detail - ' . $claim->claim_no)

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1">Claim Detail</h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.claims.index') }}">Claim Management</a></li>
                    @if($claim->claim_category === 'ticket')
                        <li class="breadcrumb-item"><a href="{{ route('admin.claims.ticket-claims') }}">Ticket Claims</a></li>
                    @else
                        <li class="breadcrumb-item"><a href="{{ route('admin.claims.other-claims') }}">Other Claims</a></li>
                    @endif
                    <li class="breadcrumb-item active">{{ $claim->claim_no }}</li>
                </ol>
            </nav>
        </div>
        <a href="{{ $claim->claim_category === 'ticket' ? route('admin.claims.ticket-claims') : route('admin.claims.other-claims') }}" class="btn btn-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i> Back
        </a>
    </div>

    <!-- Status Banner -->
    <div class="alert alert-light border-0 shadow-sm d-flex justify-content-between align-items-center mb-4">
        <div>
            <strong class="me-2">{{ $claim->claim_no }}</strong>
            @if($claim->claim_category === 'ticket')
                <span class="badge bg-info">Ticket Claim</span>
            @else
                <span class="badge bg-secondary">Other Claim</span>
            @endif
        </div>
        <div>{!! Claim::getStatusBadge($claim->status) !!}</div>
    </div>

    <div class="row g-4">
        <div class="col-lg-8">
            <!-- Claim Information -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h6 class="mb-0"><i class="bi bi-info-circle me-1"></i> Claim Information</h6>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <small class="text-muted d-block">Claim No</small>
                            <strong>{{ $claim->claim_no }}</strong>
                        </div>
                        <div class="col-md-4">
                            <small class="text-muted d-block">Category</small>
                            @if($claim->claim_category === 'ticket')
                                <span class="badge bg-info">Ticket Claim</span>
                            @else
                                <span class="badge bg-secondary">Other Claim</span>
                            @endif
                        </div>
                        <div class="col-md-4">
                            <small class="text-muted d-block">Submitted</small>
                            <strong>{{ $claim->submitted_at ? $claim->submitted_at->format('d/m/Y H:i') : '-' }}</strong>
                        </div>

                        @if($claim->ticket)
                        <div class="col-md-4">
                            <small class="text-muted d-block">Ticket No</small>
                            <strong>{{ $claim->ticket->ticket_no }}</strong>
                        </div>
                        <div class="col-md-4">
                            <small class="text-muted d-block">Vendor</small>
                            <strong>{{ $claim->ticket->vendor->company_name ?? '-' }}</strong>
                        </div>
                        <div class="col-md-4">
                            <small class="text-muted d-block">Merchant</small>
                            <strong>{{ $claim->ticket->merchant_name ?? '-' }}</strong>
                        </div>
                        <div class="col-md-4">
                            <small class="text-muted d-block">Supervisor</small>
                            <strong>{{ $claim->ticket->supervisor->name ?? '-' }}</strong>
                        </div>
                        <div class="col-md-4">
                            <small class="text-muted d-block">Job Type</small>
                            <strong>{{ $claim->ticket->jobType->name ?? '-' }}</strong>
                        </div>
                        @endif

                        @if($claim->claim_category === 'other')
                        <div class="col-md-4">
                            <small class="text-muted d-block">Claim Type</small>
                            <strong>{{ $claim->claim_type_label ?? 'Other' }}</strong>
                        </div>
                        @endif

                        <div class="col-md-4">
                            <small class="text-muted d-block">Technician</small>
                            <strong>{{ $claim->technician->name ?? '-' }}</strong>
                        </div>
                        <div class="col-md-4">
                            <small class="text-muted d-block">Submitted By</small>
                            <strong>{{ $claim->submitter->name ?? '-' }}</strong>
                        </div>

                        <div class="col-12">
                            <small class="text-muted d-block">Description</small>
                            <strong>{{ $claim->description ?? '-' }}</strong>
                        </div>

                        @if($claim->remarks)
                        <div class="col-12">
                            <small class="text-muted d-block">Remarks</small>
                            <strong>{{ $claim->remarks }}</strong>
                        </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Amounts -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h6 class="mb-0"><i class="bi bi-calculator me-1"></i> Claim Amounts</h6>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        @if($claim->claim_category === 'ticket')
                        <div class="col-md-4">
                            <small class="text-muted d-block">Mileage (KM)</small>
                            <strong>{{ number_format((float) $claim->total_mileage_km, 2) }}</strong>
                        </div>
                        <div class="col-md-4">
                            <small class="text-muted d-block">Mileage Amount (RM)</small>
                            <strong>{{ number_format((float) $claim->total_mileage_amount, 2) }}</strong>
                        </div>
                        <div class="col-md-4">
                            <small class="text-muted d-block">Allowance (RM)</small>
                            <strong>{{ number_format((float) $claim->total_allowance_amount, 2) }}</strong>
                        </div>
                        @endif
                        <div class="col-md-4">
                            <small class="text-muted d-block">Original Amount (RM)</small>
                            <strong>{{ number_format((float) $claim->original_amount, 2) }}</strong>
                        </div>
                        <div class="col-md-4">
                            <small class="text-muted d-block fw-bold text-primary">Total Amount (RM)</small>
                            <strong class="fs-5 text-primary">{{ number_format((float) $claim->total_amount, 2) }}</strong>
                        </div>
                        @if($claim->original_amount && $claim->original_amount != $claim->total_amount)
                        <div class="col-md-4">
                            <small class="text-muted d-block">Adjusted?</small>
                            <span class="badge bg-warning">Yes — Original: RM {{ number_format((float) $claim->original_amount, 2) }}</span>
                        </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Attachments -->
            @if($claim->attachments && $claim->attachments->isNotEmpty())
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h6 class="mb-0"><i class="bi bi-paperclip me-1"></i> Attachments ({{ $claim->attachments->count() }})</h6>
                </div>
                <div class="card-body">
                    <div class="row g-2">
                        @foreach($claim->attachments as $attachment)
                        <div class="col-md-6">
                            <div class="border rounded p-2 d-flex align-items-center">
                                @if(in_array(strtolower(pathinfo($attachment->file_name, PATHINFO_EXTENSION)), ['png','jpg','jpeg']))
                                    <img src="{{ asset('storage/' . $attachment->file_path) }}" alt="{{ $attachment->file_name }}"
                                         class="me-2 rounded" style="width:50px;height:50px;object-fit:cover;">
                                @else
                                    <i class="bi bi-file-earmark-pdf text-danger fs-3 me-2"></i>
                                @endif
                                <div class="flex-grow-1 text-truncate">
                                    <small class="d-block text-truncate">{{ $attachment->file_name }}</small>
                                    <small class="text-muted">{{ $attachment->uploadedBy->name ?? '-' }}</small>
                                </div>
                                <a href="{{ asset('storage/' . $attachment->file_path) }}" target="_blank" class="btn btn-sm btn-outline-primary ms-2">
                                    <i class="bi bi-eye"></i>
                                </a>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
            @endif

            <!-- Ticket Proofs (if ticket claim) -->
            @if($claim->claim_category === 'ticket' && $claim->ticket && $claim->ticket->proofs && $claim->ticket->proofs->isNotEmpty())
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h6 class="mb-0"><i class="bi bi-camera me-1"></i> Ticket Proofs ({{ $claim->ticket->proofs->count() }})</h6>
                </div>
                <div class="card-body">
                    <div class="row g-2">
                        @foreach($claim->ticket->proofs as $proof)
                        <div class="col-md-3">
                            <div class="border rounded p-1 text-center">
                                @if(in_array(strtolower(pathinfo($proof->file_name ?? $proof->file_path, PATHINFO_EXTENSION)), ['png','jpg','jpeg']))
                                    <img src="{{ asset('storage/' . $proof->file_path) }}" alt="Proof"
                                         class="img-fluid rounded" style="max-height:120px;object-fit:cover;">
                                @else
                                    <i class="bi bi-file-earmark text-muted" style="font-size:3rem;"></i>
                                @endif
                                <small class="d-block mt-1 text-muted text-truncate">{{ $proof->status ?? 'proof' }}</small>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
            @endif
        </div>

        <!-- Sidebar: Admin Actions -->
        <div class="col-lg-4">
            @if($claim->canBeVerified())
            <!-- Verification Panel -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h6 class="mb-0"><i class="bi bi-shield-check me-1"></i> Verification</h6>
                </div>
                <div class="card-body">
                    <form id="verify-form">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label">Adjusted Amount (RM)</label>
                            <input type="number" name="total_amount" id="verify-amount"
                                   class="form-control" step="0.01" min="0"
                                   value="{{ $claim->total_amount }}">
                            <small class="text-muted">Original: RM {{ number_format((float) $claim->original_amount, 2) }}</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Admin Remarks</label>
                            <textarea name="admin_remarks" id="input-remarks" class="form-control" rows="3"
                                      placeholder="Enter remarks (required for Non-Claimable)...">{{ $claim->admin_remarks }}</textarea>
                        </div>
                        <div class="d-grid gap-2">
                            <button type="button" id="btn-verify" class="btn btn-success">
                                <i class="bi bi-check-circle me-1"></i> Verified
                            </button>
                            <button type="button" id="btn-non-claimable" class="btn btn-danger">
                                <i class="bi bi-x-circle me-1"></i> Non-Claimable
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Quick Amount Edit -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">
                    <h6 class="mb-0"><i class="bi bi-pencil me-1"></i> Edit Amount Only</h6>
                </div>
                <div class="card-body">
                    <form id="update-amount-form">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label">New Amount (RM)</label>
                            <input type="number" name="total_amount" id="edit-amount"
                                   class="form-control" step="0.01" min="0"
                                   value="{{ $claim->total_amount }}">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Remarks</label>
                            <textarea name="admin_remarks" id="edit-remarks" class="form-control" rows="2"
                                      placeholder="Reason for adjustment...">{{ $claim->admin_remarks }}</textarea>
                        </div>
                        <button type="button" id="btn-save-amount" class="btn btn-primary w-100">
                            <i class="bi bi-save me-1"></i> Save Amount
                        </button>
                    </form>
                </div>
            </div>
            @else
            <!-- Read-only status card -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h6 class="mb-0"><i class="bi bi-info-circle me-1"></i> Claim Status</h6>
                </div>
                <div class="card-body text-center">
                    <div class="mb-3">{!! Claim::getStatusBadge($claim->status) !!}</div>

                    @if($claim->verified_at)
                    <div class="text-start mb-2">
                        <small class="text-muted d-block">Verified By</small>
                        <strong>{{ $claim->verifier->name ?? '-' }}</strong>
                    </div>
                    <div class="text-start mb-2">
                        <small class="text-muted d-block">Verified At</small>
                        <strong>{{ \Carbon\Carbon::parse($claim->verified_at)->format('d/m/Y H:i') }}</strong>
                    </div>
                    @endif

                    @if($claim->admin_remarks)
                    <div class="text-start mb-2">
                        <small class="text-muted d-block">Admin Remarks</small>
                        <strong>{{ $claim->admin_remarks }}</strong>
                    </div>
                    @endif

                    @if($claim->paid_at)
                    <hr>
                    <div class="text-start mb-2">
                        <small class="text-muted d-block">Paid By</small>
                        <strong>{{ $claim->payer->name ?? '-' }}</strong>
                    </div>
                    <div class="text-start">
                        <small class="text-muted d-block">Paid At</small>
                        <strong>{{ \Carbon\Carbon::parse($claim->paid_at)->format('d/m/Y H:i') }}</strong>
                    </div>
                    @endif
                </div>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(function() {
    // Verify
    $('#btn-verify').on('click', function() {
        Swal.fire({
            title: 'Verify this claim?',
            text: 'Amount: RM ' + parseFloat($('#verify-amount').val()).toFixed(2),
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#198754',
            confirmButtonText: 'Yes, Verify'
        }).then(function(result) {
            if (result.isConfirmed) {
                $.ajax({
                    url: '{{ route("admin.claims.verify", $claim->id) }}',
                    method: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}',
                        total_amount: $('#verify-amount').val(),
                        admin_remarks: $('#input-remarks').val()
                    },
                    success: function(res) {
                        if (res.success) {
                            showToast(res.message, 'success');
                            setTimeout(function() { location.reload(); }, 1500);
                        } else {
                            showToast(res.message || 'Error occurred.', 'error');
                        }
                    },
                    error: function(xhr) {
                        showToast(xhr.responseJSON?.message || 'Error occurred.', 'error');
                    }
                });
            }
        });
    });

    // Non-claimable
    $('#btn-non-claimable').on('click', function() {
        var remarks = $('#input-remarks').val();
        if (!remarks || remarks.trim() === '') {
            showToast('Remarks are required when marking as Non-Claimable.', 'warning');
            $('#input-remarks').focus();
            return;
        }

        Swal.fire({
            title: 'Mark as Non-Claimable?',
            text: 'This claim will be rejected and marked as Non-Claimable.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            confirmButtonText: 'Yes, Non-Claimable'
        }).then(function(result) {
            if (result.isConfirmed) {
                $.ajax({
                    url: '{{ route("admin.claims.non-claimable", $claim->id) }}',
                    method: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}',
                        admin_remarks: remarks
                    },
                    success: function(res) {
                        if (res.success) {
                            showToast(res.message, 'success');
                            setTimeout(function() { location.reload(); }, 1500);
                        } else {
                            showToast(res.message || 'Error occurred.', 'error');
                        }
                    },
                    error: function(xhr) {
                        showToast(xhr.responseJSON?.message || 'Error occurred.', 'error');
                    }
                });
            }
        });
    });

    // Save amount only
    $('#btn-save-amount').on('click', function() {
        $.ajax({
            url: '{{ route("admin.claims.update-amount", $claim->id) }}',
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                total_amount: $('#edit-amount').val(),
                admin_remarks: $('#edit-remarks').val()
            },
            success: function(res) {
                if (res.success) {
                    showToast(res.message, 'success');
                    setTimeout(function() { location.reload(); }, 1500);
                } else {
                    showToast(res.message || 'Error occurred.', 'error');
                }
            },
            error: function(xhr) {
                showToast(xhr.responseJSON?.message || 'Error occurred.', 'error');
            }
        });
    });
});
</script>
@endpush
