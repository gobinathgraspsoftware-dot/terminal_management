@extends('layouts.app')

@section('title', 'Partner Details - TMS')

@section('content')
<!-- Page Header -->
<div class="page-header">
    <div class="row align-items-center">
        <div class="col">
            <h1><i class="bi bi-building me-2"></i>Partner Details</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.partners.index') }}">Partners</a></li>
                    <li class="breadcrumb-item active">{{ $partner->partner_code }}</li>
                </ol>
            </nav>
        </div>
        <div class="col-auto">
            @can('partners.edit')
            <a href="{{ route('admin.partners.edit', $partner->id) }}" class="btn btn-primary me-2">
                <i class="bi bi-pencil me-1"></i> Edit
            </a>
            @endcan
            <a href="{{ route('admin.partners.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Back to List
            </a>
        </div>
    </div>
</div>

<!-- Partner Header Card -->
<div class="card mb-4">
    <div class="card-body">
        <div class="row">
            <div class="col-md-8">
                <div class="d-flex align-items-start">
                    <div class="bg-primary bg-opacity-10 rounded-circle p-3 me-4">
                        <i class="bi bi-building fs-1 text-primary"></i>
                    </div>
                    <div>
                        <h3 class="mb-1">{{ $partner->partner_name }}</h3>
                        <p class="text-muted mb-2">
                            <span class="badge bg-secondary me-2">{{ $partner->partner_code }}</span>
                            {!! $partner->status_badge !!}
                            {!! $partner->job_intake_method_badge !!}
                        </p>
                        @if($partner->full_address)
                        <p class="mb-0">
                            <i class="bi bi-geo-alt text-muted me-1"></i>
                            {{ $partner->full_address }}
                        </p>
                        @endif
                    </div>
                </div>
            </div>
            <div class="col-md-4 text-md-end">
                <div class="mb-2">
                    <small class="text-muted">Created:</small><br>
                    {{ $partner->created_at->format('Y-m-d H:i') }}
                    @if($partner->createdBy)
                    <br><small class="text-muted">by {{ $partner->createdBy->name }}</small>
                    @endif
                </div>
                @if($partner->updated_at != $partner->created_at)
                <div>
                    <small class="text-muted">Updated:</small><br>
                    {{ $partner->updated_at->format('Y-m-d H:i') }}
                    @if($partner->updatedBy)
                    <br><small class="text-muted">by {{ $partner->updatedBy->name }}</small>
                    @endif
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Statistics Cards -->
<div class="row mb-4">
    <div class="col-md-3 col-sm-6 mb-3">
        <div class="stats-card h-100">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="stats-value">{{ $statistics['total_clients'] ?? 0 }}</div>
                    <div class="stats-label">Total Clients</div>
                    <small class="text-success">{{ $statistics['active_clients'] ?? 0 }} active</small>
                </div>
                <div class="stats-icon bg-primary bg-opacity-10 text-primary">
                    <i class="bi bi-people"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6 mb-3">
        <div class="stats-card h-100">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="stats-value">{{ $statistics['total_jobs'] ?? 0 }}</div>
                    <div class="stats-label">Total Jobs</div>
                    <small class="text-warning">{{ $statistics['pending_jobs'] ?? 0 }} pending</small>
                </div>
                <div class="stats-icon bg-info bg-opacity-10 text-info">
                    <i class="bi bi-briefcase"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6 mb-3">
        <div class="stats-card h-100">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="stats-value">{{ $statistics['total_invoices'] ?? 0 }}</div>
                    <div class="stats-label">Total Invoices</div>
                    <small class="text-warning">{{ $statistics['pending_invoices'] ?? 0 }} pending</small>
                </div>
                <div class="stats-icon bg-success bg-opacity-10 text-success">
                    <i class="bi bi-receipt"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6 mb-3">
        <div class="stats-card h-100">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="stats-value">RM {{ number_format($statistics['total_revenue'] ?? 0, 2) }}</div>
                    <div class="stats-label">Total Revenue</div>
                    <small class="text-danger">RM {{ number_format($statistics['outstanding_amount'] ?? 0, 2) }} outstanding</small>
                </div>
                <div class="stats-icon bg-warning bg-opacity-10 text-warning">
                    <i class="bi bi-currency-dollar"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Tabs -->
