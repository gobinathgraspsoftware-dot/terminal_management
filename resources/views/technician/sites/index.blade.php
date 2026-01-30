@extends('layouts.app')

@section('title', 'My Sites')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="mb-4">
        <h1 class="h3 mb-1">My Sites</h1>
        <p class="text-muted">Sites where you have job assignments</p>
    </div>

    <!-- Statistics Cards -->
    <div class="row g-3 mb-4">
        <div class="col-6">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <div class="display-6 text-primary mb-2">{{ $stats['total_sites'] ?? 0 }}</div>
                    <small class="text-muted">My Sites</small>
                </div>
            </div>
        </div>
        <div class="col-6">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <div class="display-6 text-success mb-2">{{ $stats['active_jobs'] ?? 0 }}</div>
                    <small class="text-muted">Active Jobs</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Sites List -->
    <div class="row g-3">
        @forelse($sites as $site)
        <div class="col-md-6 col-lg-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-primary text-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <h6 class="mb-0">{{ $site->site_code }}</h6>
                        @if($site->status === 'active')
                            <span class="badge bg-success">Active</span>
                        @else
                            <span class="badge bg-secondary">Inactive</span>
                        @endif
                    </div>
                </div>
                <div class="card-body">
                    <h5 class="card-title">{{ $site->site_name }}</h5>
                    
                    @if($site->client)
                    <p class="text-muted small mb-2">
                        <i class="bi bi-building me-1"></i>{{ $site->client->client_name }}
                    </p>
                    @endif

                    <p class="text-muted small mb-2">
                        <i class="bi bi-geo-alt me-1"></i>{{ $site->city ?? 'Unknown' }}, {{ $site->state ?? 'Unknown' }}
                    </p>

                    @if($site->site_assets_count > 0)
                    <p class="mb-2">
                        <i class="bi bi-box-seam text-info me-1"></i>
                        <span class="badge bg-info">{{ $site->site_assets_count }} Assets</span>
                    </p>
                    @endif

                    @if($site->latitude && $site->longitude)
                    <a href="https://www.google.com/maps?q={{ $site->latitude }},{{ $site->longitude }}" 
                       target="_blank" class="btn btn-sm btn-outline-primary w-100 mb-2">
                        <i class="bi bi-map me-1"></i> Navigate (GPS)
                    </a>
                    @endif

                    <a href="{{ route('technician.sites.show', $site) }}" 
                       class="btn btn-sm btn-primary w-100">
                        <i class="bi bi-eye me-1"></i> View Details
                    </a>
                </div>
            </div>
        </div>
        @empty
        <div class="col-12">
            <div class="alert alert-info">
                <i class="bi bi-info-circle me-2"></i>
                You don't have any job assignments yet. Sites will appear here when jobs are assigned to you.
            </div>
        </div>
        @endforelse
    </div>

    <!-- Pagination -->
    @if($sites->hasPages())
    <div class="mt-4">
        {{ $sites->links() }}
    </div>
    @endif
</div>
@endsection
