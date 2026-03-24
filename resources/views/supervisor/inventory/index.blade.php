@extends('layouts.app')
@section('title', 'Inventory Management')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1">Inventory Management</h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('supervisor.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item active">Inventory</li>
                </ol>
            </nav>
        </div>
    </div>

    <!-- Stats -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center">
                    <div class="fs-3 fw-bold text-primary">{{ $stats['total_items'] }}</div>
                    <small class="text-muted">Active Items</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center">
                    <div class="fs-3 fw-bold text-info">{{ $stats['team_members'] }}</div>
                    <small class="text-muted">Team Members</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center">
                    <div class="fs-3 fw-bold text-success">{{ $stats['warehouse'] }}</div>
                    <small class="text-muted">Warehouse Stock</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center">
                    <div class="fs-3 fw-bold text-warning">{{ $stats['team_stock'] }}</div>
                    <small class="text-muted">Team Stock</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-3">
                    <select id="filter-type" class="form-select">
                        <option value="">All Types</option>
                        <option value="router">Router</option>
                        <option value="accessory">Accessory</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <select id="filter-category" class="form-select">
                        <option value="">All Categories</option>
                        @foreach($categories as $cat)
                        <option value="{{ $cat->id }}">{{ $cat->category_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <button type="button" id="btn-filter" class="btn btn-primary"><i class="bi bi-funnel me-1"></i> Filter</button>
                    <button type="button" id="btn-reset" class="btn btn-outline-secondary"><i class="bi bi-arrow-counterclockwise"></i></button>
                </div>
            </div>
        </div>
    </div>

    <!-- DataTable -->
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table id="inventory-table" class="table table-hover align-middle w-100">
                    <thead class="table-light">
                        <tr>
                            <th width="5%">#</th>
                            <th>Item Code</th>
                            <th>Item Name</th>
                            <th>Category</th>
                            <th>Type</th>
                            <th class="text-center">Warehouse</th>
                            <th class="text-center">Total</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Stock Details Modal -->
<div class="modal fade" id="stockModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="stockModalTitle">Stock Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="stockModalBody"></div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    let rowCounter = 0;
    var table = $('#inventory-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route("supervisor.inventory.datatable") }}',
            data: function(d) {
                d.item_type       = $('#filter-type').val();
                d.job_category_id = $('#filter-category').val();
            }
        },
        columns: [
            { data: null, orderable: false, searchable: false, render: function() { return ++rowCounter; } },
            { data: 'item_code', name: 'item_code' },
            { data: 'item_name', name: 'item_name' },
            { data: 'category_name', orderable: false },
            { data: 'type_badge' },
            { data: 'warehouse_qty', orderable: false, className: 'text-center' },
            { data: 'total_qty', orderable: false, className: 'text-center' },
            { data: 'status_badge' },
            { data: 'action', orderable: false, searchable: false },
        ],
        order: [[1, 'asc']],
        drawCallback: function() { rowCounter = this.api().page() * this.api().page.len(); }
    });

    $('#btn-filter').on('click', function() { rowCounter = 0; table.draw(); });
    $('#btn-reset').on('click', function() { $('#filter-type, #filter-category').val(''); rowCounter = 0; table.draw(); });

    // View stock details
    $(document).on('click', '.btn-view-stock', function() {
        let id = $(this).data('id');
        let name = $(this).data('name');
        $('#stockModalTitle').text('Stock: ' + name);

        $.get('{{ route("supervisor.inventory.get-item-stock") }}', { item_id: id }, function(res) {
            if (res.success) {
                let html = '<table class="table table-sm"><thead><tr><th>Holder</th><th class="text-center">Qty</th></tr></thead><tbody>';
                html += '<tr><td><i class="bi bi-building me-1 text-primary"></i> Warehouse</td><td class="text-center fw-bold">' + res.warehouse_stock + '</td></tr>';
                if (res.tech_stock && res.tech_stock.length > 0) {
                    res.tech_stock.forEach(function(t) {
                        html += '<tr><td><i class="bi bi-person me-1 text-info"></i> ' + t.technician_name + '</td><td class="text-center">' + t.quantity + '</td></tr>';
                    });
                }
                html += '</tbody></table>';
                $('#stockModalBody').html(html);
                new bootstrap.Modal('#stockModal').show();
            }
        });
    });
});
</script>
@endpush
