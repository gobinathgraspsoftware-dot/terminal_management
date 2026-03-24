@extends('layouts.app')
@section('title', 'My Inventory')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1"><i class="bi bi-box-seam me-2"></i>My Inventory</h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('technician.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item active">My Inventory</li>
                </ol>
            </nav>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center">
                    <div class="fs-3 fw-bold text-primary">{{ $stats['total_items'] }}</div>
                    <small class="text-muted">Total Items</small>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center">
                    <div class="fs-3 fw-bold text-success">{{ $stats['total_qty'] }}</div>
                    <small class="text-muted">Total Quantity</small>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center">
                    <div class="fs-3 fw-bold text-info">{{ $stats['routers'] }}</div>
                    <small class="text-muted">Routers</small>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center">
                    <div class="fs-3 fw-bold text-warning">{{ $stats['accessories'] }}</div>
                    <small class="text-muted">Accessories Qty</small>
                </div>
            </div>
        </div>
    </div>

    <!-- My Stock Table -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h6 class="mb-0"><i class="bi bi-box-seam me-2"></i>Current Stock</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table id="my-stock-table" class="table table-hover align-middle w-100">
                    <thead class="table-light">
                        <tr>
                            <th width="5%">#</th>
                            <th>Item Code</th>
                            <th>Item Name</th>
                            <th>Category</th>
                            <th>Type</th>
                            <th>Terminal ID</th>
                            <th class="text-center">Quantity</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($myStock as $index => $balance)
                        @php $item = $balance->inventoryItem; @endphp
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td><code>{{ $item->item_code ?? 'N/A' }}</code></td>
                            <td>{{ $item->item_name ?? 'N/A' }}</td>
                            <td>{{ $item->jobCategory->category_name ?? 'N/A' }}</td>
                            <td>
                                <span class="badge bg-{{ $item->item_type === 'router' ? 'primary' : 'info' }}">
                                    {{ ucfirst($item->item_type ?? '-') }}
                                </span>
                            </td>
                            <td>{{ $item->serial_number ?? '-' }}</td>
                            <td class="text-center fw-bold">{{ $balance->quantity }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">
                                <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                                No stock assigned to you currently.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Recent Movements -->
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white">
            <h6 class="mb-0"><i class="bi bi-arrow-left-right me-2"></i>Recent Movements</h6>
        </div>
        <div class="card-body">
            @if($recentMovements->count())
            <div class="table-responsive">
                <table class="table table-sm table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>Movement #</th>
                            <th>Type</th>
                            <th>Item</th>
                            <th class="text-center">Qty</th>
                            <th>Performed By</th>
                            <th>Date</th>
                            <th>Remarks</th>
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
                            <td>{{ $m->inventoryItem->item_name ?? 'N/A' }}</td>
                            <td class="text-center fw-bold {{ $m->quantity > 0 ? 'text-success' : 'text-danger' }}">
                                {{ $m->quantity > 0 ? '+' : '' }}{{ $m->quantity }}
                            </td>
                            <td>{{ $m->performedBy->name ?? 'N/A' }}</td>
                            <td>{{ $m->movement_date?->format('d M Y') }}</td>
                            <td>{{ \Illuminate\Support\Str::limit($m->remarks, 40) ?? '-' }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @else
            <div class="text-center text-muted py-4">
                <i class="bi bi-arrow-left-right fs-1 d-block mb-2"></i>
                No stock movements found.
            </div>
            @endif
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    $('#my-stock-table').DataTable({
        pageLength: 25,
        order: [[1, 'asc']],
        language: { emptyTable: 'No stock assigned to you.' }
    });
});
</script>
@endpush
