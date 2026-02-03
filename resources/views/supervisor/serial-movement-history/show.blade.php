@extends('layouts.app')

@section('title', 'Serial Timeline - ' . ($serial->serial_no ?? 'N/A') . ' - TMS')

@section('content')
<div class="container-fluid">
    {{-- Page Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1">
                <i class="bi bi-clock-history me-2"></i>
                Serial Timeline: <span class="text-primary">{{ $serial->serial_no }}</span>
            </h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('supervisor.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('supervisor.serial-movement-history.index') }}">Movement History</a></li>
                    <li class="breadcrumb-item active">{{ $serial->serial_no }}</li>
                </ol>
            </nav>
        </div>
        <div>
            <a href="{{ route('supervisor.inventory-serials.show', $serial->id) }}" class="btn btn-outline-info btn-sm">
                <i class="bi bi-eye me-1"></i>Serial Detail
            </a>
            <a href="{{ route('supervisor.serial-movement-history.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left me-1"></i>Back to List
            </a>
        </div>
    </div>

    <div class="row">
        {{-- Left Column: Serial Info & Summary --}}
        <div class="col-lg-4">
            {{-- Serial Info Card --}}
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-bottom">
                    <h6 class="mb-0"><i class="bi bi-upc-scan me-2"></i>Serial Information</h6>
                </div>
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
                            <td class="text-muted">Category:</td>
                            <td>{{ $serial->terminalModel?->category?->category_name ?? '-' }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">Status:</td>
                            <td>{!! $serial->status_badge !!}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">Location:</td>
                            <td>{!! $serial->location_type_badge !!}<br><small>{{ $serial->location_name }}</small></td>
                        </tr>
                        <tr>
                            <td class="text-muted">GRN Date:</td>
                            <td>{{ $serial->grn_date?->format('d/m/Y') ?? '-' }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">Warranty:</td>
                            <td>{!! $serial->warranty_status !!}</td>
                        </tr>
                    </table>
                </div>
            </div>

            {{-- Movement Summary Card --}}
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-bottom">
                    <h6 class="mb-0"><i class="bi bi-bar-chart me-2"></i>Movement Summary</h6>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Total Movements:</span>
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
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">First Movement:</span>
                        <span>{{ $summary['first_movement_date'] ?? '-' }}</span>
                    </div>
                    <div class="d-flex justify-content-between mb-3">
                        <span class="text-muted">Last Movement:</span>
                        <span>{{ $summary['last_movement_date'] ?? '-' }}</span>
                    </div>

                    <hr>
                    <h6 class="text-muted mb-2">By Movement Type</h6>
                    @forelse($summary['by_type'] ?? [] as $type => $count)
                        @php
                            $color = \App\Models\StockLedger::TYPE_COLORS[$type] ?? 'secondary';
                            $label = \App\Models\StockLedger::TYPE_OPTIONS[$type] ?? ucfirst(str_replace('_', ' ', $type));
                            $icon  = \App\Models\StockLedger::TYPE_ICONS[$type] ?? 'bi-arrow-left-right';
                        @endphp
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span>
                                <i class="bi {{ $icon }} text-{{ $color }} me-1"></i>
                                <small>{{ $label }}</small>
                            </span>
                            <span class="badge bg-{{ $color }}">{{ $count }}</span>
                        </div>
                    @empty
                        <p class="text-muted text-center mb-0">No data</p>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- Right Column: Timeline --}}
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center">
                    <h6 class="mb-0"><i class="bi bi-signpost-split me-2"></i>Movement Timeline</h6>
                    <span class="badge bg-primary">{{ $movements->count() }} movements</span>
                </div>
                <div class="card-body">
                    @include('components.serial-timeline', [
                        'movements'  => $movements,
                        'serial'     => $serial,
                        'canReverse' => false,
                        'rolePrefix' => 'supervisor',
                    ])
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
