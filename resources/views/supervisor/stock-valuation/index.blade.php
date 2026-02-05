@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">Stock Valuation Summary</h1>
            <p class="text-muted mb-0">Overview of inventory value in your regions</p>
        </div>
        <div>
            <button type="button" class="btn btn-outline-primary me-2" onclick="window.print()">
                <i class="bi bi-printer"></i> Print
            </button>
            <button type="button" class="btn btn-success" onclick="exportSummary()">
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
            <form action="{{ route('supervisor.stock-valuation.index') }}" method="GET" id="filterForm">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Depot</label>
                        <select name="depot_id" class="form-select">
                            <option value="">All Depots</option>
                            @foreach($accessibleDepots as $depot)
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
                        <a href="{{ route('supervisor.stock-valuation.index') }}" class="btn btn-outline-secondary">
                            <i class="bi bi-x-circle"></i> Clear
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card border-primary">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-2">Total Inventory Value</h6>
                            <h2 class="mb-0 text-primary">RM {{ number_format($summary['total_value'], 2) }}</h2>
                        </div>
                        <div class="text-primary">
                            <i class="bi bi-cash-stack" style="font-size: 3rem;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card border-info">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-2">Total Quantity</h6>
                            <h2 class="mb-0 text-info">{{ number_format($summary['total_quantity'], 0) }} units</h2>
                        </div>
                        <div class="text-info">
                            <i class="bi bi-boxes" style="font-size: 3rem;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Valuation by Depot -->
    <div class="card mb-4">
        <div class="card-header bg-light">
            <h5 class="card-title mb-0"><i class="bi bi-building"></i> Valuation by Depot</h5>
        </div>
        <div class="card-body">
            @if(count($summary['by_depot']) > 0)
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Depot</th>
                                <th class="text-end">Quantity</th>
                                <th class="text-end">Total Value (MYR)</th>
                                <th class="text-end">% of Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($summary['by_depot'] as $depot)
                                <tr>
                                    <td><strong>{{ $depot['depot_name'] }}</strong></td>
                                    <td class="text-end">{{ number_format($depot['quantity'], 0) }}</td>
                                    <td class="text-end">{{ number_format($depot['value'], 2) }}</td>
                                    <td class="text-end">
                                        <span class="badge bg-info">
                                            {{ $summary['total_value'] > 0 ? number_format(($depot['value'] / $summary['total_value']) * 100, 1) : 0 }}%
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="table-light">
                            <tr>
                                <th>TOTAL</th>
                                <th class="text-end">{{ number_format($summary['total_quantity'], 0) }}</th>
                                <th class="text-end">{{ number_format($summary['total_value'], 2) }}</th>
                                <th class="text-end"><span class="badge bg-success">100%</span></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            @else
                <p class="text-muted text-center py-4">No depot data available</p>
            @endif
        </div>
    </div>

    <!-- Valuation by Category -->
    <div class="card mb-4">
        <div class="card-header bg-light">
            <h5 class="card-title mb-0"><i class="bi bi-tags"></i> Valuation by Category</h5>
        </div>
        <div class="card-body">
            @if(count($summary['by_category']) > 0)
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Category</th>
                                <th class="text-end">Quantity</th>
                                <th class="text-end">Total Value (MYR)</th>
                                <th class="text-end">% of Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($summary['by_category'] as $category)
                                <tr>
                                    <td><strong>{{ $category['category_name'] }}</strong></td>
                                    <td class="text-end">{{ number_format($category['quantity'], 0) }}</td>
                                    <td class="text-end">{{ number_format($category['value'], 2) }}</td>
                                    <td class="text-end">
                                        <span class="badge bg-info">
                                            {{ $summary['total_value'] > 0 ? number_format(($category['value'] / $summary['total_value']) * 100, 1) : 0 }}%
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="table-light">
                            <tr>
                                <th>TOTAL</th>
                                <th class="text-end">{{ number_format($summary['total_quantity'], 0) }}</th>
                                <th class="text-end">{{ number_format($summary['total_value'], 2) }}</th>
                                <th class="text-end"><span class="badge bg-success">100%</span></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            @else
                <p class="text-muted text-center py-4">No category data available</p>
            @endif
        </div>
    </div>

    <!-- Top Valued Models -->
    <div class="card mb-4">
        <div class="card-header bg-light">
            <h5 class="card-title mb-0"><i class="bi bi-trophy"></i> Top 10 Valued Models</h5>
        </div>
        <div class="card-body">
            @if($topModels->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Model</th>
                                <th>Category</th>
                                <th class="text-end">Quantity</th>
                                <th class="text-end">Avg Cost (MYR)</th>
                                <th class="text-end">Total Value (MYR)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($topModels as $index => $model)
                                <tr>
                                    <td>
                                        @if($index < 3)
                                            <span class="badge bg-warning text-dark">{{ $index + 1 }}</span>
                                        @else
                                            {{ $index + 1 }}
                                        @endif
                                    </td>
                                    <td><strong>{{ $model['model_name'] }}</strong></td>
                                    <td><span class="badge bg-secondary">{{ $model['category_name'] }}</span></td>
                                    <td class="text-end">{{ number_format($model['quantity'], 0) }}</td>
                                    <td class="text-end">{{ number_format($model['avg_cost'], 2) }}</td>
                                    <td class="text-end"><strong>{{ number_format($model['value'], 2) }}</strong></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <p class="text-muted text-center py-4">No model data available</p>
            @endif
        </div>
    </div>

    <!-- Quick Links -->
    <div class="card">
        <div class="card-header bg-light">
            <h5 class="card-title mb-0"><i class="bi bi-link-45deg"></i> Related Reports</h5>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <a href="{{ route('supervisor.stock-valuation.detailed') }}" class="btn btn-outline-primary w-100">
                        <i class="bi bi-list-ul"></i> Detailed Valuation by Location
                    </a>
                </div>
                <div class="col-md-6">
                    <a href="{{ route('supervisor.stock-balance.index') }}" class="btn btn-outline-info w-100">
                        <i class="bi bi-boxes"></i> Stock Balance Report
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function exportSummary() {
    const form = document.getElementById('filterForm');
    const params = new URLSearchParams(new FormData(form)).toString();
    window.location.href = '{{ route("supervisor.stock-valuation.export-summary") }}?' + params;
}
</script>
@endpush
@endsection
