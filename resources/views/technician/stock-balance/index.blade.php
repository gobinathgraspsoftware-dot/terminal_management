@extends('layouts.app')

@section('title', 'My Stock Balance')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">
                <i class="bi bi-boxes me-2"></i>My Stock Balance
            </h1>
            <p class="text-muted mb-0">Current inventory on hand</p>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-white-50 mb-1">Total Models</h6>
                            <h3 class="mb-0">{{ $summary->unique_models ?? 0 }}</h3>
                        </div>
                        <div>
                            <i class="bi bi-box-seam" style="font-size: 2.5rem; opacity: 0.5;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card bg-success text-white shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-white-50 mb-1">Total Quantity</h6>
                            <h3 class="mb-0">{{ number_format($summary->total_quantity ?? 0, 0) }}</h3>
                        </div>
                        <div>
                            <i class="bi bi-boxes" style="font-size: 2.5rem; opacity: 0.5;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card bg-info text-white shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-white-50 mb-1">Reserved</h6>
                            <h3 class="mb-0">{{ number_format($summary->total_reserved ?? 0, 0) }}</h3>
                        </div>
                        <div>
                            <i class="bi bi-lock" style="font-size: 2.5rem; opacity: 0.5;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card bg-dark text-white shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-white-50 mb-1">Available</h6>
                            <h3 class="mb-0">{{ number_format($summary->total_available ?? 0, 0) }}</h3>
                        </div>
                        <div>
                            <i class="bi bi-check-circle" style="font-size: 2.5rem; opacity: 0.5;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Alerts -->
    @if($lowStockCount > 0 || $outOfStockCount > 0)
    <div class="row mb-4">
        @if($outOfStockCount > 0)
        <div class="col-md-6">
            <div class="alert alert-danger">
                <i class="bi bi-x-circle me-2"></i>
                <strong>Out of Stock:</strong> You have {{ $outOfStockCount }} item(s) with no stock available.
            </div>
        </div>
        @endif

        @if($lowStockCount > 0)
        <div class="col-md-6">
            <div class="alert alert-warning">
                <i class="bi bi-exclamation-triangle me-2"></i>
                <strong>Low Stock:</strong> You have {{ $lowStockCount }} item(s) running low on stock.
            </div>
        </div>
        @endif
    </div>
    @endif

    <!-- Filters Card -->
    <div class="card mb-4">
        <div class="card-header bg-light">
            <h5 class="mb-0"><i class="bi bi-funnel me-2"></i>Filters</h5>
        </div>
        <div class="card-body">
            <form id="filterForm">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Category</label>
                        <select class="form-select" name="category_id" id="categoryFilter">
                            <option value="">All Categories</option>
                            @foreach($categories as $category)
                            <option value="{{ $category->id }}">{{ $category->category_name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Model</label>
                        <select class="form-select" name="model_id" id="modelFilter">
                            <option value="">All Models</option>
                            @foreach($models as $model)
                            <option value="{{ $model->id }}">{{ $model->model_name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Stock Status</label>
                        <select class="form-select" name="stock_status" id="stockStatusFilter">
                            <option value="">All Status</option>
                            <option value="low_stock">Low Stock</option>
                            <option value="out_of_stock">Out of Stock</option>
                        </select>
                    </div>

                    <div class="col-12">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-search me-1"></i>Apply Filters
                        </button>
                        <button type="button" class="btn btn-secondary" id="resetFilters">
                            <i class="bi bi-x-circle me-1"></i>Reset
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Data Table Card -->
    <div class="card shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover" id="balanceTable" style="width:100%">
                    <thead>
                        <tr>
                            <th>Model</th>
                            <th>Category</th>
                            <th class="text-end">On Hand</th>
                            <th class="text-end">Reserved</th>
                            <th class="text-end">Available</th>
                            <th class="text-end">Min Level</th>
                            <th>Last Movement</th>
                            <th>Status</th>
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
    // Initialize DataTable
    const table = $('#balanceTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route("technician.stock-balance.index") }}',
            data: function(d) {
                d.category_id = $('#categoryFilter').val();
                d.model_id = $('#modelFilter').val();
                d.stock_status = $('#stockStatusFilter').val();
            }
        },
        columns: [
            {
                data: null,
                render: function(data) {
                    return '<strong>' + data.model_name + '</strong><br>' +
                           '<small class="text-muted">' + data.model_code + '</small>';
                }
            },
            { data: 'category' },
            { data: 'quantity_on_hand', className: 'text-end' },
            { data: 'quantity_reserved', className: 'text-end' },
            {
                data: 'quantity_available',
                className: 'text-end',
                render: function(data, type, row) {
                    let className = 'text-dark';
                    if (row.is_out_of_stock) {
                        className = 'text-danger fw-bold';
                    } else if (row.is_low_stock) {
                        className = 'text-warning fw-bold';
                    }
                    return '<span class="' + className + '">' + data + '</span>';
                }
            },
            { data: 'min_stock_level', className: 'text-end' },
            { data: 'last_movement_date' },
            { data: 'stock_badge', orderable: false }
        ],
        order: [[0, 'asc']],
        pageLength: 25,
        language: {
            emptyTable: "No stock balances found",
            zeroRecords: "No matching stock found"
        }
    });

    // Filter form submission
    $('#filterForm').on('submit', function(e) {
        e.preventDefault();
        table.ajax.reload();
    });

    // Reset filters
    $('#resetFilters').on('click', function() {
        $('#filterForm')[0].reset();
        table.ajax.reload();
    });
});
</script>
@endpush
