@extends('layouts.app')
@section('title', 'My Inventory')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1">My Inventory Movements</h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('technician.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item active">Inventory</li>
                </ol>
            </nav>
        </div>
    </div>

    <!-- Summary -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <div class="text-muted small">Warehouse Stock</div>
                    <div class="fs-4 fw-bold text-primary">{{ $stats['total_warehouse_stock'] ?? 0 }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <div class="text-muted small">Today's Movements</div>
                    <div class="fs-4 fw-bold text-info">{{ $stats['today_movements'] ?? 0 }}</div>
                </div>
            </div>
        </div>
    </div>

    <!-- DataTable -->
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-transparent fw-bold">
            <i class="bi bi-arrow-left-right me-1"></i> Stock Movements (My Tickets)
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table id="tech-inventory-table" class="table table-hover table-sm align-middle" style="width:100%">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Movement #</th>
                            <th>Item</th>
                            <th>Type</th>
                            <th>Qty</th>
                            <th>Router IDs</th>
                            <th>Ticket</th>
                            <th>Condition</th>
                            <th>Date</th>
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
    $('#tech-inventory-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: '{{ route("technician.inventory.datatable") }}',
        columns: [
            { data: 'DT_RowIndex', orderable: false, searchable: false, width: '40px' },
            { data: 'movement_no' },
            { data: 'item_name' },
            { data: 'movement_type', orderable: false },
            {
                data: 'quantity',
                className: 'text-center fw-bold',
                render: function(data) {
                    var cls = data < 0 ? 'text-danger' : 'text-success';
                    return '<span class="' + cls + '">' + data + '</span>';
                }
            },
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
                    if (!data || data === '-') return '-';
                    return '<span class="badge bg-outline-primary border">' + data + '</span>';
                }
            },
            { data: 'condition', orderable: false },
            { data: 'movement_date' }
        ],
        order: [[1, 'desc']],
        pageLength: 25,
        language: { search: '', searchPlaceholder: 'Search movements...' }
    });
});
</script>
@endpush