<ul class="nav nav-tabs" id="partnerTabs" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link active" id="details-tab" data-bs-toggle="tab" data-bs-target="#details" type="button">
            <i class="bi bi-info-circle me-1"></i> Details
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="sla-tab" data-bs-toggle="tab" data-bs-target="#sla" type="button">
            <i class="bi bi-clock-history me-1"></i> SLA Rules
            <span class="badge bg-secondary ms-1">{{ $statistics['sla_rules_count'] ?? 0 }}</span>
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="clients-tab" data-bs-toggle="tab" data-bs-target="#clients" type="button">
            <i class="bi bi-people me-1"></i> Clients
            <span class="badge bg-secondary ms-1">{{ $statistics['total_clients'] ?? 0 }}</span>
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="jobs-tab" data-bs-toggle="tab" data-bs-target="#jobs" type="button">
            <i class="bi bi-briefcase me-1"></i> Jobs
            <span class="badge bg-secondary ms-1">{{ $statistics['total_jobs'] ?? 0 }}</span>
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="invoices-tab" data-bs-toggle="tab" data-bs-target="#invoices" type="button">
            <i class="bi bi-receipt me-1"></i> Invoices
            <span class="badge bg-secondary ms-1">{{ $statistics['total_invoices'] ?? 0 }}</span>
        </button>
    </li>
</ul>

