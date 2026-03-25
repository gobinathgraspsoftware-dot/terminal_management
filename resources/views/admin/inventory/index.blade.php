@extends('layouts.app')
@section('title', 'Inventory Items')

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1">Inventory Items</h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item active">Inventory</li>
                </ol>
            </nav>
        </div>
        <div>
            @can('export_inventory')
            <a href="{{ route('admin.inventory.export') }}" class="btn btn-outline-success btn-sm me-2">
                <i class="bi bi-file-earmark-excel me-1"></i> Export
            </a>
            @endcan
            @can('create_inventory')
            <a href="{{ route('admin.inventory.create') }}" class="btn btn-primary btn-sm">
                <i class="bi bi-plus-circle me-1"></i> Add Item
            </a>
            @endcan
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="row g-3 mb-4">
        <div class="col-md-2">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <div class="text-muted small">Total Items</div>
                    <div class="fs-4 fw-bold text-primary">{{ $stats['total_items'] ?? 0 }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <div class="text-muted small">Active</div>
                    <div class="fs-4 fw-bold text-success">{{ $stats['active_items'] ?? 0 }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <div class="text-muted small">Routers</div>
                    <div class="fs-4 fw-bold text-info">{{ $stats['total_routers'] ?? 0 }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <div class="text-muted small">Accessories</div>
                    <div class="fs-4 fw-bold text-warning">{{ $stats['total_accessories'] ?? 0 }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <div class="text-muted small">Low Stock</div>
                    <div class="fs-4 fw-bold text-danger">{{ $stats['low_stock_count'] ?? 0 }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <div class="text-muted small">Warehouse Stock</div>
                    <div class="fs-4 fw-bold text-secondary">{{ $stats['total_warehouse_stock'] ?? 0 }}</div>
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
                    <select id="filter-item-type" class="form-select form-select-sm">
                        <option value="">All Types</option>
                        <option value="router">Router</option>
                        <option value="accessory">Accessory</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Accessory Type</label>
                    <select id="filter-accessory-type" class="form-select form-select-sm">
                        <option value="">All</option>
                        <option value="sim_card">SIM Card</option>
                        <option value="antenna">Antenna</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Status</label>
                    <select id="filter-status" class="form-select form-select-sm">
                        <option value="">All</option>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button id="btn-reset-filters" class="btn btn-outline-secondary btn-sm w-100">
                        <i class="bi bi-x-circle me-1"></i> Reset Filters
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- DataTable -->
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table id="inventory-table" class="table table-hover table-sm align-middle" style="width:100%">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Item Code</th>
                            <th>Item Name</th>
                            <th>Type</th>
                            <th>Brand</th>
                            <th>Warehouse</th>
                            <th>Status</th>
                            <th>Created</th>
                            <th class="text-center">Actions</th>
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
    var table = $('#inventory-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route("admin.inventory.datatable") }}',
            data: function(d) {
                d.item_type = $('#filter-item-type').val();
                d.accessory_type = $('#filter-accessory-type').val();
                d.status = $('#filter-status').val();
            }
        },
        columns: [
            { data: 'DT_RowIndex', orderable: false, searchable: false, width: '40px' },
            { data: 'item_code' },
            { data: 'item_name' },
            { data: 'item_type', orderable: false },
            { data: 'brand' },
            {
                data: 'warehouse_stock',
                render: function(data, type, row) {
                    var cls = row.is_low_stock ? 'text-danger fw-bold' : '';
                    var icon = row.is_low_stock ? ' <i class="bi bi-exclamation-triangle-fill text-danger"></i>' : '';
                    return '<span class="' + cls + '">' + data + icon + '</span>';
                }
            },
            { data: 'status', orderable: false },
            { data: 'created_at' },
            {
                data: 'id',
                orderable: false,
                searchable: false,
                className: 'text-center',
                render: function(data, type, row) {
                    var html = '<div class="btn-group btn-group-sm">';
                    html += '<a href="{{ url("admin/inventory") }}/' + data + '" class="btn btn-outline-info btn-sm" title="View"><i class="bi bi-eye"></i></a>';
                    @can('edit_inventory')
                    html += '<a href="{{ url("admin/inventory") }}/' + data + '/edit" class="btn btn-outline-primary btn-sm" title="Edit"><i class="bi bi-pencil"></i></a>';
                    @endcan
                    @can('delete_inventory')
                    html += '<button type="button" class="btn btn-outline-danger btn-sm btn-delete" data-id="' + data + '" title="Delete"><i class="bi bi-trash"></i></button>';
                    @endcan
                    html += '</div>';
                    return html;
                }
            }
        ],
        order: [[7, 'desc']],
        pageLength: 25,
        language: { search: '', searchPlaceholder: 'Search...' },
        dom: "<'row'<'col-sm-12 col-md-6'l><'col-sm-12 col-md-6'f>>" +
             "<'row'<'col-sm-12'tr>>" +
             "<'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>"
    });

    // Filter events
    $('#filter-item-type, #filter-accessory-type, #filter-status').on('change', function() {
        table.ajax.reload();
    });

    $('#btn-reset-filters').on('click', function() {
        $('#filter-item-type, #filter-accessory-type, #filter-status').val('');
        table.ajax.reload();
    });

    // Delete
    $(document).on('click', '.btn-delete', function() {
        var id = $(this).data('id');
        Swal.fire({
            title: 'Delete Item?',
            text: 'This action cannot be undone.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            confirmButtonText: 'Yes, Delete'
        }).then(function(result) {
            if (result.isConfirmed) {
                $.ajax({
                    url: '{{ url("admin/inventory") }}/' + id,
                    type: 'DELETE',
                    headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                    success: function(res) {
                        if (res.success) {
                            showToast('success', res.message);
                            table.ajax.reload(null, false);
                        } else {
                            showToast('error', res.message);
                        }
                    },
                    error: function(xhr) {
                        showToast('error', xhr.responseJSON?.message || 'Delete failed.');
                    }
                });
            }
        });
    });
});
</script>
@endpush
