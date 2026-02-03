@extends('layouts.app')

@section('title', 'Serial Detail - ' . $serial->serial_no)

@section('content')
<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h1><i class="bi bi-upc-scan me-2"></i>Serial: {{ $serial->serial_no }}</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.inventory-serials.index') }}">Serial Tracking</a></li>
                    <li class="breadcrumb-item active">{{ $serial->serial_no }}</li>
                </ol>
            </nav>
        </div>
        <div class="d-flex gap-2">
            @can('edit_inventory')
            <a href="{{ route('admin.inventory-serials.edit', $serial->id) }}" class="btn btn-primary">
                <i class="bi bi-pencil me-1"></i> Edit
            </a>
            @endcan
            <a href="{{ route('admin.inventory-serials.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Back
            </a>
        </div>
    </div>
</div>

<!-- Status & Quick Info Bar -->
<div class="row mb-4">
    <div class="col-md-3 mb-3">
        <div class="stats-card text-center">
            <div class="stats-label">Current Status</div>
            <div class="mt-2">{!! $serial->status_badge !!}</div>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="stats-card text-center">
            <div class="stats-label">Location</div>
            <div class="mt-2">{!! $serial->location_type_badge !!}</div>
            <small class="text-muted">{{ $serial->location_name }}</small>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="stats-card text-center">
            <div class="stats-label">Warranty</div>
            <div class="mt-2">{!! $serial->warranty_status !!}</div>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="stats-card text-center">
            <div class="stats-label">Age</div>
            <div class="mt-2"><strong>{{ $serial->age_since_grn ?? 'N/A' }}</strong></div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Left Column: Serial Details -->
    <div class="col-md-6">
        <!-- Basic Information -->
        <div class="card mb-4">
            <div class="card-header"><i class="bi bi-info-circle me-2"></i>Serial Information</div>
            <div class="card-body">
                <table class="table table-borderless mb-0">
                    <tr>
                        <td class="text-muted" width="40%">Serial Number</td>
                        <td><strong>{{ $serial->serial_no }}</strong></td>
                    </tr>
                    <tr>
                        <td class="text-muted">Terminal Model</td>
                        <td>{{ $serial->terminalModel?->model_name ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">Category</td>
                        <td>{{ $serial->terminalModel?->category?->category_name ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">Hardware Type</td>
                        <td>{{ $serial->hardware_type ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">Device Type</td>
                        <td>{{ $serial->device_type ?? '-' }}</td>
                    </tr>
                    @if($serial->telco)
                    <tr>
                        <td class="text-muted">Telco</td>
                        <td>{{ $serial->telco }}</td>
                    </tr>
                    @endif
                    @if($serial->sim_quota)
                    <tr>
                        <td class="text-muted">SIM Quota</td>
                        <td>{{ $serial->sim_quota }}</td>
                    </tr>
                    @endif
                    <tr>
                        <td class="text-muted">Purchase Price</td>
                        <td>{{ $serial->purchase_price ? 'RM ' . number_format($serial->purchase_price, 2) : '-' }}</td>
                    </tr>
                    @if($serial->remarks)
                    <tr>
                        <td class="text-muted">Remarks</td>
                        <td>{{ $serial->remarks }}</td>
                    </tr>
                    @endif
                </table>
            </div>
        </div>

        <!-- Procurement Info -->
        <div class="card mb-4">
            <div class="card-header"><i class="bi bi-receipt me-2"></i>Procurement Details</div>
            <div class="card-body">
                <table class="table table-borderless mb-0">
                    <tr>
                        <td class="text-muted" width="40%">GRN Number</td>
                        <td>{{ $serial->grn?->grn_no ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">GRN Date</td>
                        <td>{{ $serial->grn_date?->format('d/m/Y') ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">Vendor</td>
                        <td>{{ $serial->grn?->vendor?->vendor_name ?? $serial->grn?->vendor?->company_name ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">Receiving Depot</td>
                        <td>{{ $serial->grn?->receivingDepot?->depot_name ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">Purchase Order</td>
                        <td>{{ $serial->purchaseOrder?->po_no ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">Warranty Period</td>
                        <td>
                            @if($serial->warranty_start && $serial->warranty_end)
                                {{ $serial->warranty_start->format('d/m/Y') }} - {{ $serial->warranty_end->format('d/m/Y') }}
                            @else
                                -
                            @endif
                        </td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- Audit Info -->
        <div class="card mb-4">
            <div class="card-header"><i class="bi bi-clock-history me-2"></i>Record Info</div>
            <div class="card-body">
                <table class="table table-borderless mb-0">
                    <tr>
                        <td class="text-muted" width="40%">Created By</td>
                        <td>{{ $serial->createdBy?->name ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">Created At</td>
                        <td>{{ $serial->created_at?->format('d/m/Y H:i') ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">Updated By</td>
                        <td>{{ $serial->updatedBy?->name ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">Updated At</td>
                        <td>{{ $serial->updated_at?->format('d/m/Y H:i') ?? '-' }}</td>
                    </tr>
                </table>
            </div>
        </div>
    </div>

    <!-- Right Column: History -->
    <div class="col-md-6">
        <!-- Movement History Timeline -->
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-arrow-left-right me-2"></i>Movement History</span>
                <span class="badge bg-primary">{{ $movement_history->count() }} records</span>
            </div>
            <div class="card-body" style="max-height: 500px; overflow-y: auto;">
                @include('components.serial-timeline', ['movements' => $movement_history])
            </div>
        </div>

        <!-- Installation History -->
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-geo-alt me-2"></i>Installation History</span>
                <span class="badge bg-info">{{ $installation_history->count() }} records</span>
            </div>
            <div class="card-body">
                @if($installation_history->isEmpty())
                    <div class="text-center text-muted py-3">
                        <i class="bi bi-geo-alt" style="font-size: 2rem;"></i>
                        <p class="mt-2 mb-0">No installation records found.</p>
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Site</th>
                                    <th>Installed</th>
                                    <th>By</th>
                                    <th>Status</th>
                                    <th>Removed</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($installation_history as $install)
                                <tr>
                                    <td>{{ $install->site?->site_name ?? '-' }}</td>
                                    <td>{{ $install->installed_date?->format('d/m/Y') ?? '-' }}</td>
                                    <td>{{ $install->installedBy?->name ?? '-' }}</td>
                                    <td>
                                        <span class="badge bg-{{ $install->status === 'active' ? 'success' : 'secondary' }}">
                                            {{ ucfirst($install->status) }}
                                        </span>
                                    </td>
                                    <td>{{ $install->removed_date?->format('d/m/Y') ?? '-' }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>

        <!-- Service History -->
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-wrench me-2"></i>Service History</span>
                <span class="badge bg-warning text-dark">{{ $service_history->count() }} records</span>
            </div>
            <div class="card-body">
                @if($service_history->isEmpty())
                    <div class="text-center text-muted py-3">
                        <i class="bi bi-wrench" style="font-size: 2rem;"></i>
                        <p class="mt-2 mb-0">No service records found.</p>
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Type</th>
                                    <th>Site</th>
                                    <th>By</th>
                                    <th>Description</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($service_history as $event)
                                <tr>
                                    <td>{{ $event->event_date?->format('d/m/Y') ?? '-' }}</td>
                                    <td>
                                        <span class="badge bg-outline-info">{{ ucfirst(str_replace('_', ' ', $event->event_type)) }}</span>
                                    </td>
                                    <td>{{ $event->siteAsset?->site?->site_name ?? '-' }}</td>
                                    <td>{{ $event->performedBy?->name ?? '-' }}</td>
                                    <td><small>{{ Str::limit($event->description, 50) }}</small></td>
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
@endsection
