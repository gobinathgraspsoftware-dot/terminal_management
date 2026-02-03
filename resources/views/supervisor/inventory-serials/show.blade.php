@extends('layouts.app')

@section('title', 'Serial Detail - ' . $serial->serial_no)

@section('content')
<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h1><i class="bi bi-upc-scan me-2"></i>Serial: {{ $serial->serial_no }}</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('supervisor.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('supervisor.inventory-serials.index') }}">Serial Tracking</a></li>
                    <li class="breadcrumb-item active">{{ $serial->serial_no }}</li>
                </ol>
            </nav>
        </div>
        <a href="{{ route('supervisor.inventory-serials.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Back
        </a>
    </div>
</div>

<!-- Status Bar -->
<div class="row mb-4">
    <div class="col-md-3 mb-3">
        <div class="stats-card text-center">
            <div class="stats-label">Status</div>
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
            <div class="stats-label">Model</div>
            <div class="mt-2"><strong>{{ $serial->terminalModel?->model_name ?? '-' }}</strong></div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="card mb-4">
            <div class="card-header"><i class="bi bi-info-circle me-2"></i>Serial Information</div>
            <div class="card-body">
                <table class="table table-borderless mb-0">
                    <tr><td class="text-muted" width="40%">Serial Number</td><td><strong>{{ $serial->serial_no }}</strong></td></tr>
                    <tr><td class="text-muted">Terminal Model</td><td>{{ $serial->terminalModel?->model_name ?? '-' }}</td></tr>
                    <tr><td class="text-muted">Category</td><td>{{ $serial->terminalModel?->category?->category_name ?? '-' }}</td></tr>
                    <tr><td class="text-muted">Hardware Type</td><td>{{ $serial->hardware_type ?? '-' }}</td></tr>
                    <tr><td class="text-muted">Device Type</td><td>{{ $serial->device_type ?? '-' }}</td></tr>
                    @if($serial->telco)<tr><td class="text-muted">Telco</td><td>{{ $serial->telco }}</td></tr>@endif
                    <tr><td class="text-muted">GRN</td><td>{{ $serial->grn?->grn_no ?? '-' }} {{ $serial->grn_date ? '(' . $serial->grn_date->format('d/m/Y') . ')' : '' }}</td></tr>
                    <tr><td class="text-muted">Warranty</td><td>
                        @if($serial->warranty_start && $serial->warranty_end)
                            {{ $serial->warranty_start->format('d/m/Y') }} — {{ $serial->warranty_end->format('d/m/Y') }}
                        @else - @endif
                    </td></tr>
                </table>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between">
                <span><i class="bi bi-arrow-left-right me-2"></i>Movement History</span>
                <span class="badge bg-primary">{{ $movement_history->count() }}</span>
            </div>
            <div class="card-body" style="max-height: 500px; overflow-y: auto;">
                @include('components.serial-timeline', ['movements' => $movement_history])
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between">
                <span><i class="bi bi-geo-alt me-2"></i>Installation History</span>
                <span class="badge bg-info">{{ $installation_history->count() }}</span>
            </div>
            <div class="card-body">
                @if($installation_history->isEmpty())
                    <p class="text-muted text-center mb-0">No installations recorded.</p>
                @else
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead><tr><th>Site</th><th>Installed</th><th>Status</th></tr></thead>
                            <tbody>
                                @foreach($installation_history as $install)
                                <tr>
                                    <td>{{ $install->site?->site_name ?? '-' }}</td>
                                    <td>{{ $install->installed_date?->format('d/m/Y') ?? '-' }}</td>
                                    <td><span class="badge bg-{{ $install->status === 'active' ? 'success' : 'secondary' }}">{{ ucfirst($install->status) }}</span></td>
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
