@extends('layouts.app')

@section('title', 'Stock Balance')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">
                <i class="bi bi-boxes me-2"></i>Stock Balance Dashboard
            </h1>
            <p class="text-muted mb-0">Real-time inventory balances across all locations</p>
        </div>
        <div>
            @can('view_stock_alerts')
            <a href="{{ route('admin.stock-balance.alerts') }}" class="btn btn-warning">
                <i class="bi bi-exclamation-triangle me-1"></i>View Alerts
                @if($lowStockCount > 0)
                <span class="badge bg-danger">{{ $lowStockCount }}</span>
                @endif
            </a>
            @endcan

            @can('recalculate_stock_balance')
            <button type="button" class="btn btn-info" id="recalculateBtn">
                <i class="bi bi-arrow-repeat me-1"></i>Recalculate Balances
            </button>
            @endcan

            @can('export_stock_balance')
            <button type="button" class="btn btn-success" id="exportBtn">
                <i class="bi bi-file-earmark-excel me-1"></i>Export
            </button>
            @endcan
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
                            <h3 class="mb-0">{{ $summary->sum('unique_models') }}</h3>
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
                            <h6 class="text-white-50 mb-1">Total Stock</h6>
                            <h3 class="mb-0">{{ number_format($summary->sum('total_quantity'), 0) }}</h3>
                        </div>
                        <div>
                            <i class="bi bi-boxes" style="font-size: 2.5rem; opacity: 0.5;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card bg-warning text-dark shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-1" style="opacity: 0.7;">Low Stock Items</h6>
                            <h3 class="mb-0">{{ $lowStockCount }}</h3>
                        </div>
                        <div>
                            <i class="bi bi-exclamation-triangle" style="font-size: 2.5rem; opacity: 0.5;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card bg-danger text-white shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-white-50 mb-1">Out of Stock</h6>
                            <h3 class="mb-0">{{ $outOfStockCount }}</h3>
                        </div>
                        <div>
                            <i class="bi bi-x-circle" style="font-size: 2.5rem; opacity: 0.5;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Location Summary -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-header bg-light">
                    <h5 class="mb-0"><i class="bi bi-building me-2"></i>Depot Summary</h5>
                </div>
                <div class="card-body">
                    @if($depotSummary && $depotSummary->count() > 0)
                    <table class="table table-sm mb-0">
                        <tbody>
                            @foreach($depotSummary as $summary)
                            <tr>
                                <td><strong>Total Quantity:</strong></td>
                                <td class="text-end">{{ number_format($summary->total_quantity, 0) }}</td>
                            </tr>
                            <tr>
                                <td><strong>Reserved:</strong></td>
                                <td class="text-end">{{ number_format($summary->total_reserved, 0) }}</td>
                            </tr>
                            <tr>
                                <td><strong>Available:</strong></td>
                                <td class="text-end"><strong>{{ number_format($summary->total_available, 0) }}</strong></td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                    @else
                    <p class="text-muted mb-0">No depot stock available</p>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-header bg-light">
                    <h5 class="mb-0"><i class="bi bi-person me-2"></i>Technician Summary</h5>
                </div>
                <div class="card-body">
                    @if($technicianSummary && $technicianSummary->count() > 0)
                    <table class="table table-sm mb-0">
                        <tbody>
                            @foreach($technicianSummary as $summary)
                            <tr>
                                <td><strong>Total Quantity:</strong></td>
                                <td class="text-end">{{ number_format($summary->total_quantity, 0) }}</td>
                            </tr>
                            <tr>
                                <td><strong>Reserved:</strong></td>
                                <td class="text-end">{{ number_format($summary->total_reserved, 0) }}</td>
                            </tr>
                            <tr>
                                <td><strong>Available:</strong></td>
                                <td class="text-end"><strong>{{ number_format($summary->total_available, 0) }}</strong></td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                    @else
                    <p class="text-muted mb-0">No technician stock available</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Filters Card -->
    <div class="card mb-4">
        <div class="card-header bg-light">
            <h5 class="mb-0"><i class="bi bi-funnel me-2"></i>Filters</h5>
        </div>
        <div class="card-body">
            <form id="filterForm">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Category</label>
                        <select class="form-select" name="category_id" id="categoryFilter">
                            <option value="">All Categories</option>
                            @foreach($categories as $category)
                            <option value="{{ $category->id }}">{{ $category->category_name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Model</label>
                        <select class="form-select" name="model_id" id="modelFilter">
                            <option value="">All Models</option>
                            @foreach($models as $model)
                            <option value="{{ $model->id }}">{{ $model->model_name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Location Type</label>
                        <select class="form-select" name="location_type" id="locationTypeFilter">
                            <option value="">All Locations</option>
                            <option value="depot">Depot</option>
                            <option value="technician">Technician</option>
                        </select>
                    </div>

                    <div class="col-md-3">
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
                            <th>Location</th>
                            <th>On Hand</th>
                            <th>Reserved</th>
                            <th>Available</th>
                            <th>Min Level</th>
                            <th>Status</th>
                            <th>Last Movement</th>
                            <th>Actions</th>
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
            url: '{{ route("admin.stock-balance.index") }}',
            data: function(d) {
                d.category_id = $('#categoryFilter').val();
                d.model_id = $('#modelFilter').val();
                d.location_type = $('#locationTypeFilter').val();
                d.stock_status = $('#stockStatusFilter').val();
            }
        },
        columns: [
            { data: 'model_name' },
            { data: 'category' },
            { data: 'location_name' },
            { data: 'quantity_on_hand', className: 'text-end' },
            { data: 'quantity_reserved', className: 'text-end' },
            { data: 'quantity_available', className: 'text-end' },
            { data: 'min_stock_level', className: 'text-end' },
            { data: 'stock_badge', orderable: false },
            { data: 'last_movement_date' },
            {
                data: null,
                orderable: false,
                render: function(data) {
                    return '<a href="/admin/stock-balance/' + data.location_type + '/' + data.location_id + '" class="btn btn-sm btn-info">' +
                           '<i class="bi bi-eye"></i></a>';
                }
            }
        ],
        order: [[0, 'asc']],
        pageLength: 25
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

    // Recalculate balances
    $('#recalculateBtn').on('click', function() {
        Swal.fire({
            title: 'Recalculate Balances?',
            text: 'This will recalculate all stock balances from the ledger.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, recalculate!',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (!result.isConfirmed) {
                return;
            }

            const btn = $('#recalculateBtn');
            btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>Recalculating...');

            $.ajax({
                url: '{{ route("admin.stock-balance.recalculate") }}',
                method: 'POST',
                data: { _token: '{{ csrf_token() }}' },
                success: function(response) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: 'Balances recalculated successfully',
                        timer: 2000,
                        showConfirmButton: false
                    });
                    table.ajax.reload();
                },
                error: function(xhr) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error!',
                        text: xhr.responseJSON?.message || 'Recalculation failed'
                    });
                },
                complete: function() {
                    btn.prop('disabled', false).html('<i class="bi bi-arrow-repeat me-1"></i>Recalculate Balances');
                }
            });
        });
    });

    // Export button
    $('#exportBtn').on('click', function() {
        const filters = $('#filterForm').serialize();
        window.location.href = '{{ route("admin.stock-balance.export") }}?' + filters;
    });
});
</script>
@endpush
