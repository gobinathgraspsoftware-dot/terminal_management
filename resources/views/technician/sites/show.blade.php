@extends('layouts.app')

@section('title', $site->site_name)

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h4 mb-1">{{ $site->site_name }}</h1>
            <p class="text-muted mb-0">{{ $site->site_code }}</p>
        </div>
        <a href="{{ route('technician.sites.index') }}" class="btn btn-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i> Back
        </a>
    </div>

    <!-- Site Information Card -->
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-header bg-primary text-white">
            <h6 class="mb-0"><i class="bi bi-info-circle me-2"></i>Site Information</h6>
        </div>
        <div class="card-body">
            <div class="row g-2">
                <div class="col-12">
                    <strong>Client:</strong><br>
                    <span class="text-muted">{{ $site->client ? $site->client->client_name : '-' }}</span>
                </div>
                
                <div class="col-12 border-top pt-2 mt-2">
                    <strong>Address:</strong><br>
                    <span class="text-muted">{{ $site->full_address }}</span>
                </div>

                @if($site->operating_hours)
                <div class="col-12 border-top pt-2 mt-2">
                    <strong>Operating Hours:</strong><br>
                    <span class="text-muted">{{ $site->operating_hours }}</span>
                </div>
                @endif

                @if($site->latitude && $site->longitude)
                <div class="col-12 border-top pt-2 mt-2">
                    <strong>GPS Location:</strong><br>
                    <a href="https://www.google.com/maps?q={{ $site->latitude }},{{ $site->longitude }}" 
                       target="_blank" class="btn btn-primary w-100 mt-2">
                        <i class="bi bi-map me-1"></i> Navigate to Site
                    </a>
                </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Contacts Card -->
    @if($site->contacts && $site->contacts->count() > 0)
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-header bg-secondary text-white">
            <h6 class="mb-0"><i class="bi bi-people me-2"></i>Site Contacts</h6>
        </div>
        <div class="card-body">
            @foreach($site->contacts as $contact)
            <div class="mb-3 {{ !$loop->last ? 'border-bottom pb-3' : '' }}">
                <strong>{{ $contact->contact_name }}</strong>
                @if($contact->is_primary)
                    <span class="badge bg-success">Primary</span>
                @endif
                @if($contact->contact_title)
                    <br><small class="text-muted">{{ $contact->contact_title }}</small>
                @endif
                
                <div class="mt-2">
                    @if($contact->contact_phone)
                    <a href="tel:{{ $contact->contact_phone }}" class="btn btn-sm btn-outline-primary me-2">
                        <i class="bi bi-telephone me-1"></i> Call
                    </a>
                    @endif
                    @if($contact->contact_email)
                    <a href="mailto:{{ $contact->contact_email }}" class="btn btn-sm btn-outline-info">
                        <i class="bi bi-envelope me-1"></i> Email
                    </a>
                    @endif
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    <!-- Installed Equipment Card -->
    @if($site->siteAssets && $site->siteAssets->count() > 0)
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-header bg-info text-white">
            <h6 class="mb-0">
                <i class="bi bi-box-seam me-2"></i>Installed Equipment
                <span class="badge bg-light text-dark ms-2">{{ $site->siteAssets->count() }}</span>
            </h6>
        </div>
        <div class="card-body">
            @foreach($site->siteAssets as $asset)
            <div class="mb-3 {{ !$loop->last ? 'border-bottom pb-3' : '' }}">
                <h6>
                    {{ $asset->model->model_name ?? 'Unknown Model' }}
                    @if($asset->status === 'active')
                        <span class="badge bg-success">Active</span>
                    @else
                        <span class="badge bg-secondary">{{ ucfirst($asset->status) }}</span>
                    @endif
                </h6>
                <p class="small text-muted mb-1">
                    <strong>Serial:</strong> {{ $asset->serial_no }}
                </p>
                <p class="small text-muted mb-0">
                    <strong>Installed:</strong> {{ $asset->installed_date ? \Carbon\Carbon::parse($asset->installed_date)->format('d M Y') : '-' }}
                </p>
                @if($asset->warranty_end)
                <p class="small mb-0">
                    <i class="bi bi-shield-check text-success me-1"></i>
                    Warranty: {{ \Carbon\Carbon::parse($asset->warranty_end)->format('d M Y') }}
                    @if(\Carbon\Carbon::parse($asset->warranty_end)->isPast())
                        <span class="badge bg-danger">Expired</span>
                    @endif
                </p>
                @endif
            </div>
            @endforeach
        </div>
    </div>
    @endif

    <!-- My Job History Card -->
    @if($myJobs && $myJobs->count() > 0)
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-header bg-warning text-dark">
            <h6 class="mb-0">
                <i class="bi bi-clipboard-check me-2"></i>My Job History
                <span class="badge bg-dark ms-2">{{ $myJobs->count() }}</span>
            </h6>
        </div>
        <div class="card-body">
            @foreach($myJobs as $job)
            <div class="mb-3 {{ !$loop->last ? 'border-bottom pb-3' : '' }}">
                <h6>
                    {{ $job->job_no }}
                    @if($job->status === 'completed')
                        <span class="badge bg-success">Completed</span>
                    @elseif($job->status === 'in_progress')
                        <span class="badge bg-primary">In Progress</span>
                    @elseif($job->status === 'assigned')
                        <span class="badge bg-info">Assigned</span>
                    @else
                        <span class="badge bg-secondary">{{ ucfirst($job->status) }}</span>
                    @endif
                </h6>
                
                @if($job->job_type)
                <p class="small text-muted mb-1">
                    <strong>Type:</strong> {{ ucfirst(str_replace('_', ' ', $job->job_type)) }}
                </p>
                @endif

                <p class="small text-muted mb-1">
                    <i class="bi bi-calendar me-1"></i>
                    {{ $job->scheduled_date ? \Carbon\Carbon::parse($job->scheduled_date)->format('d M Y') : 'Not Scheduled' }}
                </p>

                @if($job->completed_at)
                <p class="small text-success mb-0">
                    <i class="bi bi-check-circle me-1"></i>
                    Completed: {{ \Carbon\Carbon::parse($job->completed_at)->format('d M Y H:i') }}
                </p>
                @endif

                @if($job->status === 'assigned' || $job->status === 'in_progress')
                <button class="btn btn-sm btn-primary mt-2 w-100" 
                        onclick="alert('Job details available in Jobs section')">
                    <i class="bi bi-arrow-right-circle me-1"></i> Go to Job
                </button>
                @endif
            </div>
            @endforeach
        </div>
    </div>
    @else
    <div class="alert alert-info">
        <i class="bi bi-info-circle me-2"></i>No job history at this site yet.
    </div>
    @endif
</div>
@endsection
