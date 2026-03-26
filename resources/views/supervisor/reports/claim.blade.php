@extends('layouts.app')
@section('title', 'Claim Report — TMS')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1"><i class="bi bi-receipt me-2"></i>Claim Report</h4>
            <nav aria-label="breadcrumb"><ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('supervisor.reports.index') }}">Reports</a></li>
                <li class="breadcrumb-item active">Claim Report</li>
            </ol></nav>
        </div>
        <div class="btn-group">
            <button class="btn btn-success btn-sm" id="btnExportExcel"><i class="bi bi-file-earmark-excel me-1"></i> Excel</button>
            <button class="btn btn-danger btn-sm" id="btnExportPdf"><i class="bi bi-file-earmark-pdf me-1"></i> PDF</button>
        </div>
    </div>

    {{-- Summary Cards --}}
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-2"><div class="card border-0 shadow-sm"><div class="card-body py-3 text-center">
            <div class="fs-4 fw-bold text-primary">{{ number_format($stats['total'] ?? 0) }}</div><small class="text-muted">Total Claims</small>
        </div></div></div>
        <div class="col-6 col-md-2"><div class="card border-0 shadow-sm"><div class="card-body py-3 text-center">
            <div class="fs-4 fw-bold text-success">RM {{ number_format($stats['total_amount'] ?? 0, 2) }}</div><small class="text-muted">Total Amount</small>
        </div></div></div>
        <div class="col-6 col-md-2"><div class="card border-0 shadow-sm"><div class="card-body py-3 text-center">
            <div class="fs-4 fw-bold text-warning">{{ number_format($stats['pending'] ?? 0) }}</div><small class="text-muted">Pending</small>
        </div></div></div>
        <div class="col-6 col-md-2"><div class="card border-0 shadow-sm"><div class="card-body py-3 text-center">
            <div class="fs-4 fw-bold text-info">{{ number_format($stats['approved'] ?? 0) }}</div><small class="text-muted">Approved</small>
        </div></div></div>
        <div class="col-6 col-md-2"><div class="card border-0 shadow-sm"><div class="card-body py-3 text-center">
            <div class="fs-4 fw-bold text-success">{{ number_format($stats['paid'] ?? 0) }}</div><small class="text-muted">Paid</small>
        </div></div></div>
        <div class="col-6 col-md-2"><div class="card border-0 shadow-sm"><div class="card-body py-3 text-center">
            <div class="fs-4 fw-bold text-danger">{{ number_format($stats['rejected'] ?? 0) }}</div><small class="text-muted">Rejected</small>
        </div></div></div>
    </div>

    @include('components.reports.filter-panel', [
        'filters' => ['date_range','technician','claim_type','claim_status','claim_category','job_type','merchant'],
        'filterOptions' => $filterOptions,
    ])

    <div class="card shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table id="reportTable" class="table table-sm table-hover mb-0" style="width:100%">
                    <thead class="table-light">
                        <tr>
                            <th>#</th><th>Claim No</th><th>Category</th><th>Ticket No</th><th>Technician</th><th>Vendor</th>
                            <th>Date</th><th class="text-end">Mileage</th><th class="text-end">Allowance</th><th class="text-end">Total</th><th>Status</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
var reportTable;
$(document).ready(function() {
    reportTable = $('#reportTable').DataTable({
        processing: true, serverSide: true, searching: false,
        ajax: { url: '{{ route("supervisor.reports.claim.data") }}', type: 'GET', data: function(d) { return $.extend(d, getReportFilters()); } },
        columns: [
            { data: null, orderable: false, render: function(d,t,r,m) { return m.row + m.settings._iDisplayStart + 1; } },
            { data: 'claim_no' }, { data: 'claim_category' }, { data: 'ticket_no' }, { data: 'technician' }, { data: 'vendor' },
            { data: 'claim_date' }, { data: 'total_mileage', className: 'text-end' }, { data: 'total_allowance', className: 'text-end' },
            { data: 'total_amount', className: 'text-end' }, { data: 'status' },
        ],
        order: [], pageLength: 25, language: { emptyTable: 'No claims found.' }
    });

    $('#btnExportExcel').on('click', function() { window.location.href = '{{ route("supervisor.reports.claim.export") }}?' + $.param(getReportFilters()) + '&format=xlsx'; });
    $('#btnExportPdf').on('click', function() { window.location.href = '{{ route("supervisor.reports.claim.export") }}?' + $.param(getReportFilters()) + '&format=pdf'; });
});
</script>
@endpush
