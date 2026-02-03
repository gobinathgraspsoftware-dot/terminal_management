@extends('layouts.app')

@section('title', 'Serial Timeline - ' . ($serial->serial_no ?? 'N/A') . ' - TMS')

@section('content')
<div class="container-fluid">
    {{-- Page Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1">
                <i class="bi bi-clock-history me-2"></i>
                Timeline: <span class="text-primary">{{ $serial->serial_no }}</span>
            </h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('technician.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('technician.serial-movement-history.index') }}">Movement History</a></li>
                    <li class="breadcrumb-item active">{{ $serial->serial_no }}</li>
                </ol>
            </nav>
        </div>
        <div>
            <a href="{{ route('technician.inventory-serials.show', $serial->id) }}" class="btn btn-outline-info btn-sm">
                <i class="bi bi-eye me-1"></i>Detail
            </a>
            <a href="{{ route('technician.serial-movement-history.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left me-1"></i>Back
            </a>
        </div>
    </div>

    <div class="row">
        {{-- Serial Info Card (Collapsible on mobile) --}}
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center"
                     data-bs-toggle="collapse" data-bs-target="#serialInfoCollapse" role="button">
                    <h6 class="mb-0"><i class="bi bi-upc-scan me-2"></i>Serial Info</h6>
                    <i class="bi bi-chevron-down d-lg-none"></i>
                </div>
                <div id="serialInfoCollapse" class="collapse show">
                    <div class="card-body">
                        <table class="table table-sm table-borderless mb-0">
                            <tr>
                                <td class="text-muted" width="40%">Serial No:</td>
                                <td class="fw-bold">{{ $serial->serial_no }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted">Model:</td>
                                <td>{{ $serial->terminalModel?->model_name ?? '-' }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted">Status:</td>
                                <td>{!! $serial->status_badge !!}</td>
                            </tr>
                            <tr>
                                <td class="text-muted">Location:</td>
                                <td>{{ $serial->location_name }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted">Warranty:</td>
                                <td>{!! $serial->warranty_status !!}</td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>

            {{-- Summary --}}
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-bottom">
                    <h6 class="mb-0"><i class="bi bi-bar-chart me-2"></i>Summary</h6>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Total:</span>
                        <span class="fw-bold">{{ $summary['total_movements'] ?? 0 }}</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Active:</span>
                        <span class="fw-bold text-success">{{ $summary['active_movements'] ?? 0 }}</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Reversed:</span>
                        <span class="fw-bold text-danger">{{ $summary['reversed_movements'] ?? 0 }}</span>
                    </div>

                    <hr>
                    @forelse($summary['by_type'] ?? [] as $type => $count)
                        @php
                            $color = \App\Models\StockLedger::TYPE_COLORS[$type] ?? 'secondary';
                            $label = \App\Models\StockLedger::TYPE_OPTIONS[$type] ?? ucfirst(str_replace('_', ' ', $type));
                        @endphp
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <small>{{ $label }}</small>
                            <span class="badge bg-{{ $color }}">{{ $count }}</span>
                        </div>
                    @empty
                        <p class="text-muted text-center mb-0">No data</p>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- Timeline --}}
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center">
                    <h6 class="mb-0"><i class="bi bi-signpost-split me-2"></i>Movement Timeline</h6>
                    <span class="badge bg-primary">{{ $movements->count() }}</span>
                </div>
                <div class="card-body">
                    @include('components.serial-timeline', [
                        'movements'  => $movements,
                        'serial'     => $serial,
                        'canReverse' => false,
                        'rolePrefix' => 'technician',
                    ])
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
