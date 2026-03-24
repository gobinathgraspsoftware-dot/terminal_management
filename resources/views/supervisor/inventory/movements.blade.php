@extends('layouts.app')
@section('title', 'Stock Movements')

@section('content')
@php $roleName = explode('.', Route::currentRouteName())[0]; @endphp
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1"><i class="bi bi-arrow-left-right me-2"></i>Stock Movements</h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route($roleName . '.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route($roleName . '.inventory.index') }}">Inventory</a></li>
                    <li class="breadcrumb-item active">Movements</li>
                </ol>
            </nav>
        </div>
    </div>

    <!-- Stats -->
    <div class="row g-3 mb-4">
        <div class="col-md-2">
            <div class="card border-0 shadow-sm text-center">
                <div class="card-body py-2">
                    <div class="fs-4 fw-bold">{{ $stats['total'] ?? 0 }}</div>
                    <small class="text-muted">Total</small>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card border-0 shadow-sm text-center border-start border-3 border-success">
                <div class="card-body py-2">
                    <div class="fs-4 fw-bold text-success">{{ $stats['stock_in'] ?? 0 }}</div>
                    <small class="text-muted">Stock In</small>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card border-0 shadow-sm text-center border-start border-3 border-danger">
                <div class="card-body py-2">
                    <div class="fs-4 fw-bold text-danger">{{ $stats['stock_out'] ?? 0 }}</div>
                    <small class="text-muted">Stock Out</small>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card border-0 shadow-sm text-center border-start border-3 border-info">
                <div class="card-body py-2">
                    <div class="fs-4 fw-bold text-info">{{ $stats['returns'] ?? 0 }}</div>
                    <small class="text-muted">Returns</small>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card border-0 shadow-sm text-center border-start border-3 border-warning">
                <div class="card-body py-2">
                    <div class="fs-4 fw-bold text-warning">{{ $stats['adjustments'] ?? 0 }}</div>
                    <small class="text-muted">Adjustments</small>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card border-0 shadow-sm text-center border-start border-3 border-primary">
                <div class="card-body py-2">
                    <div class="fs-4 fw-bold text-primary">{{ $stats['transfers'] ?? 0 }}</div>
                    <small class="text-muted">Transfers</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Movement Type</label>
                    <select id="filter-type" class="form-select">
                        <option value="">All Types</option>
                        <option value="stock_in">Stock In</option>
                        <option value="stock_out">Stock Out</option>
                        <option value="stock_return">Stock Return</option>
                        <option value="stock_adjustment">Adjustment</option>
                        <option value="stock_transfer">Transfer</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Item</label>
                    <select id="filter-item" class="form-select">
                        <option value="">All Items</option>
                        @foreach($items as $item)
                        <option value="{{ $item->id }}">{{ $item->item_code }} - {{ $item->item_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Date From</label>
                    <input type="date" id="filter-from" class="form-control">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Date To</label>
                    <input type="date" id="filter-to" class="form-control">
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="button" id="btn-filter" class="btn btn-primary me-2"><i class="bi bi-funnel"></i></button>
                    <button type="button" id="btn-reset" class="btn btn-outline-secondary"><i class="bi bi-arrow-counterclockwise"></i></button>
                </div>
            </div>
        </div>
    </div>

    <!-- DataTable -->
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table id="movements-table" class="table table-hover align-middle w-100">
                    <thead class="table-light">
                        <tr>
                            <th width="5%">#</th>
                            <th>Movement #</th>
                            <th>Item</th>
                            <th>Type</th>
                            <th>Qty</th>
                            <th>From</th>
                            <th>To</th>
                            <th>Ticket</th>
                            <th>Performed By</th>
                            <th>Date</th>
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
$(document).ready(function() {
    let rowCounter = 0;

    // Pre-fill item filter from URL
    let urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('inventory_item_id')) {
        $('#filter-item').val(urlParams.get('inventory_item_id'));
    }

    var table = $('#movements-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route($roleName . ".inventory.movements.datatable") }}',
            data: function(d) {
                d.movement_type     = $('#filter-type').val();
                d.inventory_item_id = $('#filter-item').val();
                d.date_from         = $('#filter-from').val();
                d.date_to           = $('#filter-to').val();
            }
        },
        columns: [
            { data: null, orderable: false, searchable: false, render: function() { return ++rowCounter; } },
            { data: 'movement_no', name: 'movement_no' },
            { data: 'item_name', name: 'item_name', orderable: false },
            { data: 'type_badge', name: 'movement_type' },
            { data: 'qty_display', name: 'quantity', className: 'text-center' },
            { data: 'from_display', name: 'from_display', orderable: false },
            { data: 'to_display', name: 'to_display', orderable: false },
            { data: 'ticket_display', name: 'ticket_display', orderable: false },
            { data: 'performed_by_name', name: 'performed_by_name', orderable: false },
            { data: 'date_display', name: 'movement_date' },
        ],
        order: [[9, 'desc']],
        drawCallback: function() { rowCounter = this.api().page() * this.api().page.len(); }
    });

    $('#btn-filter').on('click', function() { rowCounter = 0; table.draw(); });
    $('#btn-reset').on('click', function() {
        $('#filter-type, #filter-item, #filter-from, #filter-to').val('');
        rowCounter = 0;
        table.draw();
    });
});
</script>
@endpush
