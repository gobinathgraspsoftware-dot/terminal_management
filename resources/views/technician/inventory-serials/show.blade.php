@extends('layouts.app')

@section('title', 'Serial Detail - ' . $serial->serial_no)

@section('content')
<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h1><i class="bi bi-upc-scan me-2"></i>{{ $serial->serial_no }}</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('technician.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('technician.inventory-serials.index') }}">My Stock</a></li>
                    <li class="breadcrumb-item active">{{ $serial->serial_no }}</li>
                </ol>
            </nav>
        </div>
        <a href="{{ route('technician.inventory-serials.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i> Back
        </a>
    </div>
</div>

<!-- Status Card -->
<div class="row mb-4">
    <div class="col-6 mb-3">
        <div class="stats-card text-center">
            <div class="stats-label">Status</div>
            <div class="mt-2">{!! $serial->status_badge !!}</div>
        </div>
    </div>
    <div class="col-6 mb-3">
        <div class="stats-card text-center">
            <div class="stats-label">Warranty</div>
            <div class="mt-2">{!! $serial->warranty_status !!}</div>
        </div>
    </div>
</div>

<!-- Serial Info -->
<div class="card mb-4">
    <div class="card-header"><i class="bi bi-info-circle me-2"></i>Device Info</div>
    <div class="card-body">
        <table class="table table-borderless mb-0">
            <tr><td class="text-muted">Serial No</td><td><strong>{{ $serial->serial_no }}</strong></td></tr>
            <tr><td class="text-muted">Model</td><td>{{ $serial->terminalModel?->model_name ?? '-' }}</td></tr>
            <tr><td class="text-muted">Category</td><td>{{ $serial->terminalModel?->category?->category_name ?? '-' }}</td></tr>
            <tr><td class="text-muted">Type</td><td>{{ $serial->hardware_type ?? '-' }} / {{ $serial->device_type ?? '-' }}</td></tr>
            @if($serial->telco)
            <tr><td class="text-muted">Telco</td><td>{{ $serial->telco }} {{ $serial->sim_quota ? '(' . $serial->sim_quota . ')' : '' }}</td></tr>
            @endif
            <tr><td class="text-muted">GRN</td><td>{{ $serial->grn?->grn_no ?? '-' }}</td></tr>
            <tr><td class="text-muted">Warranty</td><td>
                @if($serial->warranty_end)
                    Until {{ $serial->warranty_end->format('d/m/Y') }}
                @else - @endif
            </td></tr>
        </table>
    </div>
</div>

<!-- Movement History -->
<div class="card mb-4">
    <div class="card-header d-flex justify-content-between">
        <span><i class="bi bi-arrow-left-right me-2"></i>Recent Movements</span>
        <span class="badge bg-primary">{{ $movement_history->count() }}</span>
    </div>
    <div class="card-body" style="max-height: 400px; overflow-y: auto;">
        @include('components.serial-timeline', ['movements' => $movement_history->take(10)])
    </div>
</div>
@endsection
