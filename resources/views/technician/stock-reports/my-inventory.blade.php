@extends('layouts.app')

@section('title', 'My Inventory')

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h3 mb-1">My Inventory</h1>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-0">
                            <li class="breadcrumb-item"><a href="{{ route('technician.dashboard') }}">Dashboard</a></li>
                            <li class="breadcrumb-item active">My Inventory</li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </div>

    <!-- Current Inventory Summary -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card border-start border-primary border-4">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <i class="bi bi-box-seam text-primary fs-2"></i>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="text-muted mb-1">Total Items</h6>
                            <h3 class="mb-0">{{ $currentInventory->count() }}</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-start border-success border-4">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <i class="bi bi-check-circle text-success fs-2"></i>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="text-muted mb-1">In Stock</h6>
                            <h3 class="mb-0">{{ $currentInventory->where('current_status', 'in_stock')->count() }}</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-start border-warning border-4">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <i class="bi bi-tools text-warning fs-2"></i>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="text-muted mb-1">In Service</h6>
                            <h3 class="mb-0">{{ $currentInventory->where('current_status', 'in_service')->count() }}</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Current Inventory -->
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-light">
            <h5 class="mb-0"><i class="bi bi-list-check me-2"></i>Current Stock</h5>
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
                            <th>Received Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($currentInventory as $serial)
                            <tr>
                                <td>
                                    <strong>{{ $serial->serial_no }}</strong>
                                </td>
                                <td>{{ $serial->model ? $serial->model->model_name : 'N/A' }}</td>
                                <td>
                                    @if($serial->model && $serial->model->category)
                                        <span class="badge bg-secondary">{{ $serial->model->category->category_name }}</span>
                                    @else
                                        <span class="badge bg-light text-dark">N/A</span>
                                    @endif
                                </td>
                                <td>
                                    @php
                                        $statusColors = [
                                            'in_stock' => 'success',
                                            'reserved' => 'warning',
                                            'in_service' => 'info',
                                        ];
                                        $color = $statusColors[$serial->current_status] ?? 'secondary';
                                    @endphp
                                    <span class="badge bg-{{ $color }}">
                                        {{ ucfirst(str_replace('_', ' ', $serial->current_status)) }}
                                    </span>
                                </td>
                                <td>{{ $serial->created_at->format('M d, Y') }}</td>
                                <td>
                                    <a href="{{ route('technician.stock-reports.stock-card', $serial->id) }}" 
                                       class="btn btn-sm btn-outline-info" title="View History">
                                        <i class="bi bi-card-text"></i> History
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">
                                    <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                                    No inventory items found
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Recent Movements -->
    <div class="card shadow-sm">
        <div class="card-header bg-light">
            <h5 class="mb-0"><i class="bi bi-clock-history me-2"></i>Recent Movements</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-sm table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Date</th>
                            <th>Type</th>
                            <th>Serial No</th>
                            <th>Model</th>
                            <th>From</th>
                            <th>To</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($movements as $movement)
                            <tr>
                                <td>{{ $movement->transaction_date->format('M d, Y') }}</td>
                                <td>{!! $movement->type_badge !!}</td>
                                <td>
                                    <a href="{{ route('technician.stock-reports.stock-card', $movement->serial_id) }}" class="text-decoration-none">
                                        {{ $movement->serial_no }}
                                    </a>
                                </td>
                                <td>{{ $movement->model ? $movement->model->model_name : '-' }}</td>
                                <td>{{ $movement->from_location_name }}</td>
                                <td>{{ $movement->to_location_name }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-3">
                                    No recent movements
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            @if($movements->hasPages())
                <div class="mt-3">
                    {{ $movements->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
