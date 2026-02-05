@extends('layouts.app')

@section('title', 'Stock Card')

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h3 mb-1">Stock Card</h1>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-0">
                            <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('admin.stock-reports.index') }}">Stock Reports</a></li>
                            <li class="breadcrumb-item active">Stock Card</li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </div>

    <!-- Serial Selection -->
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-light">
            <h5 class="mb-0"><i class="bi bi-search me-2"></i>Select Serial Number</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <label class="form-label">Search Serial Number</label>
                    <select id="serialSelect" class="form-select" style="width: 100%;">
                        <option value="">Type to search serial number...</option>
                        @foreach($serials as $serial)
                            <option value="{{ $serial->id }}" {{ isset($stockCardData) && $stockCardData['serial']->id == $serial->id ? 'selected' : '' }}>
                                {{ $serial->serial_no }} - {{ $serial->model ? $serial->model->model_name : 'Unknown' }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6 d-flex align-items-end">
                    <button type="button" class="btn btn-primary" onclick="viewStockCard()">
                        <i class="bi bi-eye me-2"></i>View Stock Card
                    </button>
                </div>
            </div>
        </div>
    </div>

    @if(isset($stockCardData))
    <!-- Serial Information -->
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0"><i class="bi bi-box-seam me-2"></i>Serial Information</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-3">
                    <p class="mb-1 text-muted">Serial Number</p>
                    <h5>{{ $stockCardData['serial']->serial_no }}</h5>
                </div>
                <div class="col-md-3">
                    <p class="mb-1 text-muted">Model</p>
                    <h5>{{ $stockCardData['serial']->model ? $stockCardData['serial']->model->model_name : 'N/A' }}</h5>
                </div>
                <div class="col-md-3">
                    <p class="mb-1 text-muted">Current Status</p>
                    <h5><span class="badge bg-info">{{ ucfirst(str_replace('_', ' ', $stockCardData['serial']->current_status)) }}</span></h5>
                </div>
                <div class="col-md-3">
                    <p class="mb-1 text-muted">Current Location</p>
                    <h5>{{ $stockCardData['serial']->current_location_name ?? 'N/A' }}</h5>
                </div>
            </div>
        </div>
    </div>

    <!-- Summary Statistics -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card border-start border-primary border-4">
                <div class="card-body">
                    <h6 class="text-muted mb-1">Total Movements</h6>
                    <h4 class="mb-0">{{ $stockCardData['movement_count'] }}</h4>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-start border-success border-4">
                <div class="card-body">
                    <h6 class="text-muted mb-1">Total In</h6>
                    <h4 class="mb-0">{{ number_format($stockCardData['total_in']) }}</h4>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-start border-danger border-4">
                <div class="card-body">
                    <h6 class="text-muted mb-1">Total Out</h6>
                    <h4 class="mb-0">{{ number_format($stockCardData['total_out']) }}</h4>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-start border-info border-4">
                <div class="card-body">
                    <h6 class="text-muted mb-1">Current Balance</h6>
                    <h4 class="mb-0">{{ $stockCardData['current_balance'] }}</h4>
                </div>
            </div>
        </div>
    </div>

    <!-- Action Buttons -->
    <div class="row mb-3">
        <div class="col-12 text-end">
            <a href="{{ route('admin.stock-reports.stock-card-export', $stockCardData['serial']->id) }}" class="btn btn-success">
                <i class="bi bi-file-earmark-excel me-2"></i>Export to Excel
            </a>
            <a href="{{ route('admin.stock-reports.stock-card-print', $stockCardData['serial']->id) }}" target="_blank" class="btn btn-secondary">
                <i class="bi bi-printer me-2"></i>Print
            </a>
        </div>
    </div>

    <!-- Movement History -->
    <div class="card shadow-sm">
        <div class="card-header bg-light">
            <h5 class="mb-0"><i class="bi bi-clock-history me-2"></i>Movement History</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Date</th>
                            <th>Transaction No</th>
                            <th>Type</th>
                            <th class="text-end">Qty In</th>
                            <th class="text-end">Qty Out</th>
                            <th class="text-end">Balance</th>
                            <th>From</th>
                            <th>To</th>
                            <th>Reference</th>
                            <th>Remarks</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($stockCardData['movements'] as $movement)
                            <tr>
                                <td>{{ $movement->transaction_date->format('M d, Y') }}</td>
                                <td><span class="badge bg-secondary">{{ $movement->transaction_no }}</span></td>
                                <td>{!! $movement->type_badge !!}</td>
                                <td class="text-end">
                                    @if($movement->quantity > 0)
                                        <span class="badge bg-success">{{ $movement->quantity }}</span>
                                    @else
                                        -
                                    @endif
                                </td>
                                <td class="text-end">
                                    @if($movement->quantity < 0)
                                        <span class="badge bg-danger">{{ abs($movement->quantity) }}</span>
                                    @else
                                        -
                                    @endif
                                </td>
                                <td class="text-end"><strong>{{ $movement->running_balance }}</strong></td>
                                <td>{{ $movement->from_location_name }}</td>
                                <td>{{ $movement->to_location_name }}</td>
                                <td>
                                    @if($movement->reference_url)
                                        <a href="{{ $movement->reference_url }}" class="text-decoration-none">
                                            {{ $movement->reference_label }}
                                        </a>
                                    @else
                                        {{ $movement->reference_label }}
                                    @endif
                                </td>
                                <td>{{ $movement->remarks ?? '-' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @else
    <!-- No Serial Selected -->
    <div class="card shadow-sm">
        <div class="card-body text-center py-5">
            <i class="bi bi-search fs-1 text-muted mb-3 d-block"></i>
            <h5 class="text-muted">Please select a serial number to view its stock card</h5>
        </div>
    </div>
    @endif
</div>

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
$(document).ready(function() {
    $('#serialSelect').select2({
        theme: 'bootstrap-5',
        placeholder: 'Type to search serial number...',
        allowClear: true,
        ajax: {
            url: '{{ route("admin.stock-reports.search-serials") }}',
            dataType: 'json',
            delay: 250,
            data: function (params) {
                return {
                    term: params.term
                };
            },
            processResults: function (data) {
                return {
                    results: data
                };
            },
            cache: true
        }
    });
});

function viewStockCard() {
    const serialId = $('#serialSelect').val();
    if (serialId) {
        window.location.href = '{{ route("admin.stock-reports.stock-card", "") }}/' + serialId;
    } else {
        alert('Please select a serial number');
    }
}
</script>
@endpush
@endsection
