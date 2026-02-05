@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">Movement Value Tracking</h1>
            <p class="text-muted mb-0">Track cost of goods issued, installed, and wasted</p>
        </div>
        <div>
            <a href="{{ route('admin.stock-valuation.index') }}" class="btn btn-outline-secondary me-2">
                <i class="bi bi-arrow-left"></i> Back to Summary
            </a>
            <button type="button" class="btn btn-success" onclick="exportMovementValue()">
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
            <form action="{{ route('admin.stock-valuation.movement-value') }}" method="GET" id="filterForm">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Start Date</label>
                        <input type="date" name="start_date" class="form-control" 
                               value="{{ $filters['start_date'] }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">End Date</label>
                        <input type="date" name="end_date" class="form-control" 
                               value="{{ $filters['end_date'] }}">
                    </div>
                    <div class="col-md-2">
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
                    <div class="col-md-2">
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
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-search"></i> Filter
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Period Display -->
    <div class="alert alert-info">
        <i class="bi bi-calendar3"></i> 
        <strong>Period:</strong> 
        {{ \Carbon\Carbon::parse($movementTracking['period']['start_date'])->format('d M Y') }} 
        to 
        {{ \Carbon\Carbon::parse($movementTracking['period']['end_date'])->format('d M Y') }}
    </div>

    <!-- Movement Summary Cards -->
    <div class="row mb-4">
        <!-- GRN In -->
        <div class="col-md-6">
            <div class="card border-success h-100">
                <div class="card-header bg-success text-white">
                    <h6 class="mb-0"><i class="bi bi-arrow-down-circle"></i> Goods Received (GRN)</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-6">
                            <p class="text-muted mb-1">Quantity</p>
                            <h4 class="mb-0">{{ number_format($movementTracking['grn_in']['quantity'], 0) }}</h4>
                        </div>
                        <div class="col-6 text-end">
                            <p class="text-muted mb-1">Value</p>
                            <h4 class="mb-0 text-success">RM {{ number_format($movementTracking['grn_in']['value'], 2) }}</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Total Outbound -->
        <div class="col-md-6">
            <div class="card border-danger h-100">
                <div class="card-header bg-danger text-white">
                    <h6 class="mb-0"><i class="bi bi-arrow-up-circle"></i> Total Outbound Value</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-6">
                            <p class="text-muted mb-1">Total Quantity</p>
                            <h4 class="mb-0">
                                {{ number_format(
                                    $movementTracking['issued']['quantity'] + 
                                    $movementTracking['installed']['quantity'] + 
                                    $movementTracking['wastage']['quantity'] + 
                                    $movementTracking['returned_to_vendor']['quantity'], 
                                    0
                                ) }}
                            </h4>
                        </div>
                        <div class="col-6 text-end">
                            <p class="text-muted mb-1">Total Value</p>
                            <h4 class="mb-0 text-danger">RM {{ number_format($movementTracking['total_out_value'], 2) }}</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Outbound Movement Details -->
    <div class="card mb-4">
        <div class="card-header bg-light">
            <h5 class="card-title mb-0"><i class="bi bi-arrow-up-right"></i> Outbound Movement Breakdown</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Movement Type</th>
                            <th>Description</th>
                            <th class="text-end">Quantity</th>
                            <th class="text-end">Value (MYR)</th>
                            <th class="text-end">% of Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><i class="bi bi-person-badge text-primary"></i> <strong>Issued to Technicians</strong></td>
                            <td>Stock issued to field technicians</td>
                            <td class="text-end">{{ number_format($movementTracking['issued']['quantity'], 0) }}</td>
                            <td class="text-end">{{ number_format($movementTracking['issued']['value'], 2) }}</td>
                            <td class="text-end">
                                @php
                                    $percentage = $movementTracking['total_out_value'] > 0 
                                        ? ($movementTracking['issued']['value'] / $movementTracking['total_out_value']) * 100 
                                        : 0;
                                @endphp
                                <span class="badge bg-primary">{{ number_format($percentage, 1) }}%</span>
                            </td>
                        </tr>
                        <tr>
                            <td><i class="bi bi-geo-alt text-success"></i> <strong>Installed</strong></td>
                            <td>Stock installed at customer sites</td>
                            <td class="text-end">{{ number_format($movementTracking['installed']['quantity'], 0) }}</td>
                            <td class="text-end">{{ number_format($movementTracking['installed']['value'], 2) }}</td>
                            <td class="text-end">
                                @php
                                    $percentage = $movementTracking['total_out_value'] > 0 
                                        ? ($movementTracking['installed']['value'] / $movementTracking['total_out_value']) * 100 
                                        : 0;
                                @endphp
                                <span class="badge bg-success">{{ number_format($percentage, 1) }}%</span>
                            </td>
                        </tr>
                        <tr>
                            <td><i class="bi bi-trash text-warning"></i> <strong>Wastage</strong></td>
                            <td>Damaged or scrapped items</td>
                            <td class="text-end">{{ number_format($movementTracking['wastage']['quantity'], 0) }}</td>
                            <td class="text-end">{{ number_format($movementTracking['wastage']['value'], 2) }}</td>
                            <td class="text-end">
                                @php
                                    $percentage = $movementTracking['total_out_value'] > 0 
                                        ? ($movementTracking['wastage']['value'] / $movementTracking['total_out_value']) * 100 
                                        : 0;
                                @endphp
                                <span class="badge bg-warning text-dark">{{ number_format($percentage, 1) }}%</span>
                            </td>
                        </tr>
                        <tr>
                            <td><i class="bi bi-arrow-return-left text-info"></i> <strong>Returned to Vendor</strong></td>
                            <td>Stock returned to suppliers</td>
                            <td class="text-end">{{ number_format($movementTracking['returned_to_vendor']['quantity'], 0) }}</td>
                            <td class="text-end">{{ number_format($movementTracking['returned_to_vendor']['value'], 2) }}</td>
                            <td class="text-end">
                                @php
                                    $percentage = $movementTracking['total_out_value'] > 0 
                                        ? ($movementTracking['returned_to_vendor']['value'] / $movementTracking['total_out_value']) * 100 
                                        : 0;
                                @endphp
                                <span class="badge bg-info">{{ number_format($percentage, 1) }}%</span>
                            </td>
                        </tr>
                    </tbody>
                    <tfoot class="table-light">
                        <tr>
                            <th colspan="2">TOTAL OUTBOUND</th>
                            <th class="text-end">
                                {{ number_format(
                                    $movementTracking['issued']['quantity'] + 
                                    $movementTracking['installed']['quantity'] + 
                                    $movementTracking['wastage']['quantity'] + 
                                    $movementTracking['returned_to_vendor']['quantity'], 
                                    0
                                ) }}
                            </th>
                            <th class="text-end">{{ number_format($movementTracking['total_out_value'], 2) }}</th>
                            <th class="text-end"><span class="badge bg-dark">100%</span></th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    <!-- Insights Card -->
    <div class="card">
        <div class="card-header bg-light">
            <h5 class="card-title mb-0"><i class="bi bi-lightbulb"></i> Insights</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <h6 class="text-muted">Highest Outbound Type</h6>
                    @php
                        $movements = [
                            'Issued' => $movementTracking['issued']['value'],
                            'Installed' => $movementTracking['installed']['value'],
                            'Wastage' => $movementTracking['wastage']['value'],
                            'Returned' => $movementTracking['returned_to_vendor']['value'],
                        ];
                        arsort($movements);
                        $highest = array_key_first($movements);
                    @endphp
                    <p class="mb-0"><strong>{{ $highest }}</strong> (RM {{ number_format($movements[$highest], 2) }})</p>
                </div>
                <div class="col-md-6">
                    <h6 class="text-muted">Wastage Impact</h6>
                    @php
                        $wastagePercent = $movementTracking['total_out_value'] > 0 
                            ? ($movementTracking['wastage']['value'] / $movementTracking['total_out_value']) * 100 
                            : 0;
                    @endphp
                    <p class="mb-0">
                        <strong>{{ number_format($wastagePercent, 2) }}%</strong> of total outbound value
                        @if($wastagePercent > 5)
                            <span class="badge bg-danger ms-2">High</span>
                        @elseif($wastagePercent > 2)
                            <span class="badge bg-warning text-dark ms-2">Moderate</span>
                        @else
                            <span class="badge bg-success ms-2">Low</span>
                        @endif
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function exportMovementValue() {
    const form = document.getElementById('filterForm');
    const params = new URLSearchParams(new FormData(form)).toString();
    window.location.href = '{{ route("admin.stock-valuation.export-movement-value") }}?' + params;
}
</script>
@endpush
@endsection
