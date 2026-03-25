@extends('layouts.app')
@section('title', 'Stock Out')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1">Stock Out</h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.inventory.index') }}">Inventory</a></li>
                    <li class="breadcrumb-item active">Stock Out</li>
                </ol>
            </nav>
        </div>
    </div>

    <!-- Info Banner -->
    <div class="alert alert-info border-0 shadow-sm d-flex align-items-center mb-4">
        <i class="bi bi-info-circle-fill fs-5 me-3"></i>
        <div>
            <strong>Auto-Triggered:</strong> Stock out is automatically processed when an <strong>Installation</strong> ticket is created.
            Each installation deducts router quantity from warehouse stock. This page shows all stock-out records.
        </div>
    </div>

    <!-- Filters -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <div class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label">From Date</label>
                    <input type="date" id="filter-date-from" class="form-control form-control-sm">
                </div>
                <div class="col-md-3">
                    <label class="form-label">To Date</label>
                    <input type="date" id="filter-date-to" class="form-control form-control-sm">
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button id="btn-filter" class="btn btn-primary btn-sm me-2"><i class="bi bi-search me-1"></i>Filter</button>
                    <button id="btn-reset" class="btn btn-outline-secondary btn-sm"><i class="bi bi-x-circle me-1"></i>Reset</button>
                </div>
            </div>
        </div>
    </div>

    <!-- DataTable -->
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table id="stockout-table" class="table table-hover table-sm align-middle" style="width:100%">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Movement #</th>
                            <th>Item</th>
                            <th>Qty</th>
                            <th>Router IDs</th>
                            <th>Ticket</th>
                            <th>Reason</th>
                            <th>Stock Out Date</th>
                            <th>Performed By</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(function() {
    @php $roleName = explode('.', Route::currentRouteName())[0]; @endphp

    var table = $('#stockout-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route($roleName . ".inventory.stock-out.datatable") }}',
            data: function(d) {
                d.date_from = $('#filter-date-from').val();
                d.date_to = $('#filter-date-to').val();
            }
        },
        columns: [
            { data: 'DT_RowIndex', orderable: false, searchable: false, width: '40px' },
            { data: 'movement_no' },
            {
                data: null,
                render: function(data) {
                    return '<span class="fw-semibold">' + data.item_code + '</span><br><small class="text-muted">' + data.item_name + '</small>';
                }
            },
            { data: 'quantity', className: 'text-center fw-bold text-danger' },
            {
                data: 'router_ids',
                render: function(data) {
                    if (!data || data === '-') return '<span class="text-muted">-</span>';
                    return '<small class="text-primary">' + data + '</small>';
                }
            },
            {
                data: 'ticket_no',
                render: function(data) {
                    if (!data || data === '-') return '<span class="text-muted">-</span>';
                    return '<span class="badge bg-outline-primary border">' + data + '</span>';
                }
            },
            { data: 'reason' },
            { data: 'stockout_date' },
            { data: 'performed_by' }
        ],
        order: [[1, 'desc']],
        pageLength: 25,
        language: { search: '', searchPlaceholder: 'Search...' }
    });

    $('#btn-filter').on('click', function() { table.ajax.reload(); });
    $('#btn-reset').on('click', function() {
        $('#filter-date-from, #filter-date-to').val('');
        table.ajax.reload();
    });
});
</script>
@endpush
