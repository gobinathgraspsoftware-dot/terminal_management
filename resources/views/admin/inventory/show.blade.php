@extends('layouts.app')
@section('title', $inventory_item->item_name)

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1">{{ $inventory_item->item_name }}</h4>
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
            <a href="{{ route('admin.inventory.edit', $inventory_item) }}" class="btn btn-warning btn-sm">
                <i class="bi bi-pencil me-1"></i> Edit
            </a>
            @endcan
            <a href="{{ route('admin.inventory.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left me-1"></i> Back
            </a>
        </div>
    </div>

    <div class="row g-4">
        {{-- Left: Details --}}
        <div class="col-md-5">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white fw-semibold">Item Details</div>
                <div class="card-body">
                    <table class="table table-borderless table-sm mb-0">
                        <tr><th class="text-muted" width="140">Item Code</th><td class="fw-semibold">{{ $inventory_item->item_code }}</td></tr>
                        <tr><th class="text-muted">Item Name</th><td>{{ $inventory_item->item_name }}</td></tr>
                        <tr><th class="text-muted">Type</th><td>{!! $inventory_item->getTypeBadge() !!}</td></tr>
                        @if($inventory_item->isRouter())
                        <tr><th class="text-muted">Terminal ID</th><td class="fw-semibold text-primary">{{ $inventory_item->serial_number ?? '-' }}</td></tr>
                        @endif
                        @if($inventory_item->isAccessory())
                        <tr><th class="text-muted">Accessory Type</th><td>{{ $inventory_item->accessory_type === 'sim_card' ? 'SIM Card' : 'Antenna' }}</td></tr>
                        @endif
                        <tr><th class="text-muted">Category</th><td>{{ $inventory_item->jobCategory->category_name ?? 'N/A' }}</td></tr>
                        <tr><th class="text-muted">Brand</th><td>{{ $inventory_item->brand ?? '-' }}</td></tr>
                        <tr><th class="text-muted">Model</th><td>{{ $inventory_item->model ?? '-' }}</td></tr>
                        <tr><th class="text-muted">Unit</th><td>{{ $inventory_item->unit ?? 'unit' }}</td></tr>
                        <tr><th class="text-muted">Reorder Level</th><td>{{ $inventory_item->reorder_level }}</td></tr>
                        <tr><th class="text-muted">Status</th><td>{!! $inventory_item->getStatusBadge() !!}</td></tr>
                        <tr><th class="text-muted">Description</th><td>{{ $inventory_item->description ?? '-' }}</td></tr>
                        <tr><th class="text-muted">Created</th><td>{{ $inventory_item->created_at?->format('d M Y H:i') }} by {{ $inventory_item->creator->name ?? 'N/A' }}</td></tr>
                        @if($inventory_item->updater)
                        <tr><th class="text-muted">Updated</th><td>{{ $inventory_item->updated_at?->format('d M Y H:i') }} by {{ $inventory_item->updater->name }}</td></tr>
                        @endif
                    </table>
                </div>
            </div>

            {{-- Stock Balances --}}
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white fw-semibold">Stock Balances</div>
                <div class="card-body">
                    <div class="row g-3 mb-3">
                        <div class="col-6">
                            <div class="border rounded p-3 text-center {{ $inventory_item->isLowStock() ? 'border-danger' : '' }}">
                                <div class="fs-3 fw-bold {{ $inventory_item->isLowStock() ? 'text-danger' : 'text-primary' }}">{{ $warehouseStock }}</div>
                                <small class="text-muted">Warehouse</small>
                                @if($inventory_item->isLowStock())
                                <div><small class="text-danger"><i class="bi bi-exclamation-triangle"></i> Low Stock</small></div>
                                @endif
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="border rounded p-3 text-center">
                                <div class="fs-3 fw-bold text-success">{{ $totalStock }}</div>
                                <small class="text-muted">Total (All Locations)</small>
                            </div>
                        </div>
                    </div>

                    @if($balances->count())
                    <table class="table table-sm table-hover">
                        <thead class="table-light">
                            <tr><th>Location</th><th>Holder</th><th class="text-end">Qty</th></tr>
                        </thead>
                        <tbody>
                            @foreach($balances as $bal)
                            <tr>
                                <td><span class="badge bg-{{ $bal->isWarehouse() ? 'primary' : 'info' }}">{{ ucfirst($bal->holder_type) }}</span></td>
                                <td>{{ $bal->isWarehouse() ? 'Main Warehouse' : ($bal->holder->name ?? 'Unknown') }}</td>
                                <td class="text-end fw-semibold">{{ $bal->quantity }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                    @else
                    <p class="text-muted mb-0">No stock balances found.</p>
                    @endif
                </div>
            </div>
        </div>

        {{-- Right: Movements & Adjustments --}}
        <div class="col-md-7">
            {{-- Recent Movements --}}
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <span class="fw-semibold">Recent Movements</span>
                    <a href="{{ route('admin.inventory.movements') }}?inventory_item_id={{ $inventory_item->id }}" class="btn btn-outline-primary btn-sm">View All</a>
                </div>
                <div class="card-body p-0">
                    @if($recentMovements->count())
                    <div class="table-responsive">
                        <table class="table table-sm table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Movement No</th>
                                    <th>Type</th>
                                    <th>Qty</th>
                                    <th>Ticket</th>
                                    <th>Date</th>
                                    <th>By</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($recentMovements as $mv)
                                <tr>
                                    <td><code>{{ $mv->movement_no }}</code></td>
                                    <td>{!! $mv->getTypeBadge() !!}</td>
                                    <td class="{{ $mv->quantity > 0 ? 'text-success' : 'text-danger' }} fw-semibold">
                                        {{ $mv->quantity > 0 ? '+' : '' }}{{ $mv->quantity }}
                                    </td>
                                    <td>
                                        @if($mv->ticket)
                                        <a href="{{ route('admin.tickets.show', $mv->ticket_id) }}">{{ $mv->ticket->ticket_no }}</a>
                                        @else
                                        -
                                        @endif
                                    </td>
                                    <td>{{ $mv->movement_date?->format('d M Y') }}</td>
                                    <td>{{ $mv->performer->name ?? 'N/A' }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @else
                    <div class="p-3 text-muted">No movements recorded yet.</div>
                    @endif
                </div>
            </div>

            {{-- Adjustment History --}}
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white fw-semibold">Adjustment History</div>
                <div class="card-body p-0">
                    @if($adjustments->count())
                    <div class="table-responsive">
                        <table class="table table-sm table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Adj. No</th>
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
                                    <td><code>{{ $adj->adjustment_no }}</code></td>
                                    <td>{!! $adj->getTypeBadge() !!}</td>
                                    <td>{{ $adj->old_quantity }}</td>
                                    <td class="fw-semibold">{{ $adj->new_quantity }}</td>
                                    <td class="{{ $adj->difference > 0 ? 'text-success' : 'text-danger' }}">
                                        {{ $adj->difference > 0 ? '+' : '' }}{{ $adj->difference }}
                                    </td>
                                    <td>{{ \Illuminate\Support\Str::limit($adj->reason, 40) }}</td>
                                    <td>{{ $adj->adjustedBy->name ?? 'N/A' }}</td>
                                    <td>{{ $adj->adjusted_at?->format('d M Y H:i') }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @else
                    <div class="p-3 text-muted">No adjustments recorded.</div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
