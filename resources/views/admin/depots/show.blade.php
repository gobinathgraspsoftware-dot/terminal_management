@extends('layouts.app')

@section('title', 'Depot Details')

@section('content')
<div class="container-fluid">
    <div class="row mb-3">
        <div class="col-md-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="mb-1">{{ $depot->depot_name }}</h2>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-0">
                            <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('admin.depots.index') }}">Depots</a></li>
                            <li class="breadcrumb-item active">{{ $depot->depot_code }}</li>
                        </ol>
                    </nav>
                </div>
                <div>
                    @can('edit_depots')
                    <a href="{{ route('admin.depots.edit', $depot->id) }}" class="btn btn-primary">
                        <i class="bi bi-pencil me-1"></i> Edit Depot
                    </a>
                    @endcan
                    <a href="{{ route('admin.depots.index') }}" class="btn btn-secondary">
                        <i class="bi bi-arrow-left me-1"></i> Back
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Depot Info Card -->
    <div class="row mb-4">
        <div class="col-md-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Depot Information</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-sm table-borderless">
                                <tr>
                                    <th width="40%">Depot Code:</th>
                                    <td><span class="badge bg-secondary">{{ $depot->depot_code }}</span></td>
                                </tr>
                                <tr>
                                    <th>Depot Type:</th>
                                    <td>
                                        @php
                                            $badges = [
                                                'main' => '<span class="badge bg-danger">Main Warehouse</span>',
                                                'regional' => '<span class="badge bg-primary">Regional Depot</span>',
                                                'technician' => '<span class="badge bg-info">Technician Depot</span>',
                                            ];
                                        @endphp
                                        {!! $badges[$depot->depot_type] ?? '' !!}
                                    </td>
                                </tr>
                                <tr>
                                    <th>Status:</th>
                                    <td>
                                        <span class="badge bg-{{ $depot->status === 'active' ? 'success' : 'danger' }}">
                                            {{ ucfirst($depot->status) }}
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Default Depot:</th>
                                    <td>
                                        @if($depot->is_default)
                                            <span class="badge bg-success"><i class="bi bi-star-fill"></i> Yes</span>
                                        @else
                                            <span class="text-muted">No</span>
                                        @endif
                                    </td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-sm table-borderless">
                                <tr>
                                    <th width="40%">Address:</th>
                                    <td>{{ $depot->address ?: '-' }}</td>
                                </tr>
                                <tr>
                                    <th>City/State:</th>
                                    <td>
                                        @php
                                            $location = array_filter([$depot->city, $depot->state]);
                                        @endphp
                                        {{ implode(', ', $location) ?: '-' }}
                                    </td>
                                </tr>
                                <tr>
                                    <th>Postcode:</th>
                                    <td>{{ $depot->postcode ?: '-' }}</td>
                                </tr>
                                <tr>
                                    <th>Country:</th>
                                    <td>{{ $depot->country ?: '-' }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>
                    
                    @if($depot->pic_name)
                    <hr>
                    <div class="row">
                        <div class="col-md-12">
                            <h6 class="text-muted mb-2">Person In Charge</h6>
                            <table class="table table-sm table-borderless">
                                <tr>
                                    <th width="20%">Name:</th>
                                    <td>{{ $depot->pic_name }}</td>
                                </tr>
                                <tr>
                                    <th>Phone:</th>
                                    <td>{{ $depot->pic_phone ?: '-' }}</td>
                                </tr>
                                <tr>
                                    <th>Email:</th>
                                    <td>{{ $depot->pic_email ?: '-' }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>
                    @endif

                    @if($depot->notes)
                    <hr>
                    <div class="row">
                        <div class="col-md-12">
                            <h6 class="text-muted mb-2">Notes</h6>
                            <p class="mb-0">{{ $depot->notes }}</p>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Stock Summary Card -->
        <div class="col-md-4">
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Stock Summary</h5>
                    <!-- AJAX Refresh Button -->
                    <button type="button" class="btn btn-sm btn-outline-primary" id="refreshStockBtn">
                        <i class="bi bi-arrow-clockwise"></i> Refresh
                    </button>
                </div>
                <div class="card-body" id="stockSummaryContent">
                    <div class="text-center mb-3">
                        <h2 class="mb-0 text-primary" id="totalQuantity">{{ number_format($stockSummary['total_quantity'], 0) }}</h2>
                        <small class="text-muted">Total Units</small>
                    </div>
                    <hr>
                    <div class="row text-center">
                        <div class="col-6">
                            <h5 class="mb-0 text-success" id="totalAvailable">{{ number_format($stockSummary['total_available'], 0) }}</h5>
                            <small class="text-muted">Available</small>
                        </div>
                        <div class="col-6">
                            <h5 class="mb-0 text-warning" id="totalReserved">{{ number_format($stockSummary['total_reserved'], 0) }}</h5>
                            <small class="text-muted">Reserved</small>
                        </div>
                    </div>
                    <hr>
                    <div class="text-center">
                        <h5 class="mb-0" id="totalModels">{{ $stockSummary['total_models'] }}</h5>
                        <small class="text-muted">Different Models</small>
                    </div>
                </div>
            </div>

            <!-- By Category -->
            @if($stockSummary['by_category']->isNotEmpty())
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">
                    <h6 class="mb-0">Stock by Category</h6>
                </div>
                <div class="card-body" id="categoryContent">
                    @foreach($stockSummary['by_category'] as $category => $data)
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="text-muted">{{ $category }}</span>
                        <span class="badge bg-primary">{{ number_format($data['quantity'], 0) }} units</span>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif
        </div>
    </div>

    <!-- Current Stock Levels -->
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Current Stock Levels</h5>
                </div>
                <div class="card-body">
                    @if($stockSummary['items']->isEmpty())
                        <div class="alert alert-info mb-0">
                            <i class="bi bi-info-circle me-1"></i> No stock items in this depot.
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th width="20%">Model</th>
                                        <th width="15%">Category</th>
                                        <th width="15%" class="text-center">On Hand</th>
                                        <th width="15%" class="text-center">Reserved</th>
                                        <th width="15%" class="text-center">Available</th>
                                        <th width="20%">Last Movement</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($stockSummary['items'] as $item)
                                    <tr>
                                        <td>
                                            <strong>{{ $item->model->model_name }}</strong><br>
                                            <small class="text-muted">{{ $item->model->model_code }}</small>
                                        </td>
                                        <td>
                                            <span class="badge bg-secondary">
                                                {{ $item->model->category->category_name ?? 'N/A' }}
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <strong>{{ number_format($item->quantity_on_hand, 0) }}</strong>
                                        </td>
                                        <td class="text-center">
                                            <span class="text-warning">{{ number_format($item->quantity_reserved, 0) }}</span>
                                        </td>
                                        <td class="text-center">
                                            <span class="text-success">{{ number_format($item->quantity_available, 0) }}</span>
                                        </td>
                                        <td>
                                            <small class="text-muted">
                                                {{ $item->last_movement_date ? \Carbon\Carbon::parse($item->last_movement_date)->format('d M Y') : '-' }}
                                            </small>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Stock Movements -->
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Recent Stock Movements</h5>
                    <!-- AJAX Load More Button -->
                    <button type="button" class="btn btn-sm btn-outline-primary" id="loadMoreMovementsBtn">
                        <i class="bi bi-arrow-down-circle"></i> Load More (20)
                    </button>
                </div>
                <div class="card-body">
                    <div id="movementsContent">
                        @if($recentMovements->isEmpty())
                            <div class="alert alert-info mb-0">
                                <i class="bi bi-info-circle me-1"></i> No recent movements.
                            </div>
                        @else
                            <div class="table-responsive">
                                <table class="table table-sm table-hover" id="movementsTable">
                                    <thead>
                                        <tr>
                                            <th width="12%">Date</th>
                                            <th width="15%">Type</th>
                                            <th width="20%">Model</th>
                                            <th width="10%" class="text-center">Qty</th>
                                            <th width="15%">From</th>
                                            <th width="15%">To</th>
                                            <th width="13%">Reference</th>
                                        </tr>
                                    </thead>
                                    <tbody id="movementsTableBody">
                                        @foreach($recentMovements as $movement)
                                        <tr>
                                            <td>
                                                <small>{{ \Carbon\Carbon::parse($movement->transaction_date)->format('d M Y') }}</small>
                                            </td>
                                            <td>
                                                <span class="badge bg-info">{{ ucwords(str_replace('_', ' ', $movement->transaction_type)) }}</span>
                                            </td>
                                            <td>
                                                <small>{{ $movement->model->model_name ?? 'N/A' }}</small>
                                            </td>
                                            <td class="text-center">
                                                <strong class="{{ $movement->quantity > 0 ? 'text-success' : 'text-danger' }}">
                                                    {{ $movement->quantity > 0 ? '+' : '' }}{{ number_format($movement->quantity, 0) }}
                                                </strong>
                                            </td>
                                            <td>
                                                <small class="text-muted">
                                                    {{ ucfirst($movement->from_location_type ?? '-') }}
                                                </small>
                                            </td>
                                            <td>
                                                <small class="text-muted">
                                                    {{ ucfirst($movement->to_location_type ?? '-') }}
                                                </small>
                                            </td>
                                            <td>
                                                <small class="text-muted">{{ $movement->reference_type ?? '-' }}</small>
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>
                    <div id="movementsLoading" class="text-center py-3" style="display: none;">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="mt-2 text-muted">Loading movements...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Assigned Technicians (for Regional Depots) -->
    @if($depot->depot_type === 'regional' && !empty($technicians))
    <div class="row">
        <div class="col-md-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Assigned Technicians</h5>
                </div>
                <div class="card-body">
                    @if(count($technicians) === 0)
                        <div class="alert alert-info mb-0">
                            <i class="bi bi-info-circle me-1"></i> No technicians assigned to this depot.
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th width="30%">Technician</th>
                                        <th width="20%">Email</th>
                                        <th width="15%">Phone</th>
                                        <th width="20%">Stock Items</th>
                                        <th width="15%">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($technicians as $tech)
                                    <tr>
                                        <td>{{ $tech->name }}</td>
                                        <td>{{ $tech->email }}</td>
                                        <td>{{ $tech->phone ?? '-' }}</td>
                                        <td>
                                            @php
                                                // Get stock balances safely
                                                $stockCount = 0;
                                                try {
                                                    if (method_exists($tech, 'stockBalances')) {
                                                        $stockCount = $tech->stockBalances()->count();
                                                    } else {
                                                        // Direct query if relationship doesn't exist
                                                        $stockCount = \App\Models\StockBalance::where('location_type', 'technician')
                                                            ->where('location_id', $tech->id)
                                                            ->count();
                                                    }
                                                } catch (\Exception $e) {
                                                    $stockCount = 0;
                                                }
                                            @endphp
                                            <span class="badge bg-primary">
                                                {{ $stockCount }} items
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge bg-{{ $tech->status === 'active' ? 'success' : 'secondary' }}">
                                                {{ ucfirst($tech->status ?? 'Active') }}
                                            </span>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    const depotId = {{ $depot->id }};
    
    // ========================================
    // AJAX: Refresh Stock Summary
    // ========================================
    $('#refreshStockBtn').on('click', function() {
        let btn = $(this);
        let originalHtml = btn.html();
        
        // Show loading
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span>');
        
        $.ajax({
            url: "{{ route('admin.depots.stock-summary', $depot->id) }}",
            type: 'GET',
            dataType: 'json',
            success: function(response) {
                console.log('Stock Summary Response:', response);
                
                if (response.success && response.data) {
                    // Update stock summary values
                    $('#totalQuantity').text(response.data.total_quantity.toLocaleString());
                    $('#totalAvailable').text(response.data.total_available.toLocaleString());
                    $('#totalReserved').text(response.data.total_reserved.toLocaleString());
                    $('#totalModels').text(response.data.total_models);
                    
                    // Show success message
                    if (typeof toastr !== 'undefined') {
                        toastr.success('Stock summary refreshed!');
                    }
                } else {
                    if (typeof toastr !== 'undefined') {
                        toastr.error('Failed to refresh stock summary.');
                    }
                }
            },
            error: function(xhr, status, error) {
                console.error('Stock Summary Error:', xhr);
                if (typeof toastr !== 'undefined') {
                    toastr.error('Error loading stock summary: ' + (xhr.responseJSON?.message || error));
                } else {
                    alert('Error loading stock summary: ' + error);
                }
            },
            complete: function() {
                // Restore button
                btn.prop('disabled', false).html(originalHtml);
            }
        });
    });
    
    // ========================================
    // AJAX: Load More Movements
    // ========================================
    $('#loadMoreMovementsBtn').on('click', function() {
        let btn = $(this);
        let originalHtml = btn.html();
        
        // Show loading
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Loading...');
        $('#movementsLoading').show();
        
        $.ajax({
            url: "{{ route('admin.depots.movements', $depot->id) }}",
            type: 'GET',
            data: {
                limit: 20  // Load 20 more movements
            },
            dataType: 'json',
            success: function(response) {
                console.log('Movements Response:', response);
                
                if (response.success && response.data) {
                    let movements = response.data;
                    let tbody = $('#movementsTableBody');
                    
                    // Clear current rows
                    tbody.empty();
                    
                    if (movements.length === 0) {
                        $('#movementsContent').html('<div class="alert alert-info mb-0"><i class="bi bi-info-circle me-1"></i> No movements found.</div>');
                    } else {
                        // Add new rows
                        movements.forEach(function(movement) {
                            let qtyClass = movement.quantity > 0 ? 'text-success' : 'text-danger';
                            let qtySign = movement.quantity > 0 ? '+' : '';
                            
                            let row = `
                                <tr>
                                    <td><small>${formatDate(movement.transaction_date)}</small></td>
                                    <td><span class="badge bg-info">${formatType(movement.transaction_type)}</span></td>
                                    <td><small>${movement.model?.model_name || 'N/A'}</small></td>
                                    <td class="text-center"><strong class="${qtyClass}">${qtySign}${parseFloat(movement.quantity).toLocaleString()}</strong></td>
                                    <td><small class="text-muted">${ucfirst(movement.from_location_type || '-')}</small></td>
                                    <td><small class="text-muted">${ucfirst(movement.to_location_type || '-')}</small></td>
                                    <td><small class="text-muted">${movement.reference_type || '-'}</small></td>
                                </tr>
                            `;
                            tbody.append(row);
                        });
                        
                        if (typeof toastr !== 'undefined') {
                            toastr.success(`Loaded ${movements.length} movements!`);
                        }
                    }
                } else {
                    if (typeof toastr !== 'undefined') {
                        toastr.error('Failed to load movements.');
                    }
                }
            },
            error: function(xhr, status, error) {
                console.error('Movements Error:', xhr);
                if (typeof toastr !== 'undefined') {
                    toastr.error('Error loading movements: ' + (xhr.responseJSON?.message || error));
                } else {
                    alert('Error loading movements: ' + error);
                }
            },
            complete: function() {
                // Restore button and hide loading
                btn.prop('disabled', false).html(originalHtml);
                $('#movementsLoading').hide();
            }
        });
    });
    
    // ========================================
    // Helper Functions
    // ========================================
    function formatDate(dateString) {
        if (!dateString) return '-';
        const date = new Date(dateString);
        const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        return `${date.getDate()} ${months[date.getMonth()]} ${date.getFullYear()}`;
    }
    
    function formatType(type) {
        if (!type) return '';
        return type.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
    }
    
    function ucfirst(str) {
        if (!str) return '';
        return str.charAt(0).toUpperCase() + str.slice(1);
    }
});
</script>
@endpush
