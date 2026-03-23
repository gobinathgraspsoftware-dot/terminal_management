@extends('layouts.app')

@section('title', 'My Inventory')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1"><i class="bi bi-box-seam me-2"></i>My Inventory</h4>
            <p class="text-muted mb-0">Your assigned routers &amp; accessories</p>
        </div>
        <div class="d-flex gap-2">
            @can('create_replacements')
            <a href="{{ route('technician.inventory-management.replacement') }}" class="btn btn-warning btn-sm">
                <i class="bi bi-arrow-left-right me-1"></i> Replacement
            </a>
            @endcan
            @can('view_accessory_usage')
            <a href="{{ route('technician.inventory-management.my-accessories') }}" class="btn btn-secondary btn-sm">
                <i class="bi bi-sim me-1"></i> Accessories
            </a>
            @endcan
        </div>
    </div>

    {{-- Summary Cards --}}
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm bg-info bg-opacity-10">
                <div class="card-body text-center">
                    <h3 class="mb-0 text-info">{{ $myRouters->count() }}</h3>
                    <small class="text-muted">Routers Assigned</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm bg-secondary bg-opacity-10">
                <div class="card-body text-center">
                    <h3 class="mb-0 text-secondary">{{ $myAccessories->sum('quantity_on_hand') }}</h3>
                    <small class="text-muted">Accessories On Hand</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm bg-warning bg-opacity-10">
                <div class="card-body text-center">
                    <h3 class="mb-0 text-warning">{{ $myReplacements->where('status', 'draft')->count() }}</h3>
                    <small class="text-muted">Pending Replacements</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm bg-primary bg-opacity-10">
                <div class="card-body text-center">
                    <h3 class="mb-0 text-primary">{{ $myAccessoryUsage->where('action', 'used')->count() }}</h3>
                    <small class="text-muted">Accessories Used</small>
                </div>
            </div>
        </div>
    </div>

    {{-- My Routers --}}
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-header bg-white">
            <h6 class="mb-0"><i class="bi bi-router text-info me-1"></i> My Routers <span class="badge bg-info">{{ $myRouters->count() }}</span></h6>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr><th>Serial No</th><th>Model</th><th>Status</th></tr>
                    </thead>
                    <tbody>
                        @forelse($myRouters as $router)
                        <tr>
                            <td><strong>{{ $router->serial_no }}</strong></td>
                            <td>{{ $router->model->model_name ?? 'N/A' }}</td>
                            <td>
                                @php $badge = \App\Models\InventorySerial::STATUS_BADGES[$router->current_status] ?? 'secondary'; @endphp
                                <span class="badge bg-{{ $badge }}">{{ \App\Models\InventorySerial::STATUS_OPTIONS[$router->current_status] ?? $router->current_status }}</span>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="3" class="text-center text-muted py-3">No routers assigned</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- My Accessories --}}
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-header bg-white">
            <h6 class="mb-0"><i class="bi bi-sim text-secondary me-1"></i> My Accessories</h6>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr><th>Model</th><th>Category</th><th class="text-end">Qty On Hand</th><th class="text-end">Available</th></tr>
                    </thead>
                    <tbody>
                        @forelse($myAccessories as $acc)
                        <tr>
                            <td>{{ $acc->model->model_name ?? 'N/A' }}</td>
                            <td>{{ $acc->model->category->category_name ?? 'N/A' }}</td>
                            <td class="text-end">{{ number_format($acc->quantity_on_hand) }}</td>
                            <td class="text-end">{{ number_format($acc->quantity_available) }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="4" class="text-center text-muted py-3">No accessories assigned</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Recent Replacements --}}
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white">
            <h6 class="mb-0"><i class="bi bi-arrow-left-right text-warning me-1"></i> My Replacements</h6>
        </div>
        <div class="card-body p-0">
            <div class="list-group list-group-flush">
                @forelse($myReplacements as $rpl)
                <div class="list-group-item d-flex justify-content-between align-items-center">
                    <div>
                        <strong>{{ $rpl->replacement_no }}</strong>
                        <br><small class="text-muted">{{ $rpl->old_serial_no }} &rarr; {{ $rpl->new_serial_no }}</small>
                        @if($rpl->ticket)<br><small>Ticket: {{ $rpl->ticket->ticket_no }}</small>@endif
                    </div>
                    <span class="badge bg-{{ \App\Models\Replacement::STATUS_BADGES[$rpl->status] ?? 'secondary' }}">{{ ucfirst($rpl->status) }}</span>
                </div>
                @empty
                <div class="list-group-item text-muted text-center">No replacements</div>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
