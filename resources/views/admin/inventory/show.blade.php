@extends('layouts.app')
@section('title', 'Inventory Item Details')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1">{{ $inventoryItem->item_name }}</h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.inventory.index') }}">Inventory</a></li>
                    <li class="breadcrumb-item active">{{ $inventoryItem->item_code }}</li>
                </ol>
            </nav>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.inventory.edit', $inventoryItem->id) }}" class="btn btn-primary">
                <i class="bi bi-pencil me-1"></i> Edit
            </a>
            <a href="{{ route('admin.inventory.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Back
            </a>
        </div>
    </div>

    <div class="row g-4">
        <!-- Item Details -->
        <div class="col-md-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white">
                    <h6 class="mb-0"><i class="bi bi-info-circle me-2"></i>Item Details</h6>
                </div>
                <div class="card-body">
                    <table class="table table-borderless mb-0">
                        <tr>
                            <th width="35%" class="text-muted">Item Code</th>
                            <td><span class="badge bg-secondary">{{ $inventoryItem->item_code }}</span></td>
                        </tr>
                        <tr>
                            <th class="text-muted">Item Name</th>
                            <td>{{ $inventoryItem->item_name }}</td>
                        </tr>
                        <tr>
                            <th class="text-muted">Type</th>
                            <td>
                                <span class="badge bg-{{ $inventoryItem->item_type === 'router' ? 'primary' : 'info' }}">
                                    {{ ucfirst($inventoryItem->item_type) }}
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <th class="text-muted">Category</th>
                            <td>{{ $inventoryItem->jobCategory->category_name ?? 'N/A' }}</td>
                        </tr>
                        @if($inventoryItem->serial_number)
                        <tr>
                            <th class="text-muted">Terminal ID</th>
                            <td><code>{{ $inventoryItem->serial_number }}</code></td>
                        </tr>
                        @endif
                        <tr>
                            <th class="text-muted">Brand / Model</th>
                            <td>{{ $inventoryItem->brand ?? '-' }} / {{ $inventoryItem->model ?? '-' }}</td>
                        </tr>
                        <tr>
                            <th class="text-muted">Unit</th>
                            <td>{{ ucfirst($inventoryItem->unit) }}</td>
                        </tr>
                        <tr>
                            <th class="text-muted">Reorder Level</th>
                            <td>{{ $inventoryItem->reorder_level }}</td>
                        </tr>
                        <tr>
                            <th class="text-muted">Status</th>
                            <td>
                                <span class="badge bg-{{ $inventoryItem->status === 'active' ? 'success' : 'danger' }}">
                                    {{ ucfirst($inventoryItem->status) }}
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <th class="text-muted">Created By</th>
                            <td>{{ $inventoryItem->createdBy->name ?? 'System' }} — {{ $inventoryItem->created_at?->format('d M Y H:i') }}</td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        <!-- Stock Balances -->
        <div class="col-md-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white">
                    <h6 class="mb-0"><i class="bi bi-box-seam me-2"></i>Stock Balances</h6>
                </div>
                <div class="card-body">
                    @if($balances->count())
                    <table class="table table-sm">
                        <thead class="table-light">
                            <tr>
                                <th>Holder</th>
                                <th class="text-center">Quantity</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($balances as $balance)
                            <tr>
                                <td>
                                    @if($balance->holder_type === 'warehouse')
                                        <i class="bi bi-building me-1 text-primary"></i> {{ $balance->holder_name }}
                                    @else
                                        <i class="bi bi-person me-1 text-info"></i> {{ $balance->holder_name }}
                                    @endif
                                </td>
                                <td class="text-center fw-bold">{{ $balance->quantity }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="table-light">
                            <tr>
                                <th>Total</th>
                                <th class="text-center">{{ $balances->sum('quantity') }}</th>
                            </tr>
                        </tfoot>
                    </table>
                    @else
                    <div class="text-center text-muted py-4">
                        <i class="bi bi-inbox fs-1"></i>
                        <p class="mt-2">No stock balances found.</p>
                    </div>
                    @endif

                    @if($inventoryItem->isLowStock())
                    <div class="alert alert-warning mt-3 mb-0">
                        <i class="bi bi-exclamation-triangle me-1"></i>
                        <strong>Low Stock Alert!</strong> Warehouse stock is at or below reorder level ({{ $inventoryItem->reorder_level }}).
                    </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Recent Movements -->
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h6 class="mb-0"><i class="bi bi-arrow-left-right me-2"></i>Recent Movements</h6>
                    <a href="{{ route('admin.inventory.movements') }}?inventory_item_id={{ $inventoryItem->id }}" class="btn btn-sm btn-outline-primary">View All</a>
                </div>
                <div class="card-body">
                    @if($recentMovements->count())
                    <div class="table-responsive">
                        <table class="table table-sm table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Movement #</th>
                                    <th>Type</th>
                                    <th>Qty</th>
                                    <th>From</th>
                                    <th>To</th>
                                    <th>Ticket</th>
                                    <th>Performed By</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($recentMovements as $m)
                                <tr>
                                    <td><code>{{ $m->movement_no }}</code></td>
                                    <td>
                                        <span class="badge bg-{{ \App\Models\StockMovement::getTypeBadgeColor($m->movement_type) }}">
                                            {{ \App\Models\StockMovement::getTypeLabel($m->movement_type) }}
                                        </span>
                                    </td>
                                    <td class="fw-bold {{ $m->quantity > 0 ? 'text-success' : 'text-danger' }}">
                                        {{ $m->quantity > 0 ? '+' : '' }}{{ $m->quantity }}
                                    </td>
                                    <td>
                                        @if($m->from_holder_type === 'warehouse') Warehouse
                                        @elseif($m->from_holder_id) {{ \App\Models\User::find($m->from_holder_id)?->name ?? '-' }}
                                        @else -
                                        @endif
                                    </td>
                                    <td>
                                        @if($m->to_holder_type === 'warehouse') Warehouse
                                        @elseif($m->to_holder_id) {{ \App\Models\User::find($m->to_holder_id)?->name ?? '-' }}
                                        @else -
                                        @endif
                                    </td>
                                    <td>
                                        @if($m->ticket)
                                        <a href="{{ route('admin.tickets.show', $m->ticket_id) }}">{{ $m->ticket->ticket_no }}</a>
                                        @else - @endif
                                    </td>
                                    <td>{{ $m->performedBy->name ?? 'N/A' }}</td>
                                    <td>{{ $m->movement_date?->format('d M Y') }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @else
                    <div class="text-center text-muted py-4">
                        <i class="bi bi-arrow-left-right fs-1"></i>
                        <p class="mt-2">No movements recorded yet.</p>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
