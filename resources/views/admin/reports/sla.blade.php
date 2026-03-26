@extends('layouts.app')
@section('title', 'SLA Report — TMS')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1"><i class="bi bi-clock-history me-2"></i>SLA Report</h4>
            <nav aria-label="breadcrumb"><ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('admin.reports.index') }}">Reports</a></li>
                <li class="breadcrumb-item active">SLA Report</li>
            </ol></nav>
        </div>
        <div class="btn-group">
            <button class="btn btn-success btn-sm" id="btnExportExcel"><i class="bi bi-file-earmark-excel me-1"></i> Excel</button>
            <button class="btn btn-danger btn-sm" id="btnExportPdf"><i class="bi bi-file-earmark-pdf me-1"></i> PDF</button>
            <button class="btn btn-secondary btn-sm" id="btnPrint"><i class="bi bi-printer me-1"></i> Print</button>
        </div>
    </div>

    @include('components.reports.filter-panel', [
        'filters' => ['date_range','sla_status','supervisor','technician','job_type','vendor','rescheduled','sla_time_range'],
        'filterOptions' => $filterOptions,
    ])

    <div class="card shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table id="reportTable" class="table table-sm table-hover mb-0" style="width:100%">
                    <thead class="table-light">
                        <tr>
                            <th>#</th><th>Ticket No</th><th>Vendor</th><th>Merchant</th><th>Job Type</th>
                            <th>Supervisor</th><th>Technician</th><th>Status</th><th>SLA</th><th>Deadline</th>
                            <th>SLA Status</th><th>Rescheduled</th><th>Created</th>
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
        ajax: { url: '{{ route("admin.reports.sla.data") }}', type: 'GET', data: function(d) { return $.extend(d, getReportFilters()); } },
        columns: [
            { data: null, orderable: false, render: function(d,t,r,m) { return m.row + m.settings._iDisplayStart + 1; } },
            { data: 'ticket_no' }, { data: 'vendor' }, { data: 'merchant_name' }, { data: 'job_type' },
            { data: 'supervisor' }, { data: 'technician' }, { data: 'status' }, { data: 'sla_hours' },
            { data: 'sla_deadline' }, { data: 'sla_status' }, { data: 'rescheduled' }, { data: 'created_at' },
        ],
        order: [], pageLength: 25, language: { emptyTable: 'No tickets found.' }
    });

    $('#btnExportExcel').on('click', function() { window.location.href = '{{ route("admin.reports.sla.export") }}?' + $.param(getReportFilters()) + '&format=xlsx'; });
    $('#btnExportPdf').on('click', function() { window.location.href = '{{ route("admin.reports.sla.export") }}?' + $.param(getReportFilters()) + '&format=pdf'; });
    $('#btnPrint').on('click', function() { openPrintView('sla'); });
});
</script>
@endpush
