@extends('layouts.app')

@section('title', 'Job Catalog')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">Job Catalog</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('technician.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item active">Job Catalog</li>
                </ol>
            </nav>
        </div>
    </div>

    <!-- Statistics -->
    <div class="row mb-4">
        <div class="col-6">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <h4 class="mb-0 text-primary">{{ $stats['total_charges'] }}</h4>
                    <small class="text-muted">Total Jobs</small>
                </div>
            </div>
        </div>
        <div class="col-6">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <h4 class="mb-0 text-info">{{ $stats['types'] }}</h4>
                    <small class="text-muted">Job Types</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Search -->
    <div class="mb-3">
        <input type="text" class="form-control" id="searchCharges" placeholder="Search charges...">
    </div>

    <!-- Charges grouped by Job Type -->
    @forelse($chargesByType as $typeName => $charges)
    <div class="card border-0 shadow-sm mb-3 charge-group">
        <div class="card-header bg-white py-2 d-flex justify-content-between align-items-center"
             data-bs-toggle="collapse" data-bs-target="#type-{{ Str::slug($typeName) }}"
             role="button" aria-expanded="true">
            <h6 class="mb-0">
                <i class="bi bi-briefcase me-1"></i> {{ $typeName }}
                <span class="badge bg-primary ms-1">{{ $charges->count() }}</span>
            </h6>
            <i class="bi bi-chevron-down"></i>
        </div>
        <div class="collapse show" id="type-{{ Str::slug($typeName) }}">
            <div class="list-group list-group-flush">
                @foreach($charges as $charge)
                <div class="list-group-item charge-item">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <strong>{{ $charge->charge_name }}</strong>
                            <br><small class="text-muted">{{ $charge->charge_code }}</small>
                            @if($charge->description)
                                <br><small class="text-muted">{{ Str::limit($charge->description, 60) }}</small>
                            @endif
                        </div>
                        <div class="text-end">
                            <strong class="text-primary">RM {{ number_format($charge->default_price, 2) }}</strong>
                            @if($charge->unit)
                                <br><small class="text-muted">{{ $charge->unit }}</small>
                            @endif
                            @if($charge->is_taxable)
                                <br><span class="badge bg-success">{{ $charge->tax_rate }}% Tax</span>
                            @endif
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>
    @empty
    <div class="card border-0 shadow-sm">
        <div class="card-body text-center text-muted py-5">
            <i class="bi bi-tag fs-1"></i>
            <p class="mt-2">No Jobs available.</p>
        </div>
    </div>
    @endforelse
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Simple client-side search filter
    $('#searchCharges').on('keyup', function() {
        var search = $(this).val().toLowerCase();
        if (search === '') {
            $('.charge-group').show();
            $('.charge-item').show();
            return;
        }
        $('.charge-item').each(function() {
            var text = $(this).text().toLowerCase();
            $(this).toggle(text.indexOf(search) > -1);
        });
        // Hide empty groups
        $('.charge-group').each(function() {
            var visible = $(this).find('.charge-item:visible').length;
            $(this).toggle(visible > 0);
        });
    });
});
</script>
@endpush
