@php use App\Models\Claim; @endphp
@extends('layouts.app')

@section('title', 'Claim Detail - ' . $claim->claim_no)

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1">Claim Detail: {{ $claim->claim_no }}</h4>
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
        <div>
            {!! Claim::getStatusBadge($claim->status) !!}
        </div>
    </div>

    <div class="row g-4">
        <!-- Left: Claim Details -->
        <div class="col-lg-8">
            <!-- Claim Info Card -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h6 class="mb-0">
                        <i class="bi bi-info-circle me-1"></i>
                        {{ $claim->claim_category === 'ticket' ? 'Ticket Claim Details' : 'Other Claim Details' }}
                    </h6>
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
                            <small class="text-muted d-block">Submitted Date</small>
                            <strong>{{ $claim->submitted_at ? $claim->submitted_at->format('d/m/Y H:i') : '-' }}</strong>
                        </div>

                        @if($claim->claim_category === 'ticket' && $claim->ticket)
                        <div class="col-md-4">
                            <small class="text-muted d-block">Ticket No</small>
                            <a href="{{ route('admin.tickets.show', $claim->ticket_id) }}">{{ $claim->ticket->ticket_no }}</a>
                        </div>
                        <div class="col-md-4">
                            <small class="text-muted d-block">Vendor</small>
                            <strong>{{ $claim->ticket->vendor->company_name ?? '-' }}</strong>
                        </div>
                        <div class="col-md-4">
                            <small class="text-muted d-block">Merchant Name</small>
                            <strong>{{ $claim->ticket->merchant_name ?? '-' }}</strong>
                        </div>
                        <div class="col-md-4">
                            <small class="text-muted d-block">Job Type</small>
                            <strong>{{ $claim->ticket->jobType->name ?? '-' }}</strong>
                        </div>
                        @endif

                        @if($claim->claim_category === 'other')
                        <div class="col-md-4">
                            <small class="text-muted d-block">Claim Type</small>
                            <strong>{{ $claim->claim_type_label ?? 'Others' }}</strong>
                        </div>
                        <div class="col-md-8">
                            <small class="text-muted d-block">Description</small>
                            <strong>{{ $claim->description ?? '-' }}</strong>
                        </div>
                        @if($claim->ticket)
                        <div class="col-md-4">
                            <small class="text-muted d-block">Linked Ticket</small>
                            <a href="{{ route('admin.tickets.show', $claim->ticket_id) }}">{{ $claim->ticket->ticket_no }}</a>
                        </div>
                        @endif
                        @endif

                        <div class="col-md-4">
                            <small class="text-muted d-block">Supervisor</small>
                            <strong>{{ $claim->ticket->supervisor->name ?? '-' }}</strong>
                        </div>
                        <div class="col-md-4">
                            <small class="text-muted d-block">Technician</small>
                            <strong>{{ $claim->technician->name ?? '-' }}</strong>
                        </div>
                        <div class="col-md-4">
                            <small class="text-muted d-block">Submitted By</small>
                            <strong>{{ $claim->submitter->name ?? '-' }}</strong>
                        </div>
                    </div>

                    @if($claim->claim_category === 'ticket' && $claim->ticket)
                    <hr>
                    <h6 class="mb-3">Ticket Claim Breakdown</h6>
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered mb-0">
                            <tbody>
                                <tr>
                                    <td class="bg-light fw-semibold" style="width:40%;">Mileage (KM)</td>
                                    <td>{{ $claim->total_mileage_km ?? '0.00' }}</td>
                                </tr>
                                <tr>
                                    <td class="bg-light fw-semibold">Mileage Amount (RM)</td>
                                    <td>{{ number_format((float)($claim->total_mileage_amount ?? 0), 2) }}</td>
                                </tr>
                                <tr>
                                    <td class="bg-light fw-semibold">Allowance (Toll + Meal) (RM)</td>
                                    <td>{{ number_format((float)($claim->total_allowance_amount ?? 0), 2) }}</td>
                                </tr>
                                <tr class="table-primary">
                                    <td class="fw-bold">Total Claim Amount (RM)</td>
                                    <td class="fw-bold">{{ number_format((float)$claim->total_amount, 2) }}</td>
                                </tr>
                                @if($claim->original_amount && $claim->original_amount != $claim->total_amount)
                                <tr class="table-warning">
                                    <td class="fw-semibold">Original Amount (RM)</td>
                                    <td><s>{{ number_format((float)$claim->original_amount, 2) }}</s></td>
                                </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                    @endif

                    @if($claim->remarks)
                    <hr>
                    <small class="text-muted d-block">Claimant Remarks</small>
                    <p class="mb-0">{{ $claim->remarks }}</p>
                    @endif

                    @if($claim->admin_remarks)
                    <hr>
                    <small class="text-muted d-block">Admin Remarks</small>
                    <p class="mb-0 text-danger">{{ $claim->admin_remarks }}</p>
                    @endif
                </div>
            </div>

            <!-- Attachments Card (for Other Claims) -->
            @if($claim->attachments->isNotEmpty())
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h6 class="mb-0"><i class="bi bi-paperclip me-1"></i> Attachments</h6>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        @foreach($claim->attachments as $attachment)
                        <div class="col-md-4">
                            <div class="border rounded p-2 text-center">
                                @if(in_array($attachment->mime_type, ['image/png', 'image/jpeg', 'image/jpg']))
                                    <a href="{{ asset('storage/' . $attachment->file_path) }}" target="_blank">
                                        <img src="{{ asset('storage/' . $attachment->file_path) }}"
                                             class="img-fluid rounded mb-2" style="max-height: 150px;" alt="Proof">
                                    </a>
                                @else
                                    <a href="{{ asset('storage/' . $attachment->file_path) }}" target="_blank" class="btn btn-outline-primary btn-sm">
                                        <i class="bi bi-file-earmark-pdf me-1"></i> {{ $attachment->file_name }}
                                    </a>
                                @endif
                                <div class="small text-muted mt-1">
                                    {{ $attachment->uploadedBy->name ?? 'Unknown' }} &bull;
                                    {{ $attachment->created_at->format('d/m/Y') }}
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
            @endif

            <!-- Ticket Proofs (for Ticket Claims) -->
            @if($claim->claim_category === 'ticket' && $claim->ticket && $claim->ticket->proofs->isNotEmpty())
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h6 class="mb-0"><i class="bi bi-camera me-1"></i> Ticket Proofs</h6>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        @foreach($claim->ticket->proofs as $proof)
                        <div class="col-md-4">
                            <div class="border rounded p-2 text-center">
                                <a href="{{ asset('storage/' . $proof->file_path) }}" target="_blank">
                                    <img src="{{ asset('storage/' . $proof->file_path) }}"
                                         class="img-fluid rounded mb-2" style="max-height: 150px;" alt="Proof">
                                </a>
                                <div class="small text-muted">{{ $proof->proof_type ?? 'Proof' }}</div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
            @endif

            <!-- Status History -->
            @if($claim->verified_at || $claim->paid_at)
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">
                    <h6 class="mb-0"><i class="bi bi-clock-history me-1"></i> Status Timeline</h6>
                </div>
                <div class="card-body">
                    <ul class="list-unstyled mb-0">
                        <li class="d-flex align-items-start mb-2">
                            <span class="badge bg-info me-2 mt-1">Submitted</span>
                            <span>{{ $claim->submitted_at ? $claim->submitted_at->format('d/m/Y H:i') : '-' }}
                                by {{ $claim->submitter->name ?? '-' }}</span>
                        </li>
                        @if($claim->verified_at)
                        <li class="d-flex align-items-start mb-2">
                            <span class="badge {{ $claim->status === 'non_claimable' ? 'bg-danger' : 'bg-success' }} me-2 mt-1">
                                {{ $claim->status === 'non_claimable' ? 'Non-Claimable' : 'Verified' }}
                            </span>
                            <span>{{ $claim->verified_at->format('d/m/Y H:i') }}
                                by {{ $claim->verifier->name ?? '-' }}</span>
                        </li>
                        @endif
                        @if($claim->paid_at)
                        <li class="d-flex align-items-start">
                            <span class="badge bg-primary me-2 mt-1">Paid</span>
                            <span>{{ $claim->paid_at->format('d/m/Y H:i') }}
                                by {{ $claim->payer->name ?? '-' }}</span>
                        </li>
                        @endif
                    </ul>
                </div>
            </div>
            @endif
        </div>

        <!-- Right: Admin Actions -->
        <div class="col-lg-4">
            @if($claim->status === Claim::STATUS_SUBMITTED)
            <!-- Verification Panel -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h6 class="mb-0"><i class="bi bi-shield-check me-1"></i> Claim Verification</h6>
                </div>
                <div class="card-body">
                    <form id="verify-form">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label">Claim Amount (RM)</label>
                            <input type="number" name="total_amount" id="input-amount"
                                   class="form-control" step="0.01" min="0"
                                   value="{{ $claim->total_amount }}">
                            @if($claim->original_amount && $claim->original_amount != $claim->total_amount)
                                <small class="text-muted">Original: RM {{ number_format((float)$claim->original_amount, 2) }}</small>
                            @endif
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
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">
                    <h6 class="mb-0"><i class="bi bi-info-circle me-1"></i> Claim Status</h6>
                </div>
                <div class="card-body text-center">
                    <div class="mb-3">{!! Claim::getStatusBadge($claim->status) !!}</div>
                    <p class="text-muted small mb-0">
                        @if($claim->status === Claim::STATUS_VERIFIED)
                            This claim has been verified and is ready for payment processing.
                        @elseif($claim->status === Claim::STATUS_NON_CLAIMABLE)
                            This claim has been marked as non-claimable.
                        @elseif($claim->status === Claim::STATUS_PENDING_PAYMENT)
                            This claim is pending payment processing.
                        @elseif($claim->status === Claim::STATUS_PAID)
                            This claim has been paid on {{ $claim->paid_at ? $claim->paid_at->format('d/m/Y') : '-' }}.
                        @endif
                    </p>
                    @if($claim->total_amount)
                    <hr>
                    <div class="fs-4 fw-bold text-primary">RM {{ number_format((float)$claim->total_amount, 2) }}</div>
                    <small class="text-muted">Claim Amount</small>
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
    // Verify claim
    $('#btn-verify').on('click', function() {
        var amount  = $('#input-amount').val();
        var remarks = $('#input-remarks').val();

        Swal.fire({
            title: 'Verify Claim?',
            text: 'Mark this claim as Verified with amount RM ' + parseFloat(amount).toFixed(2) + '?',
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
                        total_amount: amount,
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
