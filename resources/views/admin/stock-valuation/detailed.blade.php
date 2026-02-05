@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">Detailed Stock Valuation</h1>
            <p class="text-muted mb-0">Inventory value by location and model</p>
        </div>
        <div>
            <a href="{{ route('admin.stock-valuation.index') }}" class="btn btn-outline-secondary me-2">
                <i class="bi bi-arrow-left"></i> Back to Summary
            </a>
            <button type="button" class="btn btn-success" onclick="exportDetailed()">
                <i class="bi bi-file-earmark-excel"></i> Export
            </button>
        </div>
    </div>

    <!-- Filters Card -->
    <div class="card mb-4">
        <div class="card-header bg-light">
            <h5 class="card-title mb-0"><i class="bi bi-funnel"></i> Filters</h5>
        </div>
        <div class="card-body">
            <form action="{{ route('admin.stock-valuation.detailed') }}" method="GET" id="filterForm">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Location Type</label>
                        <select name="location_type" class="form-select">
                            <option value="">All Types</option>
                            <option value="depot" {{ request('location_type') == 'depot' ? 'selected' : '' }}>Depot</option>
                            <option value="technician" {{ request('location_type') == 'technician' ? 'selected' : '' }}>Technician</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Depot</label>
                        <select name="depot_id" class="form-select">
                            <option value="">All Depots</option>
                            @foreach($depots as $depot)
                                <option value="{{ $depot->id }}" {{ request('depot_id') == $depot->id ? 'selected' : '' }}>
                                    {{ $depot->depot_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Category</label>
                        <select name="category_id" class="form-select">
                            <option value="">All Categories</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}" {{ request('category_id') == $category->id ? 'selected' : '' }}>
                                    {{ $category->category_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary me-2">
                            <i class="bi bi-search"></i> Filter
                        </button>
                        <a href="{{ route('admin.stock-valuation.detailed') }}" class="btn btn-outline-secondary">
                            <i class="bi bi-x-circle"></i> Clear
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Detailed Valuation Table -->
    <div class="card">
        <div class="card-header bg-light">
            <h5 class="card-title mb-0"><i class="bi bi-table"></i> Detailed Valuation</h5>
        </div>
        <div class="card-body">
            @if($valuation->count() > 0)
                <div class="table-responsive">
                    <table class="table table-striped table-hover" id="valuationTable">
                        <thead>
                            <tr>
                                <th>Location Type</th>
                                <th>Location Name</th>
                                <th>Category</th>
                                <th>Model</th>
                                <th class="text-end">Quantity</th>
                                <th class="text-end">Avg Cost (MYR)</th>
                                <th class="text-end">Total Value (MYR)</th>
                                <th>Last Movement</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php
                                $totalQuantity = 0;
                                $totalValue = 0;
                            @endphp
                            @foreach($valuation as $item)
                                @php
                                    $totalQuantity += $item['quantity_on_hand'];
                                    $totalValue += $item['total_value'];
                                @endphp
                                <tr>
                                    <td>
                                        @if($item['location_type'] == 'Depot')
                                            <span class="badge bg-primary">{{ $item['location_type'] }}</span>
                                        @else
                                            <span class="badge bg-info">{{ $item['location_type'] }}</span>
                                        @endif
                                    </td>
                                    <td><strong>{{ $item['location_name'] }}</strong></td>
                                    <td><span class="badge bg-secondary">{{ $item['category_name'] }}</span></td>
                                    <td>{{ $item['model_name'] }}</td>
                                    <td class="text-end">{{ number_format($item['quantity_on_hand'], 0) }}</td>
                                    <td class="text-end">{{ number_format($item['average_cost'], 2) }}</td>
                                    <td class="text-end"><strong>{{ number_format($item['total_value'], 2) }}</strong></td>
                                    <td>
                                        @if($item['last_movement_date'])
                                            {{ \Carbon\Carbon::parse($item['last_movement_date'])->format('d M Y') }}
                                        @else
                                            <span class="text-muted">N/A</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="table-light">
                            <tr>
                                <th colspan="4">TOTAL</th>
                                <th class="text-end">{{ number_format($totalQuantity, 0) }}</th>
                                <th class="text-end">-</th>
                                <th class="text-end">{{ number_format($totalValue, 2) }}</th>
                                <th></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            @else
                <div class="alert alert-info text-center">
                    <i class="bi bi-info-circle"></i> No stock data found for the selected filters.
                </div>
            @endif
        </div>
    </div>
</div>

@push('scripts')
<script>
$(document).ready(function() {
    // Initialize DataTable
    if ($.fn.DataTable.isDataTable('#valuationTable')) {
        $('#valuationTable').DataTable().destroy();
    }

    $('#valuationTable').DataTable({
        order: [[6, 'desc']], // Sort by total value descending
        pageLength: 25,
        lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
        dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>rtip',
        language: {
            search: "_INPUT_",
            searchPlaceholder: "Search...",
            lengthMenu: "_MENU_ records per page"
        },
        columnDefs: [
            { orderable: false, targets: [2, 7] }
        ]
    });
});

function exportDetailed() {
    const form = document.getElementById('filterForm');
    const params = new URLSearchParams(new FormData(form)).toString();
    window.location.href = '{{ route("admin.stock-valuation.export-detailed") }}?' + params;
}
</script>
@endpush
@endsection
