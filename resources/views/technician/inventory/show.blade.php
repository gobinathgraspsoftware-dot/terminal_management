@extends('layouts.app')

@section('title', 'Serial Details - ' . $serial->serial_no)

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-1"><i class="bi bi-upc-scan"></i> Serial Details</h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('technician.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('technician.inventory.index') }}">My Inventory</a></li>
                    <li class="breadcrumb-item active">{{ $serial->serial_no }}</li>
                </ol>
            </nav>
        </div>
        <div>
            <a href="{{ route('technician.inventory.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Back to Inventory
            </a>
            <a href="{{ route('technician.inventory.return-request') }}?serial_id={{ $serial->id }}" class="btn btn-danger">
                <i class="bi bi-arrow-return-left"></i> Request Return
            </a>
        </div>
    </div>

    <div class="row">
        <!-- Serial Information -->
        <div class="col-md-6 mb-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="bi bi-info-circle"></i> Serial Information</h5>
                </div>
                <div class="card-body">
                    <table class="table table-borderless">
                        <tbody>
                            <tr>
                                <th style="width: 40%;">Serial Number:</th>
                                <td><strong class="text-primary">{{ $serial->serial_no }}</strong></td>
                            </tr>
                            <tr>
                                <th>Model:</th>
                                <td>{{ $serial->model->model_name ?? 'N/A' }}</td>
                            </tr>
                            <tr>
                                <th>Category:</th>
                                <td>{{ $serial->model->category->category_name ?? 'N/A' }}</td>
                            </tr>
                            <tr>
                                <th>Status:</th>
                                <td>
                                    @if($serial->status == 'issued')
                                        <span class="badge bg-success">ISSUED</span>
                                    @elseif($serial->status == 'deployed')
                                        <span class="badge bg-info">DEPLOYED</span>
                                    @elseif($serial->status == 'faulty')
                                        <span class="badge bg-danger">FAULTY</span>
                                    @else
                                        <span class="badge bg-secondary">{{ strtoupper($serial->status) }}</span>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <th>Current Location:</th>
                                <td>
                                    <i class="bi bi-person-badge"></i> With Me
                                    @if($serial->current_location_type == 'technician')
                                        (Technician)
                                    @endif
                                </td>
                            </tr>
                            @if($serial->batch_no)
                            <tr>
                                <th>Batch Number:</th>
                                <td><code>{{ $serial->batch_no }}</code></td>
                            </tr>
                            @endif
                            <tr>
                                <th>Received Date:</th>
                                <td>{{ $serial->created_at ? $serial->created_at->format('d M Y, h:i A') : 'N/A' }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Procurement Information -->
        <div class="col-md-6 mb-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="bi bi-receipt"></i> Procurement Information</h5>
                </div>
                <div class="card-body">
                    <table class="table table-borderless">
                        <tbody>
                            @if($serial->grn)
                            <tr>
                                <th style="width: 40%;">GRN Number:</th>
                                <td><code>{{ $serial->grn->grn_no }}</code></td>
                            </tr>
                            <tr>
                                <th>GRN Date:</th>
                                <td>{{ \Carbon\Carbon::parse($serial->grn->grn_date)->format('d M Y') }}</td>
                            </tr>
                            <tr>
                                <th>Vendor:</th>
                                <td>{{ $serial->grn->vendor->vendor_name ?? 'N/A' }}</td>
                            </tr>
                            @endif
                            
                            @if($serial->purchaseOrder)
                            <tr>
                                <th>PO Number:</th>
                                <td><code>{{ $serial->purchaseOrder->po_no }}</code></td>
                            </tr>
                            <tr>
                                <th>PO Date:</th>
                                <td>{{ \Carbon\Carbon::parse($serial->purchaseOrder->po_date)->format('d M Y') }}</td>
                            </tr>
                            @endif

                            @if($serial->unit_cost)
                            <tr>
                                <th>Unit Cost:</th>
                                <td><strong>₹{{ number_format($serial->unit_cost, 2) }}</strong></td>
                            </tr>
                            @endif

                            @if($serial->warranty_expiry)
                            <tr>
                                <th>Warranty Expiry:</th>
                                <td>
                                    {{ \Carbon\Carbon::parse($serial->warranty_expiry)->format('d M Y') }}
                                    @if(\Carbon\Carbon::parse($serial->warranty_expiry)->isFuture())
                                        <span class="badge bg-success">Active</span>
                                    @else
                                        <span class="badge bg-danger">Expired</span>
                                    @endif
                                </td>
                            </tr>
                            @endif
                        </tbody>
                    </table>

                    @if(!$serial->grn && !$serial->purchaseOrder)
                    <div class="alert alert-info mb-0">
                        <i class="bi bi-info-circle"></i> Procurement information not available
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Movement History -->
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white">
            <h5 class="mb-0"><i class="bi bi-clock-history"></i> Movement History</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Transaction No</th>
                            <th>Type</th>
                            <th>From</th>
                            <th>To</th>
                            <th>Qty</th>
                            <th>Remarks</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($movements as $movement)
                        <tr>
                            <td>{{ \Carbon\Carbon::parse($movement->transaction_date)->format('d M Y') }}</td>
                            <td><small><code>{{ $movement->transaction_no }}</code></small></td>
                            <td>
                                @php
                                    $typeLabels = [
                                        'grn_in' => 'GRN Received',
                                        'issue_to_tech' => 'Issued to Tech',
                                        'return_from_tech' => 'Returned from Tech',
                                        'transfer' => 'Transfer',
                                        'install' => 'Installed',
                                        'replacement_out' => 'Replaced Out',
                                        'replacement_in' => 'Replaced In',
                                        'wastage' => 'Wastage',
                                        'adjustment' => 'Adjustment'
                                    ];
                                    $typeColors = [
                                        'grn_in' => 'primary',
                                        'issue_to_tech' => 'success',
                                        'return_from_tech' => 'warning',
                                        'transfer' => 'info',
                                        'install' => 'dark',
                                        'replacement_out' => 'danger',
                                        'replacement_in' => 'success',
                                        'wastage' => 'danger',
                                        'adjustment' => 'secondary'
                                    ];
                                @endphp
                                <span class="badge bg-{{ $typeColors[$movement->transaction_type] ?? 'secondary' }}">
                                    {{ $typeLabels[$movement->transaction_type] ?? ucfirst(str_replace('_', ' ', $movement->transaction_type)) }}
                                </span>
                            </td>
                            <td>
                                <small>
                                    @if($movement->from_location_type)
                                        {{ ucfirst($movement->from_location_type) }}
                                        @if($movement->from_location_id)
                                            (ID: {{ $movement->from_location_id }})
                                        @endif
                                    @else
                                        -
                                    @endif
                                </small>
                            </td>
                            <td>
                                <small>
                                    @if($movement->to_location_type)
                                        {{ ucfirst($movement->to_location_type) }}
                                        @if($movement->to_location_id)
                                            (ID: {{ $movement->to_location_id }})
                                        @endif
                                    @else
                                        -
                                    @endif
                                </small>
                            </td>
                            <td>
                                @if($movement->quantity > 0)
                                    <span class="text-success">+{{ $movement->quantity }}</span>
                                @else
                                    <span class="text-danger">{{ $movement->quantity }}</span>
                                @endif
                            </td>
                            <td><small>{{ $movement->remarks ?? '-' }}</small></td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">
                                <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                                No movement history available
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Actions -->
    <div class="card border-0 shadow-sm mt-4">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="mb-0">Need to return this item?</h6>
                    <small class="text-muted">Request a return to depot</small>
                </div>
                <div>
                    <a href="{{ route('technician.inventory.return-request') }}?serial_id={{ $serial->id }}" 
                       class="btn btn-danger">
                        <i class="bi bi-arrow-return-left"></i> Request Return
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .card {
        border-radius: 10px;
    }
    
    .card-header {
        border-top-left-radius: 10px;
        border-top-right-radius: 10px;
    }
    
    .table th {
        font-weight: 600;
        color: #6c757d;
    }
    
    @media (max-width: 768px) {
        .table-responsive {
            font-size: 0.875rem;
        }
    }
</style>
@endpush
