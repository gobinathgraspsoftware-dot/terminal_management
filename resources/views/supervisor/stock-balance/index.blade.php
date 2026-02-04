@extends('layouts.app')

@section('title', 'Stock Balance - Team View')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">
                <i class="bi bi-boxes me-2"></i>Stock Balance - My Team
            </h1>
            <p class="text-muted mb-0">Team technician inventory balances</p>
        </div>
    </div>

    <!-- Summary Card -->
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card bg-primary text-white shadow-sm">
                <div class="card-body">
                    <h5>Team Inventory Summary</h5>
                    <div class="row">
                        <div class="col-md-3">
                            <h6 class="text-white-50">Total Quantity</h6>
                            <h3>{{ number_format($technicianSummary->total_quantity ?? 0, 0) }}</h3>
                        </div>
                        <div class="col-md-3">
                            <h6 class="text-white-50">Reserved</h6>
                            <h3>{{ number_format($technicianSummary->total_reserved ?? 0, 0) }}</h3>
                        </div>
                        <div class="col-md-3">
                            <h6 class="text-white-50">Available</h6>
                            <h3>{{ number_format($technicianSummary->total_available ?? 0, 0) }}</h3>
                        </div>
                        <div class="col-md-3">
                            <h6 class="text-white-50">Low Stock Items</h6>
                            <h3>{{ $lowStockCount }}</h3>
                        </div>
                    </div>
                </div>
            </div>
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
                            <th>Technician</th>
                            <th>On Hand</th>
                            <th>Reserved</th>
                            <th>Available</th>
                            <th>Status</th>
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
    const table = $('#balanceTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: '{{ route("supervisor.stock-balance.index") }}',
        columns: [
            { data: 'model_name' },
            { data: 'technician_name' },
            { data: 'quantity_on_hand', className: 'text-end' },
            { data: 'quantity_reserved', className: 'text-end' },
            { data: 'quantity_available', className: 'text-end' },
            { data: 'stock_badge', orderable: false },
            {
                data: null,
                orderable: false,
                render: function(data) {
                    return '<a href="/supervisor/stock-balance/technician/' + data.location_id + '" class="btn btn-sm btn-info">' +
                           '<i class="bi bi-eye"></i></a>';
                }
            }
        ],
        order: [[0, 'asc']]
    });
});
</script>
@endpush
