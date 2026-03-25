@extends('layouts.app')
@section('title', 'Inventory Management')

@section('content')
<div class="container-fluid">
    {{-- Page Header --}}
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
        <div>
            @can('export_inventory')
            <a href="{{ route('admin.inventory.export') }}" class="btn btn-outline-success btn-sm me-2">
                <i class="bi bi-download me-1"></i> Export
            </a>
            @endcan
            @can('create_inventory')
            <a href="{{ route('admin.inventory.create') }}" class="btn btn-primary btn-sm">
                <i class="bi bi-plus-lg me-1"></i> Add Item
            </a>
            @endcan
        </div>
    </div>

    {{-- Summary Cards --}}
    <div class="row g-3 mb-4">
        <div class="col-md-2">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center">
                    <div class="text-primary fs-4 fw-bold">{{ $stats['total_items'] }}</div>
                    <small class="text-muted">Total Items</small>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center">
                    <div class="text-success fs-4 fw-bold">{{ $stats['active_items'] }}</div>
                    <small class="text-muted">Active Items</small>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center">
                    <div class="text-info fs-4 fw-bold">{{ $stats['total_routers'] }}</div>
                    <small class="text-muted">Routers</small>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center">
                    <div class="text-secondary fs-4 fw-bold">{{ $stats['total_accessories'] }}</div>
                    <small class="text-muted">Accessories</small>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center">
                    <div class="text-danger fs-4 fw-bold">{{ $stats['low_stock_count'] }}</div>
                    <small class="text-muted">Low Stock</small>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center">
                    <div class="text-warning fs-4 fw-bold">{{ $stats['today_movements'] }}</div>
                    <small class="text-muted">Today's Movements</small>
                </div>
            </div>
        </div>
    </div>

    {{-- Filters --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <div class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Item Type</label>
                    <select id="filter_item_type" class="form-select form-select-sm">
                        <option value="">All Types</option>
                        <option value="router">Router</option>
                        <option value="accessory">Accessory</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Category</label>
                    <select id="filter_category" class="form-select form-select-sm">
                        <option value="">All Categories</option>
                        @foreach($jobCategories as $cat)
                            <option value="{{ $cat->id }}">{{ $cat->category_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Status</label>
                    <select id="filter_status" class="form-select form-select-sm">
                        <option value="">All Status</option>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
                <div class="col-md-3 text-end">
                    <button type="button" id="btn_reset_filters" class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-x-circle me-1"></i> Reset
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- DataTable --}}
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table id="inventoryTable" class="table table-hover table-sm align-middle" style="width:100%">
                    <thead class="table-light">
                        <tr>
                            <th width="50">#</th>
                            <th>Item Code</th>
                            <th>Item Name</th>
                            <th>Type</th>
                            <th>Terminal ID</th>
                            <th>Warehouse Stock</th>
                            <th>Total Stock</th>
                            <th>Status</th>
                            <th>Created</th>
                            <th width="120">Actions</th>
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
$(document).ready(function() {
    var table = $('#inventoryTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route("admin.inventory.datatable") }}',
            data: function(d) {
                d.item_type = $('#filter_item_type').val();
                d.job_category_id = $('#filter_category').val();
                d.status = $('#filter_status').val();
            }
        },
        columns: [
            { data: 'DT_RowIndex', orderable: false, searchable: false },
            { data: 'item_code' },
            { data: 'item_name' },
            { data: 'item_type', orderable: false },
            { data: 'serial_number' },
            {
                data: 'warehouse_stock',
                render: function(data, type, row) {
                    var cls = row.is_low_stock ? 'text-danger fw-bold' : '';
                    var icon = row.is_low_stock ? ' <i class="bi bi-exclamation-triangle text-danger"></i>' : '';
                    return '<span class="' + cls + '">' + data + icon + '</span>';
                }
            },
            { data: 'total_stock' },
            { data: 'status', orderable: false },
            { data: 'created_at' },
            {
                data: 'id',
                orderable: false,
                render: function(data, type, row) {
                    var showUrl = '{{ route("admin.inventory.show", ":id") }}'.replace(':id', data);
                    var editUrl = '{{ route("admin.inventory.edit", ":id") }}'.replace(':id', data);
                    var html = '<div class="btn-group btn-group-sm">';
                    html += '<a href="' + showUrl + '" class="btn btn-outline-primary" title="View"><i class="bi bi-eye"></i></a>';
                    @can('edit_inventory')
                    html += '<a href="' + editUrl + '" class="btn btn-outline-warning" title="Edit"><i class="bi bi-pencil"></i></a>';
                    @endcan
                    @can('delete_inventory')
                    html += '<button type="button" class="btn btn-outline-danger btn-delete" data-id="' + data + '" title="Delete"><i class="bi bi-trash"></i></button>';
                    @endcan
                    html += '</div>';
                    return html;
                }
            }
        ],
        order: [[8, 'desc']],
        pageLength: 25,
        language: {
            processing: '<div class="spinner-border spinner-border-sm text-primary" role="status"><span class="visually-hidden">Loading...</span></div> Loading...',
            emptyTable: 'No inventory items found.',
        }
    });

    // Filters
    $('#filter_item_type, #filter_category, #filter_status').on('change', function() {
        table.ajax.reload();
    });

    $('#btn_reset_filters').on('click', function() {
        $('#filter_item_type, #filter_category, #filter_status').val('');
        table.ajax.reload();
    });

    // Delete
    $(document).on('click', '.btn-delete', function() {
        var id = $(this).data('id');
        Swal.fire({
            title: 'Delete this item?',
            text: 'This action cannot be undone.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            confirmButtonText: 'Yes, delete it'
        }).then(function(result) {
            if (result.isConfirmed) {
                $.ajax({
                    url: '{{ route("admin.inventory.destroy", ":id") }}'.replace(':id', id),
                    type: 'DELETE',
                    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                    success: function(res) {
                        if (res.success) {
                            showToast('success', res.message);
                            table.ajax.reload(null, false);
                        } else {
                            showToast('error', res.message);
                        }
                    },
                    error: function(xhr) {
                        var msg = xhr.responseJSON?.message || 'Delete failed.';
                        showToast('error', msg);
                    }
                });
            }
        });
    });
});
</script>
@endpush
