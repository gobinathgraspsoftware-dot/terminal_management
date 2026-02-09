@extends('layouts.app')

@section('title', 'My Inventory')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-1"><i class="bi bi-box-seam"></i> My Inventory</h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('technician.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item active">My Inventory</li>
                </ol>
            </nav>
        </div>
        <div>
            <a href="{{ route('technician.inventory.return-request') }}" class="btn btn-primary">
                <i class="bi bi-arrow-return-left"></i> Request Return
            </a>
            <a href="{{ route('technician.inventory.summary') }}" class="btn btn-outline-secondary">
                <i class="bi bi-bar-chart"></i> Summary
            </a>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="row mb-4">
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-1">Total Items</h6>
                            <h3 class="mb-0">{{ $summary['total_items'] }}</h3>
                        </div>
                        <div class="text-primary">
                            <i class="bi bi-boxes fs-1"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3 col-sm-6 mb-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-1">Issued</h6>
                            <h3 class="mb-0 text-success">{{ $summary['issued_items'] }}</h3>
                        </div>
                        <div class="text-success">
                            <i class="bi bi-check-circle fs-1"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3 col-sm-6 mb-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-1">Deployed</h6>
                            <h3 class="mb-0 text-info">{{ $summary['deployed_items'] }}</h3>
                        </div>
                        <div class="text-info">
                            <i class="bi bi-geo-alt fs-1"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3 col-sm-6 mb-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-1">Faulty</h6>
                            <h3 class="mb-0 text-danger">{{ $summary['faulty_items'] }}</h3>
                        </div>
                        <div class="text-danger">
                            <i class="bi bi-exclamation-triangle fs-1"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Inventory by Model -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white">
            <h5 class="mb-0"><i class="bi bi-grid"></i> Inventory by Model</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Category</th>
                            <th>Model</th>
                            <th class="text-center">Total</th>
                            <th class="text-center">Issued</th>
                            <th class="text-center">Deployed</th>
                            <th class="text-center">Faulty</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($inventoryByModel as $item)
                        <tr>
                            <td>{{ $item->category_name }}</td>
                            <td><strong>{{ $item->model_name }}</strong></td>
                            <td class="text-center">
                                <span class="badge bg-primary">{{ $item->total_quantity }}</span>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-success">{{ $item->issued_qty }}</span>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-info">{{ $item->deployed_qty }}</span>
                            </td>
                            <td class="text-center">
                                @if($item->faulty_qty > 0)
                                <span class="badge bg-danger">{{ $item->faulty_qty }}</span>
                                @else
                                <span class="badge bg-secondary">0</span>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">
                                <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                                No inventory items found
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- All Inventory Items (DataTable) -->
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white">
            <div class="row align-items-center">
                <div class="col">
                    <h5 class="mb-0"><i class="bi bi-list-ul"></i> All Inventory Items</h5>
                </div>
                <div class="col-auto">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text"><i class="bi bi-funnel"></i></span>
                        <select id="statusFilter" class="form-select form-select-sm">
                            <option value="">All Status</option>
                            <option value="issued">Issued</option>
                            <option value="deployed">Deployed</option>
                            <option value="faulty">Faulty</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table id="inventoryTable" class="table table-striped table-hover" style="width:100%">
                    <thead>
                        <tr>
                            <th>Serial No</th>
                            <th>Model</th>
                            <th>Category</th>
                            <th>Status</th>
                            <th>Received Date</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- DataTable will populate this -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Recent Movements -->
    <div class="card border-0 shadow-sm mt-4">
        <div class="card-header bg-white">
            <h5 class="mb-0"><i class="bi bi-clock-history"></i> Recent Stock Movements</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Transaction No</th>
                            <th>Type</th>
                            <th>Model</th>
                            <th>Serial No</th>
                            <th>Qty</th>
                            <th>Remarks</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recentMovements as $movement)
                        <tr>
                            <td>{{ \Carbon\Carbon::parse($movement->transaction_date)->format('Y-m-d') }}</td>
                            <td><small>{{ $movement->transaction_no }}</small></td>
                            <td>
                                @if($movement->transaction_type == 'issue_to_tech')
                                    <span class="badge bg-success">Issue</span>
                                @elseif($movement->transaction_type == 'return_from_tech')
                                    <span class="badge bg-warning">Return</span>
                                @else
                                    <span class="badge bg-secondary">{{ ucfirst(str_replace('_', ' ', $movement->transaction_type)) }}</span>
                                @endif
                            </td>
                            <td>{{ $movement->model_name }}</td>
                            <td><code>{{ $movement->serial_no ?? 'N/A' }}</code></td>
                            <td>{{ $movement->quantity > 0 ? '+' : '' }}{{ $movement->quantity }}</td>
                            <td><small>{{ $movement->remarks ?? '-' }}</small></td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted">No recent movements</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Initialize DataTable
    var table = $('#inventoryTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route("technician.inventory.index") }}',
            data: function(d) {
                d.status = $('#statusFilter').val();
            }
        },
        columns: [
            { data: 'serial_no', name: 'serial_no' },
            { data: 'model_name', name: 'model.model_name' },
            { data: 'category_name', name: 'category_name' },
            { 
                data: 'status', 
                name: 'status',
                render: function(data) {
                    const badges = {
                        'issued': 'success',
                        'deployed': 'info',
                        'faulty': 'danger'
                    };
                    const badge = badges[data] || 'secondary';
                    return `<span class="badge bg-${badge}">${data.toUpperCase()}</span>`;
                }
            },
            { data: 'received_date', name: 'created_at' },
            { 
                data: 'action', 
                name: 'action', 
                orderable: false, 
                searchable: false,
                className: 'text-center'
            }
        ],
        order: [[4, 'desc']],
        pageLength: 25,
        responsive: true,
        language: {
            processing: '<i class="bi bi-hourglass-split"></i> Loading...',
            emptyTable: 'No inventory items found',
            zeroRecords: 'No matching items found'
        }
    });

    // Status filter change
    $('#statusFilter').on('change', function() {
        table.draw();
    });

    // Refresh button
    $('#refreshTable').on('click', function() {
        table.ajax.reload(null, false);
    });
});
</script>
@endpush

@push('styles')
<style>
    .card {
        border-radius: 10px;
    }
    
    .card-header {
        border-bottom: 2px solid #f0f0f0;
        padding: 1rem 1.25rem;
    }
    
    @media (max-width: 768px) {
        .table-responsive {
            font-size: 0.875rem;
        }
        
        .card-body {
            padding: 0.75rem;
        }
    }
</style>
@endpush
