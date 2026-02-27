@extends('layouts.app')

@section('title', 'My Inventory')

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <h1 class="h3 mb-1">My Inventory</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('technician.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item active">My Inventory</li>
                </ol>
            </nav>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card border-start border-primary border-4">
                <div class="card-body">
                    <h6 class="text-muted mb-1">Total Items</h6>
                    <h4 class="mb-0">{{ number_format($summary->total ?? 0) }}</h4>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-start border-info border-4">
                <div class="card-body">
                    <h6 class="text-muted mb-1">Issued to Me</h6>
                    <h4 class="mb-0">{{ number_format($summary->issued ?? 0) }}</h4>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-start border-success border-4">
                <div class="card-body">
                    <h6 class="text-muted mb-1">Installed</h6>
                    <h4 class="mb-0">{{ number_format($summary->installed ?? 0) }}</h4>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-start border-warning border-4">
                <div class="card-body">
                    <h6 class="text-muted mb-1">Under Service</h6>
                    <h4 class="mb-0">{{ number_format($summary->under_service ?? 0) }}</h4>
                </div>
            </div>
        </div>
    </div>

    <!-- Current Inventory -->
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0"><i class="bi bi-box-seam me-2"></i>My Current Serials</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Serial No</th>
                            <th>Model</th>
                            <th>Category</th>
                            <th>Status</th>
                            <th>GRN Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($serials as $serial)
                            <tr>
                                <td><strong>{{ $serial->serial_no }}</strong></td>
                                <td>{{ $serial->model?->model_name ?? 'Unknown' }}</td>
                                <td>
                                    @if($serial->model?->category)
                                        <span class="badge bg-secondary">{{ $serial->model->category->category_name }}</span>
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>{!! $serial->status_badge !!}</td>
                                <td>{{ $serial->grn_date ? $serial->grn_date->format('M d, Y') : '-' }}</td>
                                <td>
                                    <a href="{{ route('technician.stock-reports.stock-card', $serial->id) }}"
                                       class="btn btn-sm btn-outline-info" title="View Stock Card">
                                        <i class="bi bi-card-text"></i>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">
                                    <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                                    No items currently assigned to you
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-3">{{ $serials->links() }}</div>
        </div>
    </div>

    <!-- Recent Movements -->
    <div class="card shadow-sm">
        <div class="card-header bg-light">
            <h5 class="mb-0"><i class="bi bi-clock-history me-2"></i>My Recent Movements</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-sm table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>Date</th>
                            <th>Serial No</th>
                            <th>Model</th>
                            <th>Type</th>
                            <th class="text-end">Qty</th>
                            <th>From</th>
                            <th>To</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recentMovements as $movement)
                            <tr>
                                <td>{{ $movement->transaction_date->format('M d, Y') }}</td>
                                <td>
                                    @if($movement->serial)
                                        <a href="{{ route('technician.stock-reports.stock-card', $movement->serial->id) }}">
                                            {{ $movement->serial->serial_no }}
                                        </a>
                                    @else
                                        {{ $movement->serial_no ?? '-' }}
                                    @endif
                                </td>
                                <td>{{ $movement->serial?->model?->model_name ?? '-' }}</td>
                                <td>{!! $movement->type_badge !!}</td>
                                <td class="text-end">
                                    @if($movement->quantity > 0)
                                        <span class="text-success">+{{ $movement->quantity }}</span>
                                    @else
                                        <span class="text-danger">{{ $movement->quantity }}</span>
                                    @endif
                                </td>
                                <td><small>{{ $movement->from_location_name }}</small></td>
                                <td><small>{{ $movement->to_location_name }}</small></td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted py-3">
                                    <i class="bi bi-inbox"></i> No recent movements
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
