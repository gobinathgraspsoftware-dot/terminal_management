@extends('layouts.app')
@section('title', 'Router Movement Report — TMS')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1"><i class="bi bi-router me-2"></i>Router Movement Report</h4>
            <nav aria-label="breadcrumb"><ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('admin.reports.index') }}">Reports</a></li>
                <li class="breadcrumb-item active">Router Movement</li>
            </ol></nav>
        </div>
        <div class="btn-group">
            <button class="btn btn-success btn-sm" id="btnExportExcel"><i class="bi bi-file-earmark-excel me-1"></i> Excel</button>
            <button class="btn btn-danger btn-sm" id="btnExportPdf"><i class="bi bi-file-earmark-pdf me-1"></i> PDF</button>
            <button class="btn btn-secondary btn-sm" id="btnPrint"><i class="bi bi-printer me-1"></i> Print</button>
        </div>
    </div>

    @include('components.reports.filter-panel', [
        'filters' => ['date_range','terminal_id','movement_type','merchant','ticket_id'],
        'filterOptions' => $filterOptions,
    ])

    <div class="card shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table id="reportTable" class="table table-sm table-hover mb-0" style="width:100%">
                    <thead class="table-light">
                        <tr>
                            <th>#</th><th>Movement No</th><th>Date</th><th>Item Code</th><th>Item Name</th><th>Terminal ID</th>
                            <th>Type</th><th class="text-end">Qty</th><th>From</th><th>To</th><th>Ticket</th><th>Condition</th><th>By</th>
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
        ajax: {
            url: '{{ route("admin.reports.router-movement.data") }}',
            type: 'GET',
            data: function(d) { return $.extend(d, getReportFilters()); },
            error: function(xhr, error, thrown) {
                console.error('Report AJAX Error:', xhr.status, xhr.responseText);
                if (typeof showToast === 'function') {
                    showToast('Failed to load report data. Check browser console (F12) for details.', 'error');
                }
            }
        },
        columns: [
            { data: null, orderable: false, render: function(d,t,r,m) { return m.row + m.settings._iDisplayStart + 1; } },
            { data: 'movement_no' }, { data: 'movement_date' }, { data: 'item_code' }, { data: 'item_name' },
            { data: 'serial_number' }, { data: 'movement_type' }, { data: 'quantity', className: 'text-end' },
            { data: 'from' }, { data: 'to' }, { data: 'ticket_no' }, { data: 'condition' }, { data: 'performed_by' },
        ],
        order: [], pageLength: 25, language: { emptyTable: 'No router movements found.' }
    });

    $('#btnExportExcel').on('click', function() { window.location.href = '{{ route("admin.reports.router-movement.export") }}?' + $.param(getReportFilters()) + '&format=xlsx'; });
    $('#btnExportPdf').on('click', function() { window.location.href = '{{ route("admin.reports.router-movement.export") }}?' + $.param(getReportFilters()) + '&format=pdf'; });
    $('#btnPrint').on('click', function() { openPrintView('router-movement'); });
});
</script>
@endpush