<div class="tab-content" id="partnerTabsContent">
    <!-- Details Tab -->
    <div class="tab-pane fade show active" id="details" role="tabpanel">
        <div class="card border-top-0 rounded-top-0">
            <div class="card-body">
                <div class="row">
                    <!-- Partner Information -->
                    <div class="col-md-6">
                        <h6 class="text-muted mb-3"><i class="bi bi-building me-2"></i>Partner Information</h6>
                        <table class="table table-borderless">
                            <tr>
                                <td class="text-muted" width="40%">Partner Code</td>
                                <td><strong>{{ $partner->partner_code }}</strong></td>
                            </tr>
                            <tr>
                                <td class="text-muted">Partner Name</td>
                                <td>{{ $partner->partner_name }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted">Status</td>
                                <td>{!! $partner->status_badge !!}</td>
                            </tr>
                            <tr>
                                <td class="text-muted">Job Intake Method</td>
                                <td>{!! $partner->job_intake_method_badge !!}</td>
                            </tr>
                            @if($partner->job_intake_method === 'api' && $partner->api_key)
                            <tr>
                                <td class="text-muted">API Key</td>
                                <td>
                                    <code id="apiKey" class="user-select-all">{{ Str::mask($partner->api_key, '*', 8) }}</code>
                                    <button type="button" class="btn btn-sm btn-link" id="toggleApiKey" data-visible="false">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                </td>
                            </tr>
                            @endif
                        </table>
                    </div>

                    <!-- Contact Information -->
                    <div class="col-md-6">
                        <h6 class="text-muted mb-3"><i class="bi bi-person me-2"></i>Person in Charge (PIC)</h6>
                        <table class="table table-borderless">
                            <tr>
                                <td class="text-muted" width="40%">Name</td>
                                <td>{{ $partner->pic_name ?? '-' }}</td>
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
                    <!-- Address -->
                    <div class="col-md-6">
                        <h6 class="text-muted mb-3"><i class="bi bi-geo-alt me-2"></i>Address</h6>
                        <table class="table table-borderless">
                            <tr>
                                <td class="text-muted" width="40%">Address</td>
                                <td>{{ $partner->address ?? '-' }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted">City</td>
                                <td>{{ $partner->city ?? '-' }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted">State</td>
                                <td>{{ $partner->state ?? '-' }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted">Postcode</td>
                                <td>{{ $partner->postcode ?? '-' }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted">Country</td>
                                <td>{{ $partner->country ?? 'Malaysia' }}</td>
                            </tr>
                        </table>
                    </div>

                    <!-- Notes -->
                    <div class="col-md-6">
                        <h6 class="text-muted mb-3"><i class="bi bi-sticky me-2"></i>Notes</h6>
                        <div class="bg-light p-3 rounded">
                            {{ $partner->notes ?? 'No notes available.' }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- SLA Rules Tab -->
    <div class="tab-pane fade" id="sla" role="tabpanel">
        <div class="card border-top-0 rounded-top-0">
            <div class="card-body">
                @if($partner->sla_rules && count($partner->sla_rules) > 0)
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>SLA Type</th>
                                <th>Priority</th>
                                <th>Response Time</th>
                                <th>Resolution Time</th>
                                <th>Escalation</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($partner->sla_rules as $rule)
                            <tr>
                                <td>{{ $rule['sla_type'] ?? '-' }}</td>
                                <td>
                                    @php
                                        $priorityClass = match($rule['priority'] ?? 'medium') {
                                            'critical' => 'danger',
                                            'high' => 'warning',
                                            'medium' => 'info',
                                            'low' => 'secondary',
                                            default => 'secondary'
                                        };
                                    @endphp
                                    <span class="badge bg-{{ $priorityClass }}">{{ ucfirst($rule['priority'] ?? 'medium') }}</span>
                                </td>
                                <td>{{ $rule['response_hours'] ?? 24 }} hours</td>
                                <td>{{ $rule['resolution_hours'] ?? 48 }} hours</td>
                                <td>
                                    @if(!empty($rule['escalation_enabled']))
                                    <span class="text-success"><i class="bi bi-check-circle"></i> {{ $rule['escalation_hours'] ?? 'N/A' }} hours</span>
                                    @else
                                    <span class="text-muted">Disabled</span>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @else
                <div class="text-center py-5">
                    <i class="bi bi-clock-history fs-1 text-muted"></i>
                    <p class="text-muted mt-2">No SLA rules configured for this partner.</p>
                </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Clients Tab -->
    <div class="tab-pane fade" id="clients" role="tabpanel">
        <div class="card border-top-0 rounded-top-0">
            <div class="card-body">
                @if($partner->clients->count() > 0)
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Client Code</th>
                                <th>Client Name</th>
                                <th>City</th>
                                <th>Status</th>
                                <th>Created</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($partner->clients as $client)
                            <tr>
                                <td><a href="#">{{ $client->client_code }}</a></td>
                                <td>{{ $client->client_name }}</td>
                                <td>{{ $client->city ?? '-' }}</td>
                                <td>
                                    <span class="badge bg-{{ $client->status === 'active' ? 'success' : 'secondary' }}">
                                        {{ ucfirst($client->status) }}
                                    </span>
                                </td>
                                <td>{{ $client->created_at->format('Y-m-d') }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @if($partner->clients->count() >= 10)
                <div class="text-center mt-3">
                    <a href="#" class="btn btn-outline-primary btn-sm">View All Clients</a>
                </div>
                @endif
                @else
                <div class="text-center py-5">
                    <i class="bi bi-people fs-1 text-muted"></i>
                    <p class="text-muted mt-2">No clients associated with this partner.</p>
                </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Jobs Tab -->
    <div class="tab-pane fade" id="jobs" role="tabpanel">
        <div class="card border-top-0 rounded-top-0">
            <div class="card-body">
                @if($partner->jobOrders->count() > 0)
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Job No</th>
                                <th>Type</th>
                                <th>Status</th>
                                <th>Created</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($partner->jobOrders as $job)
                            <tr>
                                <td><a href="#">{{ $job->job_number }}</a></td>
                                <td>{{ $job->job_type ?? '-' }}</td>
                                <td>
                                    <span class="badge bg-secondary">{{ ucfirst($job->status) }}</span>
                                </td>
                                <td>{{ $job->created_at->format('Y-m-d') }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @if($partner->jobOrders->count() >= 10)
                <div class="text-center mt-3">
                    <a href="#" class="btn btn-outline-primary btn-sm">View All Jobs</a>
                </div>
                @endif
                @else
                <div class="text-center py-5">
                    <i class="bi bi-briefcase fs-1 text-muted"></i>
                    <p class="text-muted mt-2">No jobs associated with this partner.</p>
                </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Invoices Tab -->
    <div class="tab-pane fade" id="invoices" role="tabpanel">
        <div class="card border-top-0 rounded-top-0">
            <div class="card-body">
                @if($partner->invoices->count() > 0)
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Invoice No</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($partner->invoices as $invoice)
                            <tr>
                                <td><a href="#">{{ $invoice->invoice_number }}</a></td>
                                <td>RM {{ number_format($invoice->total_amount, 2) }}</td>
                                <td>
                                    <span class="badge bg-{{ $invoice->status === 'paid' ? 'success' : ($invoice->status === 'pending' ? 'warning' : 'secondary') }}">
                                        {{ ucfirst($invoice->status) }}
                                    </span>
                                </td>
                                <td>{{ $invoice->created_at->format('Y-m-d') }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @if($partner->invoices->count() >= 10)
                <div class="text-center mt-3">
                    <a href="#" class="btn btn-outline-primary btn-sm">View All Invoices</a>
                </div>
                @endif
                @else
                <div class="text-center py-5">
                    <i class="bi bi-receipt fs-1 text-muted"></i>
                    <p class="text-muted mt-2">No invoices associated with this partner.</p>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Toggle API Key visibility
    @if($partner->job_intake_method === 'api' && $partner->api_key)
    const apiKeyFull = "{{ $partner->api_key }}";
    const apiKeyMasked = "{{ Str::mask($partner->api_key, '*', 8) }}";
    
    $('#toggleApiKey').on('click', function() {
        let isVisible = $(this).data('visible');
        if (isVisible) {
            $('#apiKey').text(apiKeyMasked);
            $(this).find('i').removeClass('bi-eye-slash').addClass('bi-eye');
            $(this).data('visible', false);
        } else {
            $('#apiKey').text(apiKeyFull);
            $(this).find('i').removeClass('bi-eye').addClass('bi-eye-slash');
            $(this).data('visible', true);
        }
    });
    @endif
});
</script>
@endpush
