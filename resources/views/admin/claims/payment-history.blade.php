@extends('layouts.app')
@section('title', 'Payment History')

@section('content')
<div class="page-header">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div>
            <h1><i class="bi bi-clock-history me-2 text-success"></i>Payment History</h1>
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.claims.index') }}">Claims</a></li>
                <li class="breadcrumb-item active">Payment History</li>
            </ol>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.claims.export', ['status' => 'paid']) }}" class="btn btn-outline-success btn-sm">
                <i class="bi bi-download me-1"></i>Export Paid
            </a>
        </div>
    </div>
</div>

{{-- Summary Cards --}}
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="stats-card text-center">
            <div class="stats-icon bg-success bg-opacity-10 text-success mx-auto mb-2"><i class="bi bi-receipt fs-4"></i></div>
            <div class="stats-value text-success">{{ $paidCount }}</div>
            <div class="stats-label">Total Paid Claims</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stats-card text-center">
            <div class="stats-icon bg-primary bg-opacity-10 text-primary mx-auto mb-2"><i class="bi bi-currency-exchange fs-4"></i></div>
            <div class="stats-value text-primary" style="font-size:1.4rem">RM {{ number_format($totalPaid, 2) }}</div>
            <div class="stats-label">Total Paid Amount</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stats-card text-center">
            <div class="stats-icon bg-info bg-opacity-10 text-info mx-auto mb-2"><i class="bi bi-calendar-check fs-4"></i></div>
            <div class="stats-value text-info" style="font-size:1.4rem">RM {{ number_format($paidThisMonth, 2) }}</div>
            <div class="stats-label">Paid This Month</div>
        </div>
    </div>
</div>

{{-- Filter Bar --}}
<div class="card mb-3">
    <div class="card-body py-2">
        <div class="d-flex align-items-center gap-3 flex-wrap">
            <div class="d-flex align-items-center gap-2">
                <label class="form-label mb-0 fw-semibold small text-nowrap">Category:</label>
                <select id="categoryFilter" class="form-select form-select-sm" style="width:140px">
                    <option value="">All</option>
                    <option value="ticket">Ticket Claims</option>
                    <option value="other">Other Claims</option>
                </select>
            </div>
            <div class="d-flex align-items-center gap-2">
                <label class="form-label mb-0 fw-semibold small">From:</label>
                <input type="date" id="dateFrom" class="form-control form-control-sm" style="width:140px">
            </div>
            <div class="d-flex align-items-center gap-2">
                <label class="form-label mb-0 fw-semibold small">To:</label>
                <input type="date" id="dateTo" class="form-control form-control-sm" style="width:140px">
            </div>
            <button id="btnFilter" class="btn btn-primary btn-sm"><i class="bi bi-search me-1"></i>Filter</button>
            <button id="btnReset" class="btn btn-outline-secondary btn-sm"><i class="bi bi-x me-1"></i>Reset</button>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header"><i class="bi bi-table me-2"></i>Paid Claims</div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table id="paymentHistoryTable" class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Claim No</th>
                        <th>Category</th>
                        <th>Claimant</th>
                        <th>Ticket No</th>
                        <th>Vendor</th>
                        <th class="text-end">Amount (RM)</th>
                        <th>Paid At</th>
                        <th>Paid By</th>
                        <th>Status</th>
                        <th class="text-center">Action</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(function () {
    var table = $('#paymentHistoryTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route("admin.claims.payment-history-data") }}',
            data: function (d) {
                d.category  = $('#categoryFilter').val();
                d.date_from = $('#dateFrom').val();
                d.date_to   = $('#dateTo').val();
            }
        },
        columns: [
            { data: 'claim_no' },
            { data: 'category', orderable: false },
            { data: 'claimant', orderable: false },
            { data: 'ticket_no', orderable: false },
            { data: 'vendor', orderable: false },
            { data: 'total_amount', className: 'text-end' },
            { data: 'paid_at' },
            { data: 'paid_by', orderable: false },
            { data: 'status', orderable: false },
            { data: 'actions', orderable: false, searchable: false, className: 'text-center' }
        ],
        order: [[6, 'desc']],
        pageLength: 25,
        language: { processing: '<div class="spinner-border spinner-border-sm text-primary"></div>' }
    });

    $('#btnFilter').on('click', function () { table.ajax.reload(); });
    $('#btnReset').on('click', function () {
        $('#categoryFilter').val('');
        $('#dateFrom, #dateTo').val('');
        table.ajax.reload();
    });
});
</script>
@endpush
