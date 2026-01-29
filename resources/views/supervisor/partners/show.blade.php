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
                    <li class="breadcrumb-item"><a href="{{ route('supervisor.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('supervisor.partners.index') }}">Partners</a></li>
                    <li class="breadcrumb-item active">{{ $partner->partner_code }}</li>
                </ol>
            </nav>
        </div>
        <a href="{{ route('supervisor.partners.index') }}" class="btn btn-secondary">
            <i class="bi bi-arrow-left me-1"></i> Back to List
        </a>
    </div>

    <!-- Partner Header Card -->
    <div class="card mb-4 border-0 shadow-sm">
        <div class="card-body">
            <div class="row align-items-center">
                <div class="col-auto">
                    <div class="avatar-lg bg-primary bg-opacity-10 rounded-circle d-flex align-items-center justify-content-center">
                        <i class="bi bi-building fs-1 text-primary"></i>
                    </div>
                </div>
                <div class="col">
                    <h4 class="mb-1">{{ $partner->partner_name }}</h4>
                    <p class="text-muted mb-2">
                        <span class="badge bg-secondary">{{ $partner->partner_code }}</span>
                        @if($partner->status === 'active')
                            <span class="badge bg-success ms-1">Active</span>
                        @else
                            <span class="badge bg-warning ms-1">Inactive</span>
                        @endif
                        <span class="badge bg-info ms-1">{{ ucfirst($partner->job_intake_method) }}</span>
                    </p>
                    @if($partner->city || $partner->state)
                        <p class="text-muted mb-0">
                            <i class="bi bi-geo-alt me-1"></i>
                            {{ implode(', ', array_filter([$partner->city, $partner->state])) }}
                        </p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="avatar-sm bg-primary bg-opacity-10 rounded">
                                <i class="bi bi-people text-primary fs-4 d-flex align-items-center justify-content-center h-100"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="text-muted mb-1">Total Clients</h6>
                            <h4 class="mb-0">{{ $partner->clients_count ?? $partner->clients->count() }}</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="avatar-sm bg-success bg-opacity-10 rounded">
                                <i class="bi bi-check-circle text-success fs-4 d-flex align-items-center justify-content-center h-100"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="text-muted mb-1">Active Clients</h6>
                            <h4 class="mb-0">{{ $partner->clients->where('status', 'active')->count() }}</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="avatar-sm bg-info bg-opacity-10 rounded">
                                <i class="bi bi-clipboard-check text-info fs-4 d-flex align-items-center justify-content-center h-100"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="text-muted mb-1">Total Jobs</h6>
                            <h4 class="mb-0">{{ $partner->job_orders_count ?? $partner->jobOrders->count() }}</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="avatar-sm bg-warning bg-opacity-10 rounded">
                                <i class="bi bi-hourglass-split text-warning fs-4 d-flex align-items-center justify-content-center h-100"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="text-muted mb-1">Pending Jobs</h6>
                            <h4 class="mb-0">{{ $partner->jobOrders->whereIn('status', ['pending_assignment', 'assigned', 'in_progress'])->count() }}</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabs Content -->
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white">
            <ul class="nav nav-tabs card-header-tabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="details-tab" data-bs-toggle="tab" data-bs-target="#details" type="button" role="tab">
                        <i class="bi bi-info-circle me-1"></i> Details
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="sla-tab" data-bs-toggle="tab" data-bs-target="#sla" type="button" role="tab">
                        <i class="bi bi-clock-history me-1"></i> SLA Rules
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="clients-tab" data-bs-toggle="tab" data-bs-target="#clients" type="button" role="tab">
                        <i class="bi bi-people me-1"></i> Clients
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="jobs-tab" data-bs-toggle="tab" data-bs-target="#jobs" type="button" role="tab">
                        <i class="bi bi-clipboard-check me-1"></i> Jobs
                    </button>
                </li>
            </ul>
        </div>
        <div class="card-body">
            <div class="tab-content">
                <!-- Details Tab -->
                <div class="tab-pane fade show active" id="details" role="tabpanel">
                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="text-primary mb-3">
                                <i class="bi bi-info-circle me-1"></i> Partner Information
                            </h6>
                            <table class="table table-borderless">
                                <tr>
                                    <td class="text-muted" style="width: 40%;">Partner Code</td>
                                    <td><strong>{{ $partner->partner_code }}</strong></td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Partner Name</td>
                                    <td>{{ $partner->partner_name }}</td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Job Intake Method</td>
                                    <td>
                                        @php
                                            $intakeBadge = match($partner->job_intake_method) {
                                                'manual' => 'secondary',
                                                'import' => 'info',
                                                'api' => 'primary',
                                                default => 'secondary'
                                            };
                                        @endphp
                                        <span class="badge bg-{{ $intakeBadge }}">{{ ucfirst($partner->job_intake_method) }}</span>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Status</td>
                                    <td>
                                        @if($partner->status === 'active')
                                            <span class="badge bg-success">Active</span>
                                        @else
                                            <span class="badge bg-warning">Inactive</span>
                                        @endif
                                    </td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-primary mb-3">
                                <i class="bi bi-person me-1"></i> Person In Charge
                            </h6>
                            <table class="table table-borderless">
                                <tr>
                                    <td class="text-muted" style="width: 40%;">Name</td>
                                    <td>{{ $partner->pic_name ?: '-' }}</td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Email</td>
                                    <td>
                                        @if($partner->pic_email)
                                            <a href="mailto:{{ $partner->pic_email }}">{{ $partner->pic_email }}</a>
                                        @else
                                            -
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Phone</td>
                                    <td>
                                        @if($partner->pic_phone)
                                            <a href="tel:{{ $partner->pic_phone }}">{{ $partner->pic_phone }}</a>
                                        @else
                                            -
                                        @endif
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>
                    <hr>
                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="text-primary mb-3">
                                <i class="bi bi-geo-alt me-1"></i> Address
                            </h6>
                            @if($partner->address || $partner->city || $partner->state)
                                <address class="mb-0">
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
                            @else
                                <p class="text-muted">No address provided</p>
                            @endif
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-primary mb-3">
                                <i class="bi bi-sticky me-1"></i> Notes
                            </h6>
                            <p class="mb-0">{{ $partner->notes ?: 'No notes' }}</p>
                        </div>
                    </div>
                </div>

                <!-- SLA Rules Tab -->
                <div class="tab-pane fade" id="sla" role="tabpanel">
                    @if($partner->sla_rules && count($partner->sla_rules) > 0)
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead class="table-light">
                                    <tr>
                                        <th>SLA Type</th>
                                        <th>Priority</th>
                                        <th class="text-center">Response Time</th>
                                        <th class="text-center">Resolution Time</th>
                                        <th class="text-center">Escalation</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($partner->sla_rules as $rule)
                                        <tr>
                                            <td>
                                                <span class="badge bg-secondary">{{ ucfirst($rule['sla_type'] ?? '-') }}</span>
                                            </td>
                                            <td>
                                                @php
                                                    $priorityBadge = match($rule['priority'] ?? 'normal') {
                                                        'low' => 'info',
                                                        'medium' => 'primary',
                                                        'high' => 'warning',
                                                        'critical' => 'danger',
                                                        default => 'secondary'
                                                    };
                                                @endphp
                                                <span class="badge bg-{{ $priorityBadge }}">{{ ucfirst($rule['priority'] ?? 'Normal') }}</span>
                                            </td>
                                            <td class="text-center">{{ $rule['response_hours'] ?? '-' }} hours</td>
                                            <td class="text-center">{{ $rule['resolution_hours'] ?? '-' }} hours</td>
                                            <td class="text-center">
                                                @if(!empty($rule['escalation_enabled']))
                                                    <span class="text-success">
                                                        <i class="bi bi-check-circle-fill"></i> 
                                                        After {{ $rule['escalation_hours'] ?? 0 }} hours
                                                    </span>
                                                @else
                                                    <span class="text-muted">
                                                        <i class="bi bi-x-circle"></i> Disabled
                                                    </span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center text-muted py-4">
                            <i class="bi bi-clock-history fs-1"></i>
                            <p class="mt-2 mb-0">No SLA rules configured</p>
                        </div>
                    @endif
                </div>

                <!-- Clients Tab -->
                <div class="tab-pane fade" id="clients" role="tabpanel">
                    @if($partner->clients->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>Client Code</th>
                                        <th>Client Name</th>
                                        <th>PIC</th>
                                        <th>Status</th>
                                        <th class="text-center">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($partner->clients->take(10) as $client)
                                        <tr>
                                            <td><code>{{ $client->client_code }}</code></td>
                                            <td>{{ $client->client_name }}</td>
                                            <td>{{ $client->pic_name ?: '-' }}</td>
                                            <td>
                                                @php
                                                    $clientStatusBadge = match($client->status) {
                                                        'active' => 'success',
                                                        'inactive' => 'warning',
                                                        'suspended' => 'danger',
                                                        default => 'secondary'
                                                    };
                                                @endphp
                                                <span class="badge bg-{{ $clientStatusBadge }}">{{ ucfirst($client->status) }}</span>
                                            </td>
                                            <td class="text-center">
                                                <a href="{{ route('supervisor.clients.show', $client->id) }}" class="btn btn-sm btn-outline-primary">
                                                    <i class="bi bi-eye"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        @if($partner->clients->count() > 10)
                            <div class="text-center mt-3">
                                <a href="{{ route('supervisor.clients.index', ['partner_id' => $partner->id]) }}" class="btn btn-outline-primary">
                                    View All {{ $partner->clients->count() }} Clients
                                </a>
                            </div>
                        @endif
                    @else
                        <div class="text-center text-muted py-4">
                            <i class="bi bi-people fs-1"></i>
                            <p class="mt-2 mb-0">No clients found for this partner</p>
                        </div>
                    @endif
                </div>

                <!-- Jobs Tab -->
                <div class="tab-pane fade" id="jobs" role="tabpanel">
                    @if($partner->jobOrders->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>Job No</th>
                                        <th>Type</th>
                                        <th>Client</th>
                                        <th>Scheduled</th>
                                        <th>Status</th>
                                        <th class="text-center">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($partner->jobOrders->sortByDesc('created_at')->take(10) as $job)
                                        <tr>
                                            <td><code>{{ $job->job_no }}</code></td>
                                            <td><span class="badge bg-secondary">{{ ucfirst($job->job_type) }}</span></td>
                                            <td>{{ $job->client->client_name ?? '-' }}</td>
                                            <td>{{ $job->scheduled_date ? $job->scheduled_date->format('d M Y') : '-' }}</td>
                                            <td>
                                                @php
                                                    $jobStatusBadge = match($job->status) {
                                                        'pending_assignment' => 'warning',
                                                        'assigned' => 'info',
                                                        'in_progress' => 'primary',
                                                        'pending_confirmation' => 'secondary',
                                                        'completed' => 'success',
                                                        'failed' => 'danger',
                                                        'rejected' => 'danger',
                                                        'cancelled' => 'dark',
                                                        default => 'secondary'
                                                    };
                                                @endphp
                                                <span class="badge bg-{{ $jobStatusBadge }}">{{ str_replace('_', ' ', ucfirst($job->status)) }}</span>
                                            </td>
                                            <td class="text-center">
                                                <a href="{{ route('supervisor.jobs.show', $job->id) }}" class="btn btn-sm btn-outline-primary">
                                                    <i class="bi bi-eye"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        @if($partner->jobOrders->count() > 10)
                            <div class="text-center mt-3">
                                <a href="{{ route('supervisor.jobs.index', ['partner_id' => $partner->id]) }}" class="btn btn-outline-primary">
                                    View All {{ $partner->jobOrders->count() }} Jobs
                                </a>
                            </div>
                        @endif
                    @else
                        <div class="text-center text-muted py-4">
                            <i class="bi bi-clipboard-check fs-1"></i>
                            <p class="mt-2 mb-0">No jobs found for this partner</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Audit Information -->
    <div class="card border-0 shadow-sm mt-4">
        <div class="card-body">
            <div class="row text-muted small">
                <div class="col-md-6">
                    <i class="bi bi-clock me-1"></i>
                    Created: {{ $partner->created_at->format('d M Y H:i') }}
                    @if($partner->createdBy)
                        by {{ $partner->createdBy->name }}
                    @endif
                </div>
                <div class="col-md-6 text-md-end">
                    <i class="bi bi-pencil me-1"></i>
                    Updated: {{ $partner->updated_at->format('d M Y H:i') }}
                    @if($partner->updatedBy)
                        by {{ $partner->updatedBy->name }}
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .avatar-lg {
        width: 80px;
        height: 80px;
    }
    .avatar-sm {
        width: 48px;
        height: 48px;
    }
    .nav-tabs .nav-link {
        color: #6c757d;
        border: none;
        padding: 0.75rem 1rem;
    }
    .nav-tabs .nav-link.active {
        color: #0d6efd;
        border-bottom: 2px solid #0d6efd;
        background: transparent;
    }
</style>
@endpush
