@extends('layouts.app')
@section('title', 'My Inventory')

@section('content')
<div class="container-fluid">
    <div class="mb-4">
        <h4 class="mb-1">My Inventory</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('technician.dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item active">My Inventory</li>
            </ol>
        </nav>
    </div>

    {{-- Summary Cards --}}
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center">
                    <div class="text-primary fs-3 fw-bold">{{ $summary['total_items'] }}</div>
                    <small class="text-muted">Total Items Assigned</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center">
                    <div class="text-info fs-3 fw-bold">{{ $summary['router_count'] }}</div>
                    <small class="text-muted">Routers</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center">
                    <div class="text-secondary fs-3 fw-bold">{{ $summary['accessory_count'] }}</div>
                    <small class="text-muted">Accessories</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center">
                    <div class="text-success fs-3 fw-bold">{{ $summary['movement_count'] }}</div>
                    <small class="text-muted">Total Movements</small>
                </div>
            </div>
        </div>
    </div>

    {{-- Assigned Items Table --}}
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white fw-semibold">
            <i class="bi bi-box-seam me-2"></i>Items Assigned to Me
        </div>
        <div class="card-body">
            @if($assignedItems->count())
            <div class="table-responsive">
                <table id="techInventoryTable" class="table table-hover table-sm align-middle" style="width:100%">
                    <thead class="table-light">
                        <tr>
                            <th width="50">#</th>
                            <th>Item Code</th>
                            <th>Item Name</th>
                            <th>Type</th>
                            <th>Terminal ID</th>
                            <th>Category</th>
                            <th class="text-end">Qty</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($assignedItems as $index => $balance)
                        @php $item = $balance->inventoryItem; @endphp
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td><code>{{ $item->item_code ?? 'N/A' }}</code></td>
                            <td>{{ $item->item_name ?? 'N/A' }}</td>
                            <td>{!! $item->getTypeBadge() !!}</td>
                            <td>{{ $item->serial_number ?? '-' }}</td>
                            <td>{{ $item->jobCategory->category_name ?? 'N/A' }}</td>
                            <td class="text-end fw-semibold">{{ $balance->quantity }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @else
            <div class="text-center py-5 text-muted">
                <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                <p>No items currently assigned to you.</p>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    @if($assignedItems->count() > 10)
    $('#techInventoryTable').DataTable({
        processing: false,
        serverSide: false,
        pageLength: 25,
        order: [[1, 'asc']],
    });
    @endif
});
</script>
@endpush
