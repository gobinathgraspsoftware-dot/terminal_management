@extends('layouts.app')
@section('title', 'View Item: ' . $inventory_item->item_name)

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1">{{ $inventory_item->item_name }} <small class="text-muted">({{ $inventory_item->item_code }})</small></h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.inventory.index') }}">Inventory</a></li>
                    <li class="breadcrumb-item active">{{ $inventory_item->item_code }}</li>
                </ol>
            </nav>
        </div>
        <div>
            @can('edit_inventory')
            <a href="{{ route('admin.inventory.edit', $inventory_item) }}" class="btn btn-primary btn-sm">
                <i class="bi bi-pencil me-1"></i> Edit
            </a>
            @endcan
            <a href="{{ route('admin.inventory.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left me-1"></i> Back
            </a>
        </div>
    </div>

    <div class="row g-4">
        <!-- Item Details -->
        <div class="col-md-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-transparent fw-bold">Item Details</div>
                <div class="card-body">
                    <table class="table table-sm table-borderless mb-0">
                        <tr><td class="text-muted" style="width:35%">Item Code</td><td class="fw-semibold">{{ $inventory_item->item_code }}</td></tr>
                        <tr><td class="text-muted">Item Name</td><td>{{ $inventory_item->item_name }}</td></tr>
                        <tr><td class="text-muted">Type</td><td>{!! $inventory_item->getTypeBadge() !!}</td></tr>
                        @if($inventory_item->isAccessory())
                        <tr><td class="text-muted">Accessory Type</td><td>{{ $inventory_item->accessory_type === 'sim_card' ? 'SIM Card' : 'Antenna' }}</td></tr>
                        @endif
                        @if($inventory_item->jobCategory)
                        <tr><td class="text-muted">Job Category</td><td>{{ $inventory_item->jobCategory->category_name }}</td></tr>
                        @endif
                        <tr><td class="text-muted">Brand</td><td>{{ $inventory_item->brand ?? '-' }}</td></tr>
                        <tr><td class="text-muted">Model</td><td>{{ $inventory_item->model ?? '-' }}</td></tr>
                        @if($inventory_item->isRouter())
                        <tr><td class="text-muted">Serial Number</td><td>{{ $inventory_item->serial_number ?? '-' }}</td></tr>
                        @endif
                        <tr><td class="text-muted">Unit</td><td>{{ $inventory_item->unit ?? 'unit' }}</td></tr>
                        <tr><td class="text-muted">Reorder Level</td><td>{{ $inventory_item->reorder_level }}</td></tr>
                        <tr><td class="text-muted">Status</td><td>{!! $inventory_item->getStatusBadge() !!}</td></tr>
                        <tr><td class="text-muted">Description</td><td>{{ $inventory_item->description ?? '-' }}</td></tr>
                        <tr><td class="text-muted">Created By</td><td>{{ $inventory_item->creator->name ?? 'N/A' }}</td></tr>
                        <tr><td class="text-muted">Created At</td><td>{{ $inventory_item->created_at?->format('d M Y H:i') }}</td></tr>
                    </table>
                </div>
            </div>
        </div>

        <!-- Stock Summary -->
        <div class="col-md-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-transparent fw-bold">Stock Summary</div>
                <div class="card-body">
                    <div class="row g-3 mb-4">
                        <div class="col-6">
                            <div class="bg-light rounded p-3 text-center">
                                <div class="text-muted small">Warehouse Stock</div>
                                <div class="fs-3 fw-bold {{ $inventory_item->isLowStock() ? 'text-danger' : 'text-success' }}">
                                    {{ $warehouseStock }}
                                    @if($inventory_item->isLowStock())
                                    <i class="bi bi-exclamation-triangle-fill fs-5"></i>
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="bg-light rounded p-3 text-center">
                                <div class="text-muted small">Total Stock</div>
                                <div class="fs-3 fw-bold text-primary">{{ $totalStock }}</div>
                            </div>
                        </div>
                    </div>

                    <!-- Balances by location -->
                    @if($balances->count())
                    <h6 class="fw-bold mb-2">Stock by Location</h6>
                    <table class="table table-sm">
                        <thead><tr><th>Location</th><th class="text-end">Qty</th></tr></thead>
                        <tbody>
                            @foreach($balances as $bal)
                            <tr>
                                <td>{{ $bal->isWarehouse() ? 'Warehouse' : ($bal->holder->name ?? 'Technician #'.$bal->holder_id) }}</td>
                                <td class="text-end fw-semibold">{{ $bal->quantity }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                    @endif
                </div>
            </div>
        </div>

        <!-- Recent Movements -->
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-transparent fw-bold">Recent Movements</div>
                <div class="card-body">
                    @if($recentMovements->count())
                    <div class="table-responsive">
                        <table class="table table-sm table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Movement #</th>
                                    <th>Type</th>
                                    <th>Qty</th>
                                    {{-- FIX: Only show Router IDs column for router items --}}
                                    @if($inventory_item->isRouter())
                                    <th>Router IDs</th>
                                    @endif
                                    <th>From</th>
                                    <th>To</th>
                                    <th>Ticket</th>
                                    <th>Date</th>
                                    <th>By</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($recentMovements as $m)
                                <tr>
                                    <td><span class="fw-semibold">{{ $m->movement_no }}</span></td>
                                    <td>{!! $m->getTypeBadge() !!}</td>
                                    <td>{{ $m->quantity }}</td>
                                    {{-- FIX: Only show Router IDs data for router items --}}
                                    @if($inventory_item->isRouter())
                                    <td><small>{{ $m->getRouterIdsDisplay() }}</small></td>
                                    @endif
                                    <td>{{ $m->getFromLocation() }}</td>
                                    <td>{{ $m->getToLocation() }}</td>
                                    <td>{{ $m->ticket->ticket_no ?? '-' }}</td>
                                    <td>{{ $m->movement_date?->format('d M Y') }}</td>
                                    <td>{{ $m->performer->name ?? 'N/A' }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @else
                    <p class="text-muted mb-0">No movements recorded yet.</p>
                    @endif
                </div>
            </div>
        </div>

        <!-- Adjustments -->
        @if($adjustments->count())
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-transparent fw-bold">Adjustment History</div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Adj #</th>
                                    <th>Type</th>
                                    <th>Old Qty</th>
                                    <th>New Qty</th>
                                    <th>Diff</th>
                                    <th>Reason</th>
                                    <th>By</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($adjustments as $adj)
                                <tr>
                                    <td class="fw-semibold">{{ $adj->adjustment_no }}</td>
                                    <td><span class="badge bg-{{ $adj->adjustment_type === 'increase' ? 'success' : 'danger' }}">{{ ucfirst($adj->adjustment_type) }}</span></td>
                                    <td>{{ $adj->old_quantity }}</td>
                                    <td>{{ $adj->new_quantity }}</td>
                                    <td class="{{ $adj->difference > 0 ? 'text-success' : 'text-danger' }}">{{ $adj->difference > 0 ? '+' : '' }}{{ $adj->difference }}</td>
                                    <td>{{ $adj->reason }}</td>
                                    <td>{{ $adj->adjustedBy->name ?? 'N/A' }}</td>
                                    <td>{{ $adj->adjusted_at?->format('d M Y H:i') }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        @endif
    </div>
</div>
@endsection
