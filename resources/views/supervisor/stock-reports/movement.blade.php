@extends('layouts.app')

@section('title', 'Stock Movement Report')

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h3 mb-1">Stock Movement Report</h1>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-0">
                            <li class="breadcrumb-item"><a href="{{ route('supervisor.dashboard') }}">Dashboard</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('supervisor.stock-reports.index') }}">Stock Reports</a></li>
                            <li class="breadcrumb-item active">Movement Report</li>
                        </ol>
                    </nav>
                </div>
                <div>
                    <a href="{{ route('supervisor.stock-reports.movement-export', request()->query()) }}" class="btn btn-success">
                        <i class="bi bi-file-earmark-excel me-2"></i>Export to Excel
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card border-start border-primary border-4">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0"><i class="bi bi-arrow-repeat text-primary fs-2"></i></div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="text-muted mb-1">Total Movements</h6>
                            <h4 class="mb-0">{{ number_format($summary['total_movements']) }}</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-start border-success border-4">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0"><i class="bi bi-arrow-down-circle text-success fs-2"></i></div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="text-muted mb-1">Total In</h6>
                            <h4 class="mb-0">{{ number_format($summary['total_in']) }}</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-start border-danger border-4">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0"><i class="bi bi-arrow-up-circle text-danger fs-2"></i></div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="text-muted mb-1">Total Out</h6>
                            <h4 class="mb-0">{{ number_format(abs($summary['total_out'])) }}</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-start border-info border-4">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0"><i class="bi bi-calculator text-info fs-2"></i></div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="text-muted mb-1">Net Movement</h6>
                            <h4 class="mb-0">{{ number_format($summary['total_in'] + $summary['total_out']) }}</h4>
                        </div>
                    </div>
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
            <form method="GET" action="{{ route('supervisor.stock-reports.movement') }}" id="filterForm">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">From Date</label>
                        <input type="date" name="from_date" class="form-control" value="{{ $filters['from_date'] ?? '' }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">To Date</label>
                        <input type="date" name="to_date" class="form-control" value="{{ $filters['to_date'] ?? '' }}">
                    </div>
                    <div class="col-md-3">
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
                    <div class="col-md-3">
                        <label class="form-label">Model</label>
                        <select name="model_id" class="form-select">
                            <option value="">All Models</option>
                            @foreach($models as $model)
                                <option value="{{ $model->id }}" {{ ($filters['model_id'] ?? '') == $model->id ? 'selected' : '' }}>
                                    {{ $model->model_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Transaction Type</label>
                        <select name="transaction_type" class="form-select">
                            <option value="">All Types</option>
                            @foreach(\App\Models\StockLedger::TYPE_OPTIONS as $key => $label)
                                <option value="{{ $key }}" {{ ($filters['transaction_type'] ?? '') == $key ? 'selected' : '' }}>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Serial Number</label>
                        <input type="text" name="serial_no" class="form-control" placeholder="Search serial..." value="{{ $filters['serial_no'] ?? '' }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Depot</label>
                        <select name="location_id" class="form-select" onchange="document.getElementById('location_type').value='depot'">
                            <option value="">All Depots</option>
                            @foreach($depots as $depot)
                                <option value="{{ $depot->id }}" {{ ($filters['location_id'] ?? '') == $depot->id ? 'selected' : '' }}>
                                    {{ $depot->depot_name }}
                                </option>
                            @endforeach
                        </select>
                        <input type="hidden" name="location_type" id="location_type" value="{{ $filters['location_type'] ?? '' }}">
                    </div>
                    <div class="col-md-3 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary me-2">
                            <i class="bi bi-search me-1"></i>Filter
                        </button>
                        <a href="{{ route('supervisor.stock-reports.movement') }}" class="btn btn-secondary">
                            <i class="bi bi-x-circle me-1"></i>Reset
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Data Table -->
    <div class="card shadow-sm">
        <div class="card-header bg-light">
            <h5 class="mb-0"><i class="bi bi-table me-2"></i>Movement Details</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Date</th>
                            <th>Transaction No</th>
                            <th>Type</th>
                            <th>Serial No</th>
                            <th>Model</th>
                            <th>Qty</th>
                            <th>From</th>
                            <th>To</th>
                            <th>Reference</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($movements as $movement)
                            <tr>
                                <td>{{ $movement->transaction_date->format('M d, Y') }}</td>
                                <td><span class="badge bg-secondary">{{ $movement->transaction_no }}</span></td>
                                <td>{!! $movement->type_badge !!}</td>
                                <td>
                                    @if($movement->serial_id)
                                        <a href="{{ route('supervisor.stock-reports.stock-card', $movement->serial_id) }}" class="text-decoration-none">
                                            {{ $movement->serial_no }}
                                        </a>
                                    @else
                                        {{ $movement->serial_no ?? '-' }}
                                    @endif
                                </td>
                                <td>{{ $movement->serial?->model?->model_name ?? '-' }}</td>
                                <td>
                                    @if($movement->quantity > 0)
                                        <span class="badge bg-success">+{{ $movement->quantity }}</span>
                                    @else
                                        <span class="badge bg-danger">{{ $movement->quantity }}</span>
                                    @endif
                                </td>
                                <td>{{ $movement->from_location_name }}</td>
                                <td>{{ $movement->to_location_name }}</td>
                                <td>
                                    @if($movement->reference_url)
                                        <a href="{{ $movement->reference_url }}" class="text-decoration-none">
                                            {{ $movement->reference_label }}
                                        </a>
                                    @else
                                        {{ $movement->reference_label }}
                                    @endif
                                </td>
                                <td>
                                    @if($movement->serial_id)
                                        <a href="{{ route('supervisor.stock-reports.stock-card', $movement->serial_id) }}"
                                           class="btn btn-sm btn-outline-info" title="View Stock Card">
                                            <i class="bi bi-card-text"></i>
                                        </a>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="text-center text-muted py-4">
                                    <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                                    No movements found
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-3">{{ $movements->links() }}</div>
        </div>
    </div>
</div>
@endsection
