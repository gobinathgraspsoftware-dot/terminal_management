@extends('layouts.app')

@section('title', 'Inventory Management')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1"><i class="bi bi-box-seam me-2"></i>Inventory Management</h4>
            <p class="text-muted mb-0">Team inventory — Router &amp; Accessories overview</p>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            @can('create_stock_out')
            <a href="{{ route('supervisor.inventory-management.stock-out') }}" class="btn btn-danger btn-sm">
                <i class="bi bi-box-arrow-up me-1"></i> Stock Out
            </a>
            @endcan
            @can('create_replacements')
            <a href="{{ route('supervisor.inventory-management.replacement') }}" class="btn btn-warning btn-sm">
                <i class="bi bi-arrow-left-right me-1"></i> Replacement
            </a>
            @endcan
            @can('view_accessory_usage')
            <a href="{{ route('supervisor.inventory-management.accessories') }}" class="btn btn-secondary btn-sm">
                <i class="bi bi-sim me-1"></i> Accessories
            </a>
            @endcan
        </div>
    </div>

    {{-- Summary Cards --}}
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="rounded-circle bg-info bg-opacity-10 p-3 me-3">
                            <i class="bi bi-router text-info fs-4"></i>
                        </div>
                        <div>
                            <p class="text-muted mb-0 small">Routers Total</p>
                            <h4 class="mb-0">{{ $summary['router_stock']->sum('total') ?? 0 }}</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="rounded-circle bg-secondary bg-opacity-10 p-3 me-3">
                            <i class="bi bi-sim text-secondary fs-4"></i>
                        </div>
                        <div>
                            <p class="text-muted mb-0 small">Accessories Available</p>
                            <h4 class="mb-0">{{ number_format($summary['accessory_stock']->total_available ?? 0) }}</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="rounded-circle bg-warning bg-opacity-10 p-3 me-3">
                            <i class="bi bi-arrow-left-right text-warning fs-4"></i>
                        </div>
                        <div>
                            <p class="text-muted mb-0 small">Pending Replacements</p>
                            <h4 class="mb-0">{{ $recentReplacements->where('status', 'draft')->count() }}</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Router / Accessories Tabs --}}
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white">
            <ul class="nav nav-tabs card-header-tabs" role="tablist">
                <li class="nav-item">
                    <a class="nav-link active" data-bs-toggle="tab" href="#tab-routers" role="tab">
                        <i class="bi bi-router me-1"></i> Routers <span class="badge bg-info ms-1">Serial Tracked</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-bs-toggle="tab" href="#tab-accessories" role="tab">
                        <i class="bi bi-sim me-1"></i> Accessories <span class="badge bg-secondary ms-1">Qty Based</span>
                    </a>
                </li>
            </ul>
        </div>
        <div class="card-body">
            <div class="tab-content">
                <div class="tab-pane fade show active" id="tab-routers" role="tabpanel">
                    <div class="row g-2 mb-3">
                        <div class="col-md-3">
                            <select id="filterRouterStatus" class="form-select form-select-sm">
                                <option value="">All Statuses</option>
                                @foreach(\App\Models\InventorySerial::STATUS_OPTIONS as $val => $label)
                                <option value="{{ $val }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <select id="filterRouterDepot" class="form-select form-select-sm">
                                <option value="">All Depots</option>
                                @foreach($depots as $depot)
                                <option value="{{ $depot->id }}">{{ $depot->depot_name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table id="routerTable" class="table table-sm table-hover align-middle" style="width:100%">
                            <thead class="table-light">
                                <tr>
                                    <th>Serial No</th><th>Model</th><th>Category</th><th>Status</th><th>Location</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
                <div class="tab-pane fade" id="tab-accessories" role="tabpanel">
                    <div class="table-responsive">
                        <table id="accessoryTable" class="table table-sm table-hover align-middle" style="width:100%">
                            <thead class="table-light">
                                <tr>
                                    <th>Model</th><th>Category</th><th>Location</th><th class="text-end">On Hand</th><th class="text-end">Available</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Team Replacements --}}
    <div class="card border-0 shadow-sm mt-3">
        <div class="card-header bg-white">
            <h6 class="mb-0"><i class="bi bi-arrow-left-right text-warning me-1"></i> Team Replacements</h6>
        </div>
        <div class="card-body p-0">
            <div class="list-group list-group-flush">
                @forelse($recentReplacements as $rpl)
                <div class="list-group-item d-flex justify-content-between align-items-center">
                    <div>
                        <strong>{{ $rpl->replacement_no }}</strong>
                        <small class="text-muted ms-2">{{ $rpl->technician->name ?? '' }}</small>
                        <br><small>{{ $rpl->old_serial_no }} &rarr; {{ $rpl->new_serial_no }}</small>
                    </div>
                    <span class="badge bg-{{ \App\Models\Replacement::STATUS_BADGES[$rpl->status] ?? 'secondary' }}">{{ ucfirst($rpl->status) }}</span>
                </div>
                @empty
                <div class="list-group-item text-muted text-center">No team replacements</div>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    window.routerTable = $('#routerTable').DataTable({
        processing: true, serverSide: true,
        ajax: {
            url: '{{ route("supervisor.inventory-management.router-datatable") }}',
            data: function(d) { d.status = $('#filterRouterStatus').val(); d.depot_id = $('#filterRouterDepot').val(); }
        },
        columns: [
            { data: 'serial_no' }, { data: 'model_name' }, { data: 'category' },
            { data: 'status' }, { data: 'location' }
        ],
        order: [[0, 'asc']], pageLength: 10
    });

    window.accessoryTable = $('#accessoryTable').DataTable({
        processing: true, serverSide: true,
        ajax: '{{ route("supervisor.inventory-management.accessory-datatable") }}',
        columns: [
            { data: 'model_name' }, { data: 'category' }, { data: 'location' },
            { data: 'on_hand' }, { data: 'available' }
        ],
        order: [[0, 'asc']], pageLength: 10
    });

    $('#filterRouterStatus, #filterRouterDepot').on('change', function() { window.routerTable.ajax.reload(); });
});
</script>
@endpush
