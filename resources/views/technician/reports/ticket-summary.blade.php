@extends('layouts.app')
@section('title', 'My Ticket Summary — TMS')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1"><i class="bi bi-clipboard-data me-2"></i>My Ticket Summary</h4>
            <nav aria-label="breadcrumb"><ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('technician.reports.index') }}">Reports</a></li>
                <li class="breadcrumb-item active">Ticket Summary</li>
            </ol></nav>
        </div>
        <div class="btn-group">
            <button class="btn btn-success btn-sm" id="btnExportExcel"><i class="bi bi-file-earmark-excel me-1"></i> Excel</button>
            <button class="btn btn-danger btn-sm" id="btnExportPdf"><i class="bi bi-file-earmark-pdf me-1"></i> PDF</button>
        </div>
    </div>

    {{-- Summary Cards --}}
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm"><div class="card-body py-3 text-center">
                <div class="fs-4 fw-bold text-primary">{{ number_format($stats['total'] ?? 0) }}</div>
                <small class="text-muted">Total</small>
            </div></div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm"><div class="card-body py-3 text-center">
                <div class="fs-4 fw-bold text-warning">{{ number_format($stats['in_progress'] ?? 0) }}</div>
                <small class="text-muted">In Progress</small>
            </div></div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm"><div class="card-body py-3 text-center">
                <div class="fs-4 fw-bold text-success">{{ number_format($stats['completed'] ?? 0) }}</div>
                <small class="text-muted">Completed</small>
            </div></div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm"><div class="card-body py-3 text-center">
                <div class="fs-4 fw-bold text-danger">{{ number_format($stats['failed'] ?? 0) }}</div>
                <small class="text-muted">Failed</small>
            </div></div>
        </div>
    </div>

    @include('components.reports.filter-panel', [
        'filters' => ['date_range','status','job_type','job_category','vendor'],
        'filterOptions' => $filterOptions,
    ])

    <div class="card shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table id="reportTable" class="table table-sm table-hover mb-0" style="width:100%">
                    <thead class="table-light">
                        <tr>
                            <th>#</th><th>Ticket No</th><th>Vendor</th><th>Merchant</th><th>State</th><th>City</th>
                            <th>Job Category</th><th>Job Type</th><th>Status</th><th>Priority</th><th>Created</th>
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
        ajax: { url: '{{ route("technician.reports.ticket-summary.data") }}', type: 'GET', data: function(d) { return $.extend(d, getReportFilters()); } },
        columns: [
            { data: null, orderable: false, render: function(d,t,r,m) { return m.row + m.settings._iDisplayStart + 1; } },
            { data: 'ticket_no' }, { data: 'vendor' }, { data: 'merchant_name' }, { data: 'state' }, { data: 'city' },
            { data: 'job_category' }, { data: 'job_type' }, { data: 'status' }, { data: 'priority' }, { data: 'created_at' },
        ],
        order: [], pageLength: 25, language: { emptyTable: 'No tickets found.' }
    });

    $('#btnExportExcel').on('click', function() { window.location.href = '{{ route("technician.reports.ticket-summary.export") }}?' + $.param(getReportFilters()) + '&format=xlsx'; });
    $('#btnExportPdf').on('click', function() { window.location.href = '{{ route("technician.reports.ticket-summary.export") }}?' + $.param(getReportFilters()) + '&format=pdf'; });
});
</script>
@endpush
