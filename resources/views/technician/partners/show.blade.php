@extends('layouts.app')

@section('title', 'View Partner - ' . $partner->partner_name)

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">Partner Details</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('technician.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('technician.partners.index') }}">Partners</a></li>
                    <li class="breadcrumb-item active">{{ $partner->partner_code }}</li>
                </ol>
            </nav>
        </div>
        <a href="{{ route('technician.partners.index') }}" class="btn btn-secondary">
            <i class="bi bi-arrow-left me-1"></i> Back
        </a>
    </div>

    <!-- Partner Header Card -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <div class="row align-items-center">
                <div class="col-auto">
                    <div class="avatar-lg bg-primary bg-opacity-10 rounded-circle d-flex align-items-center justify-content-center">
                        <i class="bi bi-building fs-1 text-primary"></i>
                    </div>
                </div>
                <div class="col">
                    <h4 class="mb-1">{{ $partner->partner_name }}</h4>
                    <p class="text-muted mb-0">
                        <span class="badge bg-secondary">{{ $partner->partner_code }}</span>
                        @if($partner->city || $partner->state)
                            <span class="ms-2">
                                <i class="bi bi-geo-alt"></i>
                                {{ implode(', ', array_filter([$partner->city, $partner->state])) }}
                            </span>
                        @endif
                    </p>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Contact Information Card -->
        <div class="col-lg-6 mb-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white">
                    <h6 class="mb-0">
                        <i class="bi bi-person me-2 text-primary"></i>Contact Information
                    </h6>
                </div>
                <div class="card-body">
                    @if($partner->pic_name || $partner->pic_phone)
                        <div class="d-flex align-items-start mb-3">
                            <div class="flex-shrink-0">
                                <div class="avatar-sm bg-light rounded-circle d-flex align-items-center justify-content-center">
                                    <i class="bi bi-person text-secondary"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h6 class="mb-1">{{ $partner->pic_name ?: 'Contact Person' }}</h6>
                                <p class="text-muted mb-0 small">Person In Charge</p>
                            </div>
                        </div>

                        @if($partner->pic_phone)
                            <a href="tel:{{ $partner->pic_phone }}" class="btn btn-outline-primary w-100 mb-2">
                                <i class="bi bi-telephone me-2"></i>{{ $partner->pic_phone }}
                            </a>
                        @endif
                    @else
                        <div class="text-center text-muted py-3">
                            <i class="bi bi-person-slash fs-1"></i>
                            <p class="mt-2 mb-0">No contact information available</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Address Card -->
        <div class="col-lg-6 mb-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white">
                    <h6 class="mb-0">
                        <i class="bi bi-geo-alt me-2 text-primary"></i>Address
                    </h6>
                </div>
                <div class="card-body">
                    @if($partner->address || $partner->city || $partner->state)
                        <address class="mb-3">
                            @if($partner->address)
                                {{ $partner->address }}<br>
                            @endif
                            @if($partner->postcode || $partner->city)
                                {{ $partner->postcode }} {{ $partner->city }}<br>
                            @endif
                            @if($partner->state)
                                {{ $partner->state }}<br>
                            @endif
                            {{ $partner->country ?? 'Malaysia' }}
                        </address>

                        @if($partner->city && $partner->state)
                            <a href="https://www.google.com/maps/search/?api=1&query={{ urlencode($partner->address . ', ' . $partner->city . ', ' . $partner->state) }}" 
                               target="_blank" 
                               class="btn btn-outline-secondary w-100">
                                <i class="bi bi-map me-2"></i>Open in Maps
                            </a>
                        @endif
                    @else
                        <div class="text-center text-muted py-3">
                            <i class="bi bi-geo-alt-slash fs-1"></i>
                            <p class="mt-2 mb-0">No address available</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Associated Clients Card -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h6 class="mb-0">
                <i class="bi bi-people me-2 text-primary"></i>Associated Clients
            </h6>
            <span class="badge bg-primary">{{ $partner->clients->where('status', 'active')->count() }} Active</span>
        </div>
        <div class="card-body p-0">
            @if($partner->clients->where('status', 'active')->count() > 0)
                <div class="list-group list-group-flush">
                    @foreach($partner->clients->where('status', 'active')->take(10) as $client)
                        <div class="list-group-item">
                            <div class="d-flex w-100 justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-1">{{ $client->client_name }}</h6>
                                    <small class="text-muted">
                                        <code>{{ $client->client_code }}</code>
                                        @if($client->billing_city)
                                            <span class="ms-2">
                                                <i class="bi bi-geo-alt"></i> {{ $client->billing_city }}
                                            </span>
                                        @endif
                                    </small>
                                </div>
                                <a href="{{ route('technician.clients.show', $client->id) }}" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-eye"></i>
                                </a>
                            </div>
                        </div>
                    @endforeach
                </div>
                @if($partner->clients->where('status', 'active')->count() > 10)
                    <div class="card-footer bg-light text-center">
                        <span class="text-muted small">
                            Showing 10 of {{ $partner->clients->where('status', 'active')->count() }} clients
                        </span>
                    </div>
                @endif
            @else
                <div class="text-center text-muted py-4">
                    <i class="bi bi-people fs-1"></i>
                    <p class="mt-2 mb-0">No active clients for this partner</p>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .avatar-lg {
        width: 70px;
        height: 70px;
    }
    .avatar-sm {
        width: 40px;
        height: 40px;
    }
    address {
        font-style: normal;
        line-height: 1.6;
    }
    .list-group-item {
        border-left: 0;
        border-right: 0;
    }
    .list-group-item:first-child {
        border-top: 0;
    }
    /* Mobile responsiveness */
    @media (max-width: 768px) {
        .avatar-lg {
            width: 60px;
            height: 60px;
        }
        .avatar-lg i {
            font-size: 1.5rem !important;
        }
    }
</style>
@endpush
