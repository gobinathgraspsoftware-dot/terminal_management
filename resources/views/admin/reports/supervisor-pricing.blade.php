@extends('layouts.app')
@section('title', 'Supervisor Pricing Report — TMS')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1"><i class="bi bi-tags me-2"></i>Supervisor Pricing Report</h4>
            <nav aria-label="breadcrumb"><ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('admin.reports.index') }}">Reports</a></li>
                <li class="breadcrumb-item active">Supervisor Pricing</li>
            </ol></nav>
        </div>
        <div class="btn-group">
            <button class="btn btn-success btn-sm" id="btnExportExcel"><i class="bi bi-file-earmark-excel me-1"></i> Excel</button>
            <button class="btn btn-danger btn-sm" id="btnExportPdf"><i class="bi bi-file-earmark-pdf me-1"></i> PDF</button>
        </div>
    </div>

    @include('components.reports.filter-panel', [
        'filters' => ['supervisor','job_type','job_category'],
        'filterOptions' => $filterOptions,
    ])

    <div class="card shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table id="reportTable" class="table table-sm table-hover mb-0" style="width:100%">
                    <thead class="table-light">
                        <tr><th>#</th><th>Supervisor</th><th>Job Category</th><th>Job Type</th><th class="text-end">Price (RM)</th></tr>
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
        ajax: { url: '{{ route("admin.reports.supervisor-pricing.data") }}', type: 'GET', data: function(d) { return $.extend(d, getReportFilters()); } },
        columns: [
            { data: null, orderable: false, render: function(d,t,r,m) { return m.row + m.settings._iDisplayStart + 1; } },
            { data: 'supervisor' }, { data: 'job_category' }, { data: 'job_type' },
            { data: 'price', className: 'text-end' },
        ],
        order: [], pageLength: 25, language: { emptyTable: 'No pricing data found.' }
    });

    $('#btnExportExcel').on('click', function() { window.location.href = '{{ route("admin.reports.supervisor-pricing.export") }}?' + $.param(getReportFilters()) + '&format=xlsx'; });
    $('#btnExportPdf').on('click', function() { window.location.href = '{{ route("admin.reports.supervisor-pricing.export") }}?' + $.param(getReportFilters()) + '&format=pdf'; });
});
</script>
@endpush
