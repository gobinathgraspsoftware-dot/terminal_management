@extends('layouts.app')
@section('title', 'Inventory Management')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1">Inventory Management</h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item active">Inventory</li>
                </ol>
            </nav>
        </div>
        <div class="d-flex gap-2">
            @can('export_inventory')
            <a href="{{ route('admin.inventory.export') }}" class="btn btn-outline-success">
                <i class="bi bi-download me-1"></i> Export
            </a>
            @endcan
            @can('create', \App\Models\InventoryItem::class)
            <a href="{{ route('admin.inventory.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-circle me-1"></i> Add Item
            </a>
            @endcan
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="row g-3 mb-4">
        <div class="col-md-2">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center">
                    <div class="fs-3 fw-bold text-primary">{{ $stats['total'] }}</div>
                    <small class="text-muted">Total Items</small>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center">
                    <div class="fs-3 fw-bold text-info">{{ $stats['routers'] }}</div>
                    <small class="text-muted">Routers</small>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center">
                    <div class="fs-3 fw-bold text-success">{{ $stats['accessories'] }}</div>
                    <small class="text-muted">Accessories</small>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center">
                    <div class="fs-3 fw-bold text-success">{{ $stats['active'] }}</div>
                    <small class="text-muted">Active</small>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center">
                    <div class="fs-3 fw-bold text-danger">{{ $stats['low_stock'] }}</div>
                    <small class="text-muted">Low Stock</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Item Type</label>
                    <select id="filter-type" class="form-select">
                        <option value="">All Types</option>
                        <option value="router">Router</option>
                        <option value="accessory">Accessory</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Category</label>
                    <select id="filter-category" class="form-select">
                        <option value="">All Categories</option>
                        @foreach($categories as $cat)
                        <option value="{{ $cat->id }}">{{ $cat->category_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Status</label>
                    <select id="filter-status" class="form-select">
                        <option value="">All</option>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button type="button" id="btn-filter" class="btn btn-primary me-2">
                        <i class="bi bi-funnel me-1"></i> Filter
                    </button>
                    <button type="button" id="btn-reset" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-counterclockwise me-1"></i> Reset
                    </button>
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
                            <th>Terminal ID</th>
                            <th>Warehouse</th>
                            <th>Total</th>
                            <th>Stock</th>
                            <th>Status</th>
                            <th width="12%">Actions</th>
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

    var table = $('#inventory-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route("admin.inventory.datatable") }}',
            data: function(d) {
                d.item_type       = $('#filter-type').val();
                d.job_category_id = $('#filter-category').val();
                d.status          = $('#filter-status').val();
            }
        },
        columns: [
            { data: null, orderable: false, searchable: false, render: function() { return ++rowCounter; } },
            { data: 'item_code', name: 'item_code' },
            { data: 'item_name', name: 'item_name' },
            { data: 'category_name', name: 'category_name', orderable: false },
            { data: 'type_badge', name: 'item_type' },
            { data: 'serial_number', name: 'serial_number', defaultContent: '-' },
            { data: 'warehouse_qty', name: 'warehouse_qty', orderable: false, className: 'text-center' },
            { data: 'total_qty', name: 'total_qty', orderable: false, className: 'text-center' },
            { data: 'stock_status', name: 'stock_status', orderable: false, className: 'text-center' },
            { data: 'status_badge', name: 'status' },
            { data: 'action', name: 'action', orderable: false, searchable: false },
        ],
        order: [[1, 'asc']],
        drawCallback: function() { rowCounter = this.api().page() * this.api().page.len(); }
    });

    // Filter
    $('#btn-filter').on('click', function() {
        rowCounter = 0;
        table.draw();
    });

    $('#btn-reset').on('click', function() {
        $('#filter-type, #filter-category, #filter-status').val('');
        rowCounter = 0;
        table.draw();
    });

    // Toggle status
    $(document).on('click', '.btn-toggle-status', function() {
        let id = $(this).data('id');
        let current = $(this).data('status');
        let newStatus = current === 'active' ? 'inactive' : 'active';

        Swal.fire({
            title: 'Toggle Status?',
            text: 'Change status to ' + newStatus + '?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Yes, change it',
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '{{ url("admin/inventory") }}/' + id + '/toggle-status',
                    type: 'PATCH',
                    headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                    success: function(res) {
                        if (res.success) {
                            showToast('success', res.message);
                            table.draw(false);
                        }
                    },
                    error: function(xhr) {
                        showToast('error', xhr.responseJSON?.message || 'Failed to update status.');
                    }
                });
            }
        });
    });

    // Delete
    $(document).on('click', '.btn-delete', function() {
        let id = $(this).data('id');
        let name = $(this).data('name');

        Swal.fire({
            title: 'Delete Item?',
            text: 'Delete "' + name + '"? This cannot be undone.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            confirmButtonText: 'Yes, delete',
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '{{ url("admin/inventory") }}/' + id,
                    type: 'DELETE',
                    headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                    success: function(res) {
                        if (res.success) {
                            showToast('success', res.message);
                            table.draw(false);
                        }
                    },
                    error: function(xhr) {
                        showToast('error', xhr.responseJSON?.message || 'Failed to delete.');
                    }
                });
            }
        });
    });
});
</script>
@endpush
