@php use App\Models\Claim; @endphp
@extends('layouts.app')

@section('title', 'Bulk Payment')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1">Bulk Payment</h4>
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
            <div class="d-flex gap-2 align-items-center">
                <span class="text-muted small me-2">Filter:</span>
                <a href="{{ route('admin.claims.bulk-payment', ['category' => 'all']) }}" class="btn btn-sm {{ $category === 'all' ? 'btn-primary' : 'btn-outline-primary' }}">All</a>
                <a href="{{ route('admin.claims.bulk-payment', ['category' => 'ticket']) }}" class="btn btn-sm {{ $category === 'ticket' ? 'btn-info' : 'btn-outline-info' }}">Ticket Claims</a>
                <a href="{{ route('admin.claims.bulk-payment', ['category' => 'other']) }}" class="btn btn-sm {{ $category === 'other' ? 'btn-secondary' : 'btn-outline-secondary' }}">Other Claims</a>
            </div>
        </div>
    </div>

    @if($verifiedClaims->isEmpty())
        <div class="alert alert-info">
            <i class="bi bi-info-circle me-1"></i> No verified claims available for payment processing.
        </div>
    @else
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h6 class="mb-0"><i class="bi bi-cash-stack me-1"></i> Verified Claims ({{ $verifiedClaims->count() }})</h6>
            <div class="d-flex gap-2">
                <button type="button" id="btn-select-all" class="btn btn-sm btn-outline-primary">Select All</button>
                <button type="button" id="btn-process-payment" class="btn btn-sm btn-success" disabled>
                    <i class="bi bi-check2-all me-1"></i> Process Payment (<span id="selected-count">0</span>)
                </button>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover table-sm align-middle">
                    <thead class="table-light">
                        <tr>
                            <th style="width:40px;"><input type="checkbox" id="check-all" class="form-check-input"></th>
                            <th>Claim No</th>
                            <th>Category</th>
                            <th>Technician</th>
                            <th>Description</th>
                            <th>Ticket</th>
                            <th class="text-end">Amount (RM)</th>
                            <th>Submitted</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($verifiedClaims as $claim)
                        <tr>
                            <td><input type="checkbox" class="form-check-input claim-check" value="{{ $claim->id }}" data-amount="{{ $claim->total_amount }}"></td>
                            <td><a href="{{ route('admin.claims.show', $claim->id) }}">{{ $claim->claim_no }}</a></td>
                            <td>
                                @if($claim->claim_category === 'ticket')
                                    <span class="badge bg-info">Ticket</span>
                                @else
                                    <span class="badge bg-secondary">Other</span>
                                @endif
                            </td>
                            <td>{{ $claim->technician->name ?? ($claim->submitter->name ?? '-') }}</td>
                            <td>{{ \Illuminate\Support\Str::limit($claim->description, 40) }}</td>
                            <td>{{ $claim->ticket->ticket_no ?? '-' }}</td>
                            <td class="text-end fw-semibold">{{ number_format((float) $claim->total_amount, 2) }}</td>
                            <td>{{ $claim->submitted_at ? $claim->submitted_at->format('d/m/Y') : '-' }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="table-light">
                            <td colspan="6" class="text-end fw-bold">Selected Total:</td>
                            <td class="text-end fw-bold text-primary" id="selected-total">RM 0.00</td>
                            <td></td>
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
        $('.claim-check:checked').each(function() {
            count++;
            total += parseFloat($(this).data('amount')) || 0;
        });
        $('#selected-count').text(count);
        $('#selected-total').text('RM ' + total.toFixed(2));
        $('#btn-process-payment').prop('disabled', count === 0);
    }

    $('#check-all').on('change', function() {
        $('.claim-check').prop('checked', $(this).is(':checked'));
        updateSummary();
    });

    $('.claim-check').on('change', function() {
        updateSummary();
        if (!$(this).is(':checked')) {
            $('#check-all').prop('checked', false);
        }
    });

    $('#btn-select-all').on('click', function() {
        var allChecked = $('.claim-check:checked').length === $('.claim-check').length;
        $('.claim-check').prop('checked', !allChecked);
        $('#check-all').prop('checked', !allChecked);
        updateSummary();
    });

    $('#btn-process-payment').on('click', function() {
        var ids = [];
        $('.claim-check:checked').each(function() { ids.push($(this).val()); });
        if (ids.length === 0) return;

        var total = $('#selected-total').text();

        Swal.fire({
            title: 'Process Bulk Payment?',
            html: '<strong>' + ids.length + '</strong> claim(s) totalling <strong>' + total + '</strong> will be marked as Pending Payment.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#198754',
            confirmButtonText: 'Yes, Process'
        }).then(function(result) {
            if (result.isConfirmed) {
                $.ajax({
                    url: '{{ route("admin.claims.process-bulk-payment") }}',
                    method: 'POST',
                    data: { _token: '{{ csrf_token() }}', claim_ids: ids },
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
