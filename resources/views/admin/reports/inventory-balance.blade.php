@extends('layouts.app')
@section('title', 'Inventory Balance Report — TMS')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1"><i class="bi bi-boxes me-2"></i>Inventory Balance Report</h4>
            <nav aria-label="breadcrumb"><ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('admin.reports.index') }}">Reports</a></li>
                <li class="breadcrumb-item active">Inventory Balance</li>
            </ol></nav>
        </div>
        <div class="btn-group">
            <button class="btn btn-success btn-sm" id="btnExportExcel"><i class="bi bi-file-earmark-excel me-1"></i> Excel</button>
            <button class="btn btn-danger btn-sm" id="btnExportPdf"><i class="bi bi-file-earmark-pdf me-1"></i> PDF</button>
        </div>
    </div>

    @include('components.reports.filter-panel', [
        'filters' => ['item_type','low_stock','item_search'],
        'filterOptions' => $filterOptions,
    ])

    <div class="card shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table id="reportTable" class="table table-sm table-hover mb-0" style="width:100%">
                    <thead class="table-light">
                        <tr>
                            <th>#</th><th>Code</th><th>Name</th><th>Type</th><th>Category</th><th>Serial</th><th>Model</th>
                            <th class="text-end">Warehouse</th><th class="text-end">Technician</th><th class="text-end">Total</th>
                            <th>Reorder</th><th>Status</th>
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
        ajax: { url: '{{ route("admin.reports.inventory-balance.data") }}', type: 'GET', data: function(d) { return $.extend(d, getReportFilters()); } },
        columns: [
            { data: null, orderable: false, render: function(d,t,r,m) { return m.row + m.settings._iDisplayStart + 1; } },
            { data: 'item_code' }, { data: 'item_name' }, { data: 'item_type' }, { data: 'category' },
            { data: 'serial_number' }, { data: 'model' },
            { data: 'warehouse_qty', className: 'text-end' }, { data: 'technician_qty', className: 'text-end' },
            { data: 'total_qty', className: 'text-end' }, { data: 'reorder_level' }, { data: 'low_stock' },
        ],
        order: [], pageLength: 25, language: { emptyTable: 'No inventory items found.' }
    });

    $('#btnExportExcel').on('click', function() { window.location.href = '{{ route("admin.reports.inventory-balance.export") }}?' + $.param(getReportFilters()) + '&format=xlsx'; });
    $('#btnExportPdf').on('click', function() { window.location.href = '{{ route("admin.reports.inventory-balance.export") }}?' + $.param(getReportFilters()) + '&format=pdf'; });
});
</script>
@endpush
