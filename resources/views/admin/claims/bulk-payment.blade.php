@extends('layouts.app')

@section('title', 'Bulk Payment')

@section('content')
<div class="container-fluid">
    {{-- Page Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1"><i class="bi bi-cash-stack me-2"></i>Bulk Payment</h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.claims.index') }}">Claims</a></li>
                    <li class="breadcrumb-item active">Bulk Payment</li>
                </ol>
            </nav>
        </div>
    </div>

    {{-- Category Tabs --}}
    <ul class="nav nav-tabs mb-3">
        <li class="nav-item">
            <a class="nav-link {{ $category === 'all' ? 'active' : '' }}" href="{{ route('admin.claims.bulk-payment', ['category' => 'all']) }}">All</a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ $category === 'ticket' ? 'active' : '' }}" href="{{ route('admin.claims.bulk-payment', ['category' => 'ticket']) }}">Ticket Claims</a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ $category === 'other' ? 'active' : '' }}" href="{{ route('admin.claims.bulk-payment', ['category' => 'other']) }}">Other Claims</a>
        </li>
    </ul>

    {{-- Verified Claims — Process to Pending Payment --}}
    @php
        $verifiedItems = $verifiedClaims->where('status', \App\Models\Claim::STATUS_VERIFIED);
        $pendingItems  = $verifiedClaims->where('status', \App\Models\Claim::STATUS_PENDING_PAYMENT);
    @endphp

    {{-- SECTION 1: Verified → Pending Payment --}}
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center bg-success bg-opacity-10">
            <h5 class="mb-0"><i class="bi bi-check-circle me-2 text-success"></i>Verified Claims (Ready to Process)</h5>
            <span class="badge bg-success">{{ $verifiedItems->count() }} claims</span>
        </div>
        <div class="card-body">
            @if($verifiedItems->isEmpty())
                <div class="alert alert-info mb-0">No verified claims pending processing.</div>
            @else
                <div class="mb-3">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="selectAllVerified">
                        <label class="form-check-label fw-bold" for="selectAllVerified">Select All</label>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-striped table-hover table-sm">
                        <thead>
                            <tr>
                                <th width="40"><input type="checkbox" class="d-none"></th>
                                <th>Claim No</th>
                                <th>Category</th>
                                <th>Technician</th>
                                <th>Ticket</th>
                                <th>Description</th>
                                <th class="text-end">Amount (RM)</th>
                                <th>Submitted</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($verifiedItems as $claim)
                            <tr>
                                <td>
                                    <input type="checkbox" class="form-check-input verified-checkbox" value="{{ $claim->id }}" data-amount="{{ (float)$claim->total_amount }}">
                                </td>
                                <td><a href="{{ route('admin.claims.show', $claim->id) }}">{{ $claim->claim_no }}</a></td>
                                <td>
                                    @if($claim->claim_category === 'ticket')
                                        <span class="badge bg-primary">Ticket</span>
                                    @else
                                        <span class="badge bg-info">Other</span>
                                    @endif
                                </td>
                                <td>{{ $claim->technician->name ?? '-' }}</td>
                                <td>{{ $claim->ticket->ticket_no ?? '-' }}</td>
                                <td>{{ \Illuminate\Support\Str::limit($claim->description, 40) }}</td>
                                <td class="text-end fw-bold">{{ number_format((float)$claim->total_amount, 2) }}</td>
                                <td>{{ $claim->submitted_at ? $claim->submitted_at->format('d/m/Y') : '-' }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="d-flex justify-content-between align-items-center mt-3">
                    <div>
                        Selected: <strong id="verifiedCount">0</strong> claims |
                        Total: <strong>RM <span id="verifiedTotal">0.00</span></strong>
                    </div>
                    <button type="button" class="btn btn-success" id="processBulkPaymentBtn" disabled>
                        <i class="bi bi-arrow-right-circle me-1"></i> Process to Pending Payment
                    </button>
                </div>
            @endif
        </div>
    </div>

    {{-- SECTION 2: Pending Payment → Mark Paid --}}
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center bg-warning bg-opacity-10">
            <h5 class="mb-0"><i class="bi bi-hourglass-split me-2 text-warning"></i>Pending Payment Claims (Ready to Mark Paid)</h5>
            <span class="badge bg-warning text-dark">{{ $pendingItems->count() }} claims</span>
        </div>
        <div class="card-body">
            @if($pendingItems->isEmpty())
                <div class="alert alert-info mb-0">No pending payment claims.</div>
            @else
                <div class="mb-3">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="selectAllPending">
                        <label class="form-check-label fw-bold" for="selectAllPending">Select All</label>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-striped table-hover table-sm">
                        <thead>
                            <tr>
                                <th width="40"><input type="checkbox" class="d-none"></th>
                                <th>Claim No</th>
                                <th>Category</th>
                                <th>Technician</th>
                                <th>Ticket</th>
                                <th>Description</th>
                                <th class="text-end">Amount (RM)</th>
                                <th>Submitted</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($pendingItems as $claim)
                            <tr>
                                <td>
                                    <input type="checkbox" class="form-check-input pending-checkbox" value="{{ $claim->id }}" data-amount="{{ (float)$claim->total_amount }}">
                                </td>
                                <td><a href="{{ route('admin.claims.show', $claim->id) }}">{{ $claim->claim_no }}</a></td>
                                <td>
                                    @if($claim->claim_category === 'ticket')
                                        <span class="badge bg-primary">Ticket</span>
                                    @else
                                        <span class="badge bg-info">Other</span>
                                    @endif
                                </td>
                                <td>{{ $claim->technician->name ?? '-' }}</td>
                                <td>{{ $claim->ticket->ticket_no ?? '-' }}</td>
                                <td>{{ \Illuminate\Support\Str::limit($claim->description, 40) }}</td>
                                <td class="text-end fw-bold">{{ number_format((float)$claim->total_amount, 2) }}</td>
                                <td>{{ $claim->submitted_at ? $claim->submitted_at->format('d/m/Y') : '-' }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="d-flex justify-content-between align-items-center mt-3">
                    <div>
                        Selected: <strong id="pendingCount">0</strong> claims |
                        Total: <strong>RM <span id="pendingTotal">0.00</span></strong>
                    </div>
                    <button type="button" class="btn btn-primary" id="markPaidBtn" disabled>
                        <i class="bi bi-check2-all me-1"></i> Mark as Paid
                    </button>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(function() {
    // ── Verified section checkbox logic ──
    function updateVerifiedSummary() {
        let count = 0, total = 0;
        $('.verified-checkbox:checked').each(function() {
            count++;
            total += parseFloat($(this).data('amount')) || 0;
        });
        $('#verifiedCount').text(count);
        $('#verifiedTotal').text(total.toFixed(2));
        $('#processBulkPaymentBtn').prop('disabled', count === 0);
    }

    $('#selectAllVerified').on('change', function() {
        $('.verified-checkbox').prop('checked', $(this).is(':checked'));
        updateVerifiedSummary();
    });
    $(document).on('change', '.verified-checkbox', updateVerifiedSummary);

    // ── Pending section checkbox logic ──
    function updatePendingSummary() {
        let count = 0, total = 0;
        $('.pending-checkbox:checked').each(function() {
            count++;
            total += parseFloat($(this).data('amount')) || 0;
        });
        $('#pendingCount').text(count);
        $('#pendingTotal').text(total.toFixed(2));
        $('#markPaidBtn').prop('disabled', count === 0);
    }

    $('#selectAllPending').on('change', function() {
        $('.pending-checkbox').prop('checked', $(this).is(':checked'));
        updatePendingSummary();
    });
    $(document).on('change', '.pending-checkbox', updatePendingSummary);

    // ── Process Bulk Payment (verified → pending_payment) ──
    $('#processBulkPaymentBtn').on('click', function() {
        let ids = [];
        $('.verified-checkbox:checked').each(function() { ids.push($(this).val()); });
        if (!ids.length) return;

        Swal.fire({
            title: 'Process Bulk Payment?',
            html: `<strong>${ids.length}</strong> claim(s) will be moved to <span class="badge bg-warning text-dark">Pending Payment</span> status.`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Yes, Process',
            confirmButtonColor: '#198754',
        }).then((result) => {
            if (!result.isConfirmed) return;

            let $btn = $(this);
            $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Processing...');

            $.ajax({
                url: '{{ route("admin.claims.process-bulk-payment") }}',
                method: 'POST',
                data: { claim_ids: ids, _token: '{{ csrf_token() }}' },
                success: function(res) {
                    if (res.success) {
                        showToast(res.message, 'success');
                        setTimeout(() => location.reload(), 1500);
                    } else {
                        showToast(res.message, 'error');
                        $btn.prop('disabled', false).html('<i class="bi bi-arrow-right-circle me-1"></i> Process to Pending Payment');
                    }
                },
                error: function(xhr) {
                    showToast(xhr.responseJSON?.message || 'An error occurred.', 'error');
                    $btn.prop('disabled', false).html('<i class="bi bi-arrow-right-circle me-1"></i> Process to Pending Payment');
                }
            });
        });
    });

    // ── Mark as Paid (pending_payment → paid) ──
    $('#markPaidBtn').on('click', function() {
        let ids = [];
        $('.pending-checkbox:checked').each(function() { ids.push($(this).val()); });
        if (!ids.length) return;

        Swal.fire({
            title: 'Mark as Paid?',
            html: `<strong>${ids.length}</strong> claim(s) will be marked as <span class="badge bg-primary">Paid</span>.`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Yes, Mark Paid',
            confirmButtonColor: '#0d6efd',
        }).then((result) => {
            if (!result.isConfirmed) return;

            let $btn = $(this);
            $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Processing...');

            $.ajax({
                url: '{{ route("admin.claims.mark-paid") }}',
                method: 'POST',
                data: { claim_ids: ids, _token: '{{ csrf_token() }}' },
                success: function(res) {
                    if (res.success) {
                        showToast(res.message, 'success');
                        setTimeout(() => location.reload(), 1500);
                    } else {
                        showToast(res.message, 'error');
                        $btn.prop('disabled', false).html('<i class="bi bi-check2-all me-1"></i> Mark as Paid');
                    }
                },
                error: function(xhr) {
                    showToast(xhr.responseJSON?.message || 'An error occurred.', 'error');
                    $btn.prop('disabled', false).html('<i class="bi bi-check2-all me-1"></i> Mark as Paid');
                }
            });
        });
    });
});
</script>
@endpush
