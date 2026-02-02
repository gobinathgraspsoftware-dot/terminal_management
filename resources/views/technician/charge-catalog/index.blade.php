@extends('layouts.app')

@section('title', 'Charge Catalog')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="mb-4">
        <h1 class="h3 mb-0">Charge Catalog</h1>
        <p class="text-muted mb-0">Quick reference for standard charges</p>
    </div>

    <!-- Quick Stats -->
    <div class="row mb-4">
        <div class="col-6">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <h2 class="mb-0 text-primary">{{ $stats['total_charges'] }}</h2>
                    <small class="text-muted">Total Charges</small>
                </div>
            </div>
        </div>
        <div class="col-6">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <h2 class="mb-0 text-info">{{ $stats['types'] }}</h2>
                    <small class="text-muted">Charge Types</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Search Box -->
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body">
            <div class="input-group">
                <span class="input-group-text">
                    <i class="bi bi-search"></i>
                </span>
                <input type="text" 
                       class="form-control" 
                       id="searchBox" 
                       placeholder="Search charges...">
            </div>
        </div>
    </div>

    <!-- Charges Grouped by Type -->
    @foreach($chargesByType as $type => $charges)
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-header bg-white py-3">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <span class="badge bg-{{ 
                        $type == 'installation' ? 'primary' : 
                        ($type == 'service' ? 'info' : 
                        ($type == 'hardware' ? 'success' : 
                        ($type == 'accessory' ? 'warning' : 
                        ($type == 'labour' ? 'secondary' : 
                        ($type == 'transport' ? 'dark' : 'light'))))) 
                    }} me-2">
                        {{ ucfirst($type) }}
                    </span>
                </h5>
                <span class="badge bg-secondary">{{ $charges->count() }} items</span>
            </div>
        </div>
        <div class="list-group list-group-flush">
            @foreach($charges as $charge)
            <div class="list-group-item">
                <div class="d-flex justify-content-between align-items-start">
                    <div class="flex-grow-1">
                        <h6 class="mb-1">{{ $charge->charge_name }}</h6>
                        @if($charge->description)
                        <p class="mb-1 text-muted small">{{ $charge->description }}</p>
                        @endif
                        <small class="text-muted">{{ $charge->charge_code }}</small>
                        @if($charge->unit)
                        <small class="text-muted"> • {{ $charge->unit }}</small>
                        @endif
                    </div>
                    <div class="text-end ms-3">
                        <strong class="text-primary d-block">RM {{ number_format($charge->default_price, 2) }}</strong>
                        @if($charge->is_taxable)
                        <small class="text-success">
                            <i class="bi bi-check-circle-fill"></i> {{ $charge->tax_rate }}% tax
                        </small>
                        @else
                        <small class="text-secondary">No tax</small>
                        @endif
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endforeach

    @if($chargesByType->isEmpty())
    <div class="card border-0 shadow-sm">
        <div class="card-body text-center py-5">
            <i class="bi bi-inbox fs-1 text-muted"></i>
            <p class="text-muted mt-3">No charges available</p>
        </div>
    </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Simple search functionality
    $('#searchBox').on('keyup', function() {
        const searchTerm = $(this).val().toLowerCase();
        
        $('.list-group-item').each(function() {
            const text = $(this).text().toLowerCase();
            if (text.includes(searchTerm)) {
                $(this).show();
            } else {
                $(this).hide();
            }
        });

        // Hide/show card headers based on visible items
        $('.card').each(function() {
            const visibleItems = $(this).find('.list-group-item:visible').length;
            if (visibleItems > 0) {
                $(this).show();
            } else {
                $(this).hide();
            }
        });
    });
});
</script>
@endpush

@push('styles')
<style>
/* Mobile-friendly spacing */
@media (max-width: 768px) {
    .container-fluid {
        padding-left: 1rem;
        padding-right: 1rem;
    }
    
    .card {
        margin-bottom: 1rem;
    }
    
    .list-group-item {
        padding: 1rem 0.75rem;
    }
}
</style>
@endpush
