@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">Stock Aging Report</h1>
            <p class="text-muted mb-0">Analyze how long inventory has been in stock</p>
        </div>
        <div>
            <a href="{{ route('admin.stock-valuation.index') }}" class="btn btn-outline-secondary me-2">
                <i class="bi bi-arrow-left"></i> Back to Summary
            </a>
            <button type="button" class="btn btn-outline-primary" onclick="window.print()">
                <i class="bi bi-printer"></i> Print
            </button>
        </div>
    </div>

    <!-- Filters Card -->
    <div class="card mb-4">
        <div class="card-header bg-light">
            <h5 class="card-title mb-0"><i class="bi bi-funnel"></i> Filters</h5>
        </div>
        <div class="card-body">
            <form action="{{ route('admin.stock-valuation.aging') }}" method="GET" id="filterForm">
                <div class="row g-3">
                    <div class="col-md-4">
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
                    <div class="col-md-4">
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
                    <div class="col-md-4 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary me-2">
                            <i class="bi bi-search"></i> Filter
                        </button>
                        <a href="{{ route('admin.stock-valuation.aging') }}" class="btn btn-outline-secondary">
                            <i class="bi bi-x-circle"></i> Clear
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Aging Summary Cards -->
    <div class="row mb-4">
        @foreach($agingByBracket as $bracket => $data)
            @php
                $badgeColor = match($bracket) {
                    '0-30 days' => 'success',
                    '31-60 days' => 'info',
                    '61-90 days' => 'primary',
                    '91-180 days' => 'warning',
                    '181-365 days' => 'danger',
                    'Over 1 year' => 'dark',
                    default => 'secondary'
                };
            @endphp
            <div class="col-md-4 mb-3">
                <div class="card border-{{ $badgeColor }}">
                    <div class="card-body">
                        <h6 class="text-muted mb-2">{{ $bracket }}</h6>
                        <div class="row">
                            <div class="col-6">
                                <small class="text-muted">Items:</small>
                                <h5 class="mb-0">{{ $data['count'] }}</h5>
                            </div>
                            <div class="col-6 text-end">
                                <small class="text-muted">Value:</small>
                                <h5 class="mb-0 text-{{ $badgeColor }}">
                                    RM {{ number_format($data['value'], 0) }}
                                </h5>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <!-- Detailed Aging Table -->
    <div class="card">
        <div class="card-header bg-light">
            <h5 class="card-title mb-0"><i class="bi bi-table"></i> Detailed Aging Report</h5>
        </div>
        <div class="card-body">
            @if($aging->count() > 0)
                <div class="table-responsive">
                    <table class="table table-striped table-hover" id="agingTable">
                        <thead>
                            <tr>
                                <th>Serial No</th>
                                <th>Model</th>
                                <th>Category</th>
                                <th>Location</th>
                                <th>GRN Date</th>
                                <th class="text-end">Days in Stock</th>
                                <th>Aging Bracket</th>
                                <th class="text-end">Purchase Price (MYR)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($aging as $item)
                                @php
                                    $badgeColor = match($item['aging_bracket']) {
                                        '0-30 days' => 'success',
                                        '31-60 days' => 'info',
                                        '61-90 days' => 'primary',
                                        '91-180 days' => 'warning',
                                        '181-365 days' => 'danger',
                                        'Over 1 year' => 'dark',
                                        default => 'secondary'
                                    };
                                @endphp
                                <tr>
                                    <td><code>{{ $item['serial_no'] }}</code></td>
                                    <td><strong>{{ $item['model_name'] }}</strong></td>
                                    <td><span class="badge bg-secondary">{{ $item['category_name'] }}</span></td>
                                    <td>{{ $item['location_name'] }}</td>
                                    <td>
                                        @if($item['grn_date'])
                                            {{ \Carbon\Carbon::parse($item['grn_date'])->format('d M Y') }}
                                        @else
                                            <span class="text-muted">N/A</span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        <strong>{{ number_format($item['days_in_stock'], 0) }}</strong>
                                    </td>
                                    <td>
                                        <span class="badge bg-{{ $badgeColor }}">
                                            {{ $item['aging_bracket'] }}
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        @if($item['purchase_price'])
                                            {{ number_format($item['purchase_price'], 2) }}
                                        @else
                                            <span class="text-muted">N/A</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
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
    if ($.fn.DataTable.isDataTable('#agingTable')) {
        $('#agingTable').DataTable().destroy();
    }

    $('#agingTable').DataTable({
        order: [[5, 'desc']], // Sort by days in stock descending
        pageLength: 25,
        lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
        dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>rtip',
        language: {
            search: "_INPUT_",
            searchPlaceholder: "Search serial, model...",
            lengthMenu: "_MENU_ records per page"
        },
        columnDefs: [
            { orderable: false, targets: [2, 3, 6] }
        ]
    });
});
</script>
@endpush
@endsection
