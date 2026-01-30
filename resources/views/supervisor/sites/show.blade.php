@extends('layouts.app')

@section('title', 'Site Details - ' . $site->site_code)

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">{{ $site->site_name }}</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('supervisor.sites.index') }}">Sites</a></li>
                    <li class="breadcrumb-item active">{{ $site->site_code }}</li>
                </ol>
            </nav>
        </div>
        <div>
            <a href="{{ route('supervisor.sites.index') }}" class="btn btn-secondary">
                <i class="bi bi-arrow-left me-1"></i> Back to List
            </a>
        </div>
    </div>

    <div class="row">
        <!-- Site Info Card -->
        <div class="col-lg-4 mb-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <i class="bi bi-info-circle me-2"></i>Site Information
                    </h5>
                </div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-5">Site Code:</dt>
                        <dd class="col-sm-7"><strong>{{ $site->site_code }}</strong></dd>

                        <dt class="col-sm-5">Client:</dt>
                        <dd class="col-sm-7">
                            {{ $site->client ? $site->client->client_name : '-' }}
                        </dd>

                        <dt class="col-sm-5">Status:</dt>
                        <dd class="col-sm-7">
                            @if($site->status === 'active')
                                <span class="badge bg-success">Active</span>
                            @else
                                <span class="badge bg-secondary">Inactive</span>
                            @endif
                        </dd>

                        <dt class="col-sm-5 border-top pt-2">Address:</dt>
                        <dd class="col-sm-7 border-top pt-2">{{ $site->full_address }}</dd>

                        @if($site->operating_hours)
                        <dt class="col-sm-5">Operating Hours:</dt>
                        <dd class="col-sm-7">{{ $site->operating_hours }}</dd>
                        @endif

                        @if($site->latitude && $site->longitude)
                        <dt class="col-sm-5 border-top pt-2">GPS:</dt>
                        <dd class="col-sm-7 border-top pt-2">
                            {{ $site->latitude }}, {{ $site->longitude }}
                            <br>
                            <a href="https://www.google.com/maps?q={{ $site->latitude }},{{ $site->longitude }}" 
                               target="_blank" class="btn btn-sm btn-outline-primary mt-1">
                                <i class="bi bi-map me-1"></i> View on Map
                            </a>
                        </dd>
                        @endif
                    </dl>

                    @if($site->notes)
                    <div class="alert alert-info mt-3">
                        <strong>Notes:</strong><br>
                        {{ $site->notes }}
                    </div>
                    @endif
                </div>
            </div>

            <!-- Contacts Card -->
            @if($site->contacts && $site->contacts->count() > 0)
            <div class="card border-0 shadow-sm mt-3">
                <div class="card-header bg-secondary text-white">
                    <h6 class="mb-0">
                        <i class="bi bi-people me-2"></i>Site Contacts
                    </h6>
                </div>
                <div class="card-body">
                    @foreach($site->contacts as $contact)
                    <div class="mb-3 {{ !$loop->last ? 'border-bottom pb-3' : '' }}">
                        <h6 class="mb-1">
                            {{ $contact->contact_name }}
                            @if($contact->is_primary)
                                <span class="badge bg-success">Primary</span>
                            @endif
                        </h6>
                        @if($contact->contact_title)
                            <p class="text-muted small mb-1">{{ $contact->contact_title }}</p>
                        @endif
                        @if($contact->contact_phone)
                            <p class="mb-1">
                                <i class="bi bi-telephone me-1"></i>
                                <a href="tel:{{ $contact->contact_phone }}">{{ $contact->contact_phone }}</a>
                            </p>
                        @endif
                        @if($contact->contact_email)
                            <p class="mb-0">
                                <i class="bi bi-envelope me-1"></i>
                                <a href="mailto:{{ $contact->contact_email }}">{{ $contact->contact_email }}</a>
                            </p>
                        @endif
                    </div>
                    @endforeach
                </div>
            </div>
            @endif
        </div>

        <!-- Tabs Section -->
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom">
                    <ul class="nav nav-tabs card-header-tabs" id="site-tabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="assets-tab" data-bs-toggle="tab" 
                                    data-bs-target="#assets" type="button" role="tab">
                                <i class="bi bi-box-seam me-1"></i> Installed Assets
                                <span class="badge bg-info ms-1">{{ $site->siteAssets->count() }}</span>
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="jobs-tab" data-bs-toggle="tab" 
                                    data-bs-target="#jobs" type="button" role="tab">
                                <i class="bi bi-clipboard-check me-1"></i> Team Job History
                            </button>
                        </li>
                    </ul>
                </div>
                <div class="card-body">
                    <div class="tab-content" id="site-tabs-content">
                        <!-- Assets Tab -->
                        <div class="tab-pane fade show active" id="assets" role="tabpanel">
                            @include('supervisor.sites._asset_tab')
                        </div>

                        <!-- Jobs Tab -->
                        <div class="tab-pane fade" id="jobs" role="tabpanel">
                            @include('supervisor.sites._job_history_tab')
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
