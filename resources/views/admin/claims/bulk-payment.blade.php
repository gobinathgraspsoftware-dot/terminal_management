@extends('layouts.app')

@section('title', 'Bulk Payment Processing')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1">Bulk Payment Processing</h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.claims.index') }}">Claim Management</a></li>
                    <li class="breadcrumb-item active">Bulk Payment</li>
                </ol>
            </nav>
        </div>
    </div>

    <!-- Category Filter -->
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body py-2">
            <div class="row g-2 align-items-center">
                <div class="col-auto">
                    <label class="form-label mb-0 small fw-semibold">Filter Category:</label>
                </div>
                <div class="col-auto">
                    <div class="btn-group btn-group-sm">
                        <a href="{{ route('admin.claims.bulk-payment', ['category' => 'all']) }}"
                           class="btn {{ $category === 'all' ? 'btn-primary' : 'btn-outline-primary' }}">All</a>
                        <a href="{{ route('admin.claims.bulk-payment', ['category' => 'ticket']) }}"
                           class="btn {{ $category === 'ticket' ? 'btn-primary' : 'btn-outline-primary' }}">Ticket Claims</a>
                        <a href="{{ route('admin.claims.bulk-payment', ['category' => 'other']) }}"
                           class="btn {{ $category === 'other' ? 'btn-primary' : 'btn-outline-primary' }}">Other Claims</a>
                    </div>
                </div>
                <div class="col-auto ms-auto">
                    <span class="badge bg-success fs-6">{{ $verifiedClaims->count() }} Verified Claims</span>
                </div>
            </div>
        </div>
    </div>

    @if($verifiedClaims->isEmpty())
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center py-5">
                <i class="bi bi-check-circle text-muted" style="font-size: 3rem;"></i>
                <h5 class="mt-3 text-muted">No Verified Claims</h5>
                <p class="text-muted">There are no verified claims ready for payment processing.</p>
                <a href="{{ route('admin.claims.index') }}" class="btn btn-primary">
                    <i class="bi bi-arrow-left me-1"></i> Back to Claims
                </a>
            </div>
        </div>
    @else
        <!-- Bulk Action Bar -->
        <div class="card border-0 shadow-sm mb-3 sticky-top bg-white" style="top: 60px; z-index: 100;">
            <div class="card-body py-2">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" id="select-all">
                            <label class="form-check-label fw-semibold" for="select-all">Select All</label>
                        </div>
                    </div>
                    <div class="d-flex align-items-center gap-3">
                        <span class="text-muted small">Selected: <strong id="selected-count">0</strong> claims</span>
                        <span class="text-muted small">Total: <strong id="selected-total" class="text-primary">RM 0.00</strong></span>
                        <button type="button" id="btn-process-payment" class="btn btn-warning btn-sm" disabled>
                            <i class="bi bi-cash-stack me-1"></i> Proceed Payment
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Claims Table -->
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover table-sm align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 40px;"><input type="checkbox" class="form-check-input" id="select-all-top"></th>
                                <th>Claim ID</th>
                                <th>Category</th>
                                <th>Ticket</th>
                                <th>Submitted By</th>
                                <th>Technician</th>
                                <th class="text-end">Amount (RM)</th>
                                <th>Submitted</th>
                                <th>Verified</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($verifiedClaims as $claim)
                            <tr>
                                <td>
                                    <input type="checkbox" class="form-check-input claim-checkbox"
                                           value="{{ $claim->id }}" data-amount="{{ (float)$claim->total_amount }}">
                                </td>
                                <td>
                                    <a href="{{ route('admin.claims.show', $claim->id) }}">{{ $claim->claim_no }}</a>
                                </td>
                                <td>
                                    @if($claim->claim_category === 'ticket')
                                        <span class="badge bg-info">Ticket</span>
                                    @else
                                        <span class="badge bg-secondary">Other</span>
                                    @endif
                                </td>
                                <td>{{ $claim->ticket->ticket_no ?? '-' }}</td>
                                <td>{{ $claim->submitter->name ?? '-' }}</td>
                                <td>{{ $claim->technician->name ?? '-' }}</td>
                                <td class="text-end fw-semibold">{{ number_format((float)$claim->total_amount, 2) }}</td>
                                <td>{{ $claim->submitted_at ? $claim->submitted_at->format('d/m/Y') : '-' }}</td>
                                <td>{{ $claim->verified_at ? $claim->verified_at->format('d/m/Y') : '-' }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="table-light">
                            <tr>
                                <td colspan="6" class="text-end fw-bold">Grand Total:</td>
                                <td class="text-end fw-bold text-primary">
                                    RM {{ number_format($verifiedClaims->sum(fn($c) => (float)$c->total_amount), 2) }}
                                </td>
                                <td colspan="2"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
$(function() {
    function updateSummary() {
        var count = 0, total = 0;
        $('.claim-checkbox:checked').each(function() {
            count++;
            total += parseFloat($(this).data('amount')) || 0;
        });
        $('#selected-count').text(count);
        $('#selected-total').text('RM ' + total.toFixed(2));
        $('#btn-process-payment').prop('disabled', count === 0);
    }

    // Select all checkboxes
    $('#select-all, #select-all-top').on('change', function() {
        var checked = $(this).is(':checked');
        $('.claim-checkbox').prop('checked', checked);
        $('#select-all, #select-all-top').prop('checked', checked);
        updateSummary();
    });

    // Individual checkbox
    $('.claim-checkbox').on('change', function() {
        var total = $('.claim-checkbox').length;
        var checked = $('.claim-checkbox:checked').length;
        $('#select-all, #select-all-top').prop('checked', total === checked);
        updateSummary();
    });

    // Process payment
    $('#btn-process-payment').on('click', function() {
        var ids = [];
        $('.claim-checkbox:checked').each(function() { ids.push($(this).val()); });

        if (ids.length === 0) {
            showToast('Please select at least one claim.', 'warning');
            return;
        }

        Swal.fire({
            title: 'Process Payment?',
            html: 'Mark <strong>' + ids.length + '</strong> claim(s) as Pending Payment?<br>Total: <strong>' + $('#selected-total').text() + '</strong>',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#ffc107',
            confirmButtonText: 'Yes, Proceed'
        }).then(function(result) {
            if (result.isConfirmed) {
                $.ajax({
                    url: '{{ route("admin.claims.process-bulk-payment") }}',
                    method: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}',
                        claim_ids: ids
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
});
</script>
@endpush
