@extends('layouts.app')

@section('title', 'Stock Summary Report')

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h3 mb-1">Stock Summary Report</h1>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-0">
                            <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('admin.stock-reports.index') }}">Stock Reports</a></li>
                            <li class="breadcrumb-item active">Summary</li>
                        </ol>
                    </nav>
                </div>
                <div>
                    <button type="button" class="btn btn-success" onclick="exportSummary()">
                        <i class="bi bi-file-earmark-excel me-2"></i>Export to Excel
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-light">
            <h5 class="mb-0"><i class="bi bi-funnel me-2"></i>Filters</h5>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('admin.stock-reports.summary') }}" id="filterForm">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">From Date</label>
                        <input type="date" name="from_date" class="form-control" value="{{ $filters['from_date'] ?? '' }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">To Date</label>
                        <input type="date" name="to_date" class="form-control" value="{{ $filters['to_date'] ?? '' }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Category</label>
                        <select name="category_id" class="form-select">
                            <option value="">All Categories</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}" {{ ($filters['category_id'] ?? '') == $category->id ? 'selected' : '' }}>
                                    {{ $category->category_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-search me-2"></i>Filter
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Overall Summary -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card border-start border-primary border-4">
                <div class="card-body">
                    <h6 class="text-muted mb-1">Total Movements</h6>
                    <h3 class="mb-0">{{ number_format($summary['total_movements']) }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-start border-success border-4">
                <div class="card-body">
                    <h6 class="text-muted mb-1">Total Quantity In</h6>
                    <h3 class="mb-0">{{ number_format($summary['total_in']) }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-start border-danger border-4">
                <div class="card-body">
                    <h6 class="text-muted mb-1">Total Quantity Out</h6>
                    <h3 class="mb-0">{{ number_format(abs($summary['total_out'])) }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-start border-info border-4">
                <div class="card-body">
                    <h6 class="text-muted mb-1">Net Movement</h6>
                    <h3 class="mb-0">{{ number_format($summary['total_in'] + $summary['total_out']) }}</h3>
                </div>
            </div>
        </div>
    </div>

    <!-- Movements by Type -->
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-light">
            <h5 class="mb-0"><i class="bi bi-pie-chart me-2"></i>Movements by Transaction Type</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>Transaction Type</th>
                            <th class="text-end">Count</th>
                            <th class="text-end">Percentage</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $totalCount = array_sum($summary['by_type']); @endphp
                        @foreach($summary['by_type'] as $type => $count)
                            <tr>
                                <td>{!! \App\Models\StockLedger::TYPE_OPTIONS[$type] ?? $type !!}</td>
                                <td class="text-end">{{ number_format($count) }}</td>
                                <td class="text-end">
                                    @if($totalCount > 0)
                                        {{ number_format(($count / $totalCount) * 100, 1) }}%
                                    @else
                                        0%
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Top 10 Movers -->
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-light">
            <h5 class="mb-0"><i class="bi bi-graph-up me-2"></i>Top 10 Moving Models</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>Rank</th>
                            <th>Model</th>
                            <th>Category</th>
                            <th class="text-end">Movement Count</th>
                            <th class="text-end">Unique Serials</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($topMovers as $index => $mover)
                            <tr>
                                <td>
                                    @if($index == 0)
                                        <span class="badge bg-warning text-dark">🥇 #{{ $index + 1 }}</span>
                                    @elseif($index == 1)
                                        <span class="badge bg-secondary">🥈 #{{ $index + 1 }}</span>
                                    @elseif($index == 2)
                                        <span class="badge bg-danger">🥉 #{{ $index + 1 }}</span>
                                    @else
                                        <span class="badge bg-light text-dark">#{{ $index + 1 }}</span>
                                    @endif
                                </td>
                                <td><strong>{{ $mover['model_name'] }}</strong></td>
                                <td>{{ $mover['category'] }}</td>
                                <td class="text-end">{{ number_format($mover['movement_count']) }}</td>
                                <td class="text-end">{{ number_format($mover['unique_serials']) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Movements by Location -->
        <div class="col-md-6 mb-4">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-light">
                    <h5 class="mb-0"><i class="bi bi-geo-alt me-2"></i>Movements by Location</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>From</th>
                                    <th>To</th>
                                    <th class="text-end">Count</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($byLocation->take(10) as $location)
                                    <tr>
                                        <td>{{ $location['from'] }}</td>
                                        <td>{{ $location['to'] }}</td>
                                        <td class="text-end">{{ number_format($location['count']) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Movements by Model -->
        <div class="col-md-6 mb-4">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-light">
                    <h5 class="mb-0"><i class="bi bi-box me-2"></i>Movements by Model</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Model</th>
                                    <th class="text-end">In</th>
                                    <th class="text-end">Out</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($byModel->take(10) as $model)
                                    <tr>
                                        <td>{{ $model['model'] }}</td>
                                        <td class="text-end text-success">{{ number_format($model['total_in']) }}</td>
                                        <td class="text-end text-danger">{{ number_format($model['total_out']) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function exportSummary() {
    const form = document.getElementById('filterForm');
    form.action = "{{ route('admin.stock-reports.export-summary') }}";
    form.method = "POST";
    
    const csrfInput = document.createElement('input');
    csrfInput.type = 'hidden';
    csrfInput.name = '_token';
    csrfInput.value = '{{ csrf_token() }}';
    form.appendChild(csrfInput);
    
    form.submit();
    
    setTimeout(() => {
        form.action = "{{ route('admin.stock-reports.summary') }}";
        form.method = "GET";
        csrfInput.remove();
    }, 100);
}
</script>
@endpush
@endsection
