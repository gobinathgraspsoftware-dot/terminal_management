@extends('layouts.app')

@section('title', 'Inventory Management')

@section('content')
<div class="container-fluid">
    {{-- Page Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1"><i class="bi bi-box-seam me-2"></i>Inventory Management</h4>
            <p class="text-muted mb-0">Router &amp; Accessories — Stock Categories Overview</p>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            @can('create_stock_in')
            <a href="{{ route('admin.inventory-management.stock-in') }}" class="btn btn-success">
                <i class="bi bi-box-arrow-in-down me-1"></i> Stock In
            </a>
            @endcan
            @can('create_stock_out')
            <a href="{{ route('admin.inventory-management.stock-out') }}" class="btn btn-danger">
                <i class="bi bi-box-arrow-up me-1"></i> Stock Out
            </a>
            @endcan
            @can('create_replacements')
            <a href="{{ route('admin.inventory-management.replacement') }}" class="btn btn-warning">
                <i class="bi bi-arrow-left-right me-1"></i> Replacement
            </a>
            @endcan
        </div>
    </div>

    {{-- Summary Cards --}}
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="rounded-circle bg-info bg-opacity-10 p-3 me-3">
                            <i class="bi bi-router text-info fs-4"></i>
                        </div>
                        <div>
                            <p class="text-muted mb-0 small">Routers</p>
                            <h4 class="mb-0" id="routerTotal">
                                {{ $summary['router_stock']->sum('total') ?? 0 }}
                            </h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="rounded-circle bg-secondary bg-opacity-10 p-3 me-3">
                            <i class="bi bi-sim text-secondary fs-4"></i>
                        </div>
                        <div>
                            <p class="text-muted mb-0 small">Accessories On Hand</p>
                            <h4 class="mb-0">
                                {{ number_format($summary['accessory_stock']->total_on_hand ?? 0) }}
                            </h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="rounded-circle bg-success bg-opacity-10 p-3 me-3">
                            <i class="bi bi-box-arrow-in-down text-success fs-4"></i>
                        </div>
                        <div>
                            <p class="text-muted mb-0 small">Recent Stock Ins</p>
                            <h4 class="mb-0">{{ $recentStockIns->count() }}</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="rounded-circle bg-warning bg-opacity-10 p-3 me-3">
                            <i class="bi bi-arrow-left-right text-warning fs-4"></i>
                        </div>
                        <div>
                            <p class="text-muted mb-0 small">Pending Replacements</p>
                            <h4 class="mb-0">
                                {{ $recentReplacements->where('status', 'draft')->count() }}
                            </h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Stock Categories Tabs --}}
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white">
            <ul class="nav nav-tabs card-header-tabs" role="tablist">
                <li class="nav-item">
                    <a class="nav-link active" data-bs-toggle="tab" href="#tab-routers" role="tab">
                        <i class="bi bi-router me-1"></i> Routers
                        <span class="badge bg-info ms-1">Serial Tracked</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-bs-toggle="tab" href="#tab-accessories" role="tab">
                        <i class="bi bi-sim me-1"></i> Accessories
                        <span class="badge bg-secondary ms-1">Quantity Based</span>
                    </a>
                </li>
            </ul>
        </div>
        <div class="card-body">
            <div class="tab-content">
                {{-- Router Tab --}}
                <div class="tab-pane fade show active" id="tab-routers" role="tabpanel">
                    @include('admin.inventory-management.partials._router-table')
                </div>

                {{-- Accessories Tab --}}
                <div class="tab-pane fade" id="tab-accessories" role="tabpanel">
                    @include('admin.inventory-management.partials._accessory-table')
                </div>
            </div>
        </div>
    </div>

    {{-- Recent Activity Row --}}
    <div class="row g-3 mt-3">
        {{-- Recent Stock Ins --}}
        <div class="col-md-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h6 class="mb-0"><i class="bi bi-box-arrow-in-down text-success me-1"></i> Recent Stock In</h6>
                </div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush">
                        @forelse($recentStockIns as $si)
                        <div class="list-group-item d-flex justify-content-between align-items-center">
                            <div>
                                <strong>{{ $si->stock_in_no }}</strong>
                                <br><small class="text-muted">{{ $si->depot->depot_name ?? 'N/A' }} &middot; {{ $si->stock_in_date?->format('d M Y') }}</small>
                            </div>
                            <span class="badge bg-{{ \App\Models\StockIn::STATUS_BADGES[$si->status] ?? 'secondary' }}">{{ ucfirst($si->status) }}</span>
                        </div>
                        @empty
                        <div class="list-group-item text-muted text-center">No recent stock ins</div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

        {{-- Recent Stock Outs --}}
        <div class="col-md-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">
                    <h6 class="mb-0"><i class="bi bi-box-arrow-up text-danger me-1"></i> Recent Stock Out</h6>
                </div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush">
                        @forelse($recentStockOuts as $so)
                        <div class="list-group-item d-flex justify-content-between align-items-center">
                            <div>
                                <strong>{{ $so->stock_out_no }}</strong>
                                <br><small class="text-muted">{{ \App\Models\StockOut::OUT_TYPE_OPTIONS[$so->out_type] ?? $so->out_type }} &middot; {{ $so->stock_out_date?->format('d M Y') }}</small>
                            </div>
                            <span class="badge bg-{{ \App\Models\StockOut::STATUS_BADGES[$so->status] ?? 'secondary' }}">{{ ucfirst($so->status) }}</span>
                        </div>
                        @empty
                        <div class="list-group-item text-muted text-center">No recent stock outs</div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

        {{-- Recent Replacements --}}
        <div class="col-md-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">
                    <h6 class="mb-0"><i class="bi bi-arrow-left-right text-warning me-1"></i> Recent Replacements</h6>
                </div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush">
                        @forelse($recentReplacements as $rpl)
                        <div class="list-group-item d-flex justify-content-between align-items-center">
                            <div>
                                <strong>{{ $rpl->replacement_no }}</strong>
                                <br><small class="text-muted">{{ $rpl->old_serial_no }} &rarr; {{ $rpl->new_serial_no }}</small>
                            </div>
                            <span class="badge bg-{{ \App\Models\Replacement::STATUS_BADGES[$rpl->status] ?? 'secondary' }}">{{ ucfirst($rpl->status) }}</span>
                        </div>
                        @empty
                        <div class="list-group-item text-muted text-center">No recent replacements</div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Initialize Router DataTable
    window.routerTable = $('#routerTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route("admin.inventory-management.router-datatable") }}',
            data: function(d) {
                d.status   = $('#filterRouterStatus').val();
                d.depot_id = $('#filterRouterDepot').val();
            }
        },
        columns: [
            { data: 'serial_no', name: 'serial_no' },
            { data: 'model_name', name: 'model_name' },
            { data: 'category', name: 'category' },
            { data: 'status', name: 'status' },
            { data: 'location', name: 'location' },
            { data: 'action', name: 'action', orderable: false, searchable: false }
        ],
        order: [[0, 'asc']],
        pageLength: 10,
        language: { emptyTable: 'No routers found in inventory' }
    });

    // Initialize Accessories DataTable
    window.accessoryTable = $('#accessoryTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route("admin.inventory-management.accessory-datatable") }}',
            data: function(d) {
                d.depot_id = $('#filterAccDepot').val();
            }
        },
        columns: [
            { data: 'model_name', name: 'model_name' },
            { data: 'category', name: 'category' },
            { data: 'location', name: 'location' },
            { data: 'on_hand', name: 'on_hand' },
            { data: 'reserved', name: 'reserved' },
            { data: 'available', name: 'available' }
        ],
        order: [[0, 'asc']],
        pageLength: 10,
        language: { emptyTable: 'No accessories found in inventory' }
    });

    // Filter handlers
    $('#filterRouterStatus, #filterRouterDepot').on('change', function() {
        window.routerTable.ajax.reload();
    });
    $('#filterAccDepot').on('change', function() {
        window.accessoryTable.ajax.reload();
    });
});
</script>
@endpush
