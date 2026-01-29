@extends('layouts.app')

@section('title', 'Client Details')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">{{ $client->client_name }}</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('supervisor.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('supervisor.clients.index') }}">Clients</a></li>
                    <li class="breadcrumb-item active">{{ $client->client_code }}</li>
                </ol>
            </nav>
        </div>
        <div>
            <a href="{{ route('supervisor.clients.index') }}" class="btn btn-secondary">
                <i class="bi bi-arrow-left me-1"></i> Back to List
            </a>
        </div>
    </div>

    <!-- Client Info Card -->
    <div class="row mb-4">
        <div class="col-md-8">
            <div class="card">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p class="mb-2"><strong>Client Code:</strong> {{ $client->client_code }}</p>
                            <p class="mb-2"><strong>Company:</strong> {{ $client->company_name ?? '-' }}</p>
                            <p class="mb-2"><strong>Partner:</strong> 
                                {{ $client->partner ? $client->partner->partner_name : '-' }}
                            </p>
                        </div>
                        <div class="col-md-6">
                            <p class="mb-2"><strong>Status:</strong> {!! $client->status_badge !!}</p>
                            <p class="mb-2"><strong>Payment Terms:</strong> {{ $client->payment_terms }} days</p>
                            <p class="mb-2"><strong>Credit Limit:</strong> 
                                @if($client->credit_limit)
                                RM {{ number_format($client->credit_limit, 2) }}
                                @else
                                No Limit
                                @endif
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <h6 class="text-muted mb-3">Quick Stats</h6>
                    <p class="mb-2"><strong>Sites:</strong> {{ $statistics['total_sites'] }}</p>
                    <p class="mb-2"><strong>Jobs:</strong> {{ $statistics['total_jobs'] }}</p>
                    <p class="mb-2"><strong>Invoices:</strong> {{ $statistics['total_invoices'] }}</p>
                    <p class="mb-0"><strong>Outstanding:</strong> 
                        <span class="text-danger">RM {{ number_format($statistics['total_outstanding'], 2) }}</span>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabs -->
    <ul class="nav nav-tabs mb-4" id="clientTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="details-tab" data-bs-toggle="tab" data-bs-target="#details" type="button">
                <i class="bi bi-info-circle me-1"></i> Details
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="contacts-tab" data-bs-toggle="tab" data-bs-target="#contacts" type="button">
                <i class="bi bi-people me-1"></i> Contacts ({{ $client->contacts->count() }})
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="aging-tab" data-bs-toggle="tab" data-bs-target="#aging" type="button">
                <i class="bi bi-clock-history me-1"></i> Aging
            </button>
        </li>
    </ul>

    <!-- Tab Content -->
    <div class="tab-content" id="clientTabsContent">
        <!-- Details Tab -->
        <div class="tab-pane fade show active" id="details" role="tabpanel">
            <div class="row">
                <div class="col-md-6">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Company Information</h5>
                        </div>
                        <div class="card-body">
                            <table class="table table-sm table-borderless">
                                <tr>
                                    <td width="40%"><strong>Client Code:</strong></td>
                                    <td>{{ $client->client_code }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Client Name:</strong></td>
                                    <td>{{ $client->client_name }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Company Name:</strong></td>
                                    <td>{{ $client->company_name ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Registration No:</strong></td>
                                    <td>{{ $client->registration_no ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Tax ID:</strong></td>
                                    <td>{{ $client->tax_id ?? '-' }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Billing Address</h5>
                        </div>
                        <div class="card-body">
                            @if($client->billing_address)
                                <p class="mb-1">{{ $client->billing_address }}</p>
                                <p class="mb-1">{{ $client->billing_postcode }} {{ $client->billing_city }}</p>
                                <p class="mb-1">{{ $client->billing_state }}</p>
                                <p class="mb-0">{{ $client->billing_country }}</p>
                            @else
                                <p class="text-muted mb-0">No address provided</p>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Financial Information</h5>
                        </div>
                        <div class="card-body">
                            <table class="table table-sm table-borderless">
                                <tr>
                                    <td width="40%"><strong>Payment Terms:</strong></td>
                                    <td>{{ $client->payment_terms }} days</td>
                                </tr>
                                <tr>
                                    <td><strong>Credit Limit:</strong></td>
                                    <td>
                                        @if($client->credit_limit)
                                        RM {{ number_format($client->credit_limit, 2) }}
                                        @else
                                        No Limit
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>Total Outstanding:</strong></td>
                                    <td class="text-danger"><strong>RM {{ number_format($statistics['total_outstanding'], 2) }}</strong></td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Primary Contact</h5>
                        </div>
                        <div class="card-body">
                            <table class="table table-sm table-borderless">
                                <tr>
                                    <td width="40%"><strong>Name:</strong></td>
                                    <td>{{ $client->pic_name ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Email:</strong></td>
                                    <td>{{ $client->pic_email ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Phone:</strong></td>
                                    <td>{{ $client->pic_phone ?? '-' }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Contacts Tab -->
        <div class="tab-pane fade" id="contacts" role="tabpanel">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Client Contacts</h5>
                </div>
                <div class="card-body">
                    @if($client->contacts->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Title</th>
                                    <th>Email</th>
                                    <th>Phone</th>
                                    <th>Primary</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($client->contacts as $contact)
                                <tr>
                                    <td>{{ $contact->contact_name }}</td>
                                    <td>{{ $contact->contact_title ?? '-' }}</td>
                                    <td>{{ $contact->contact_email ?? '-' }}</td>
                                    <td>{{ $contact->contact_phone ?? '-' }}</td>
                                    <td>
                                        @if($contact->is_primary)
                                        <span class="badge bg-success">Primary</span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge bg-{{ $contact->status == 'active' ? 'success' : 'secondary' }}">
                                            {{ ucfirst($contact->status) }}
                                        </span>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @else
                    <p class="text-muted mb-0">No contacts found for this client.</p>
                    @endif
                </div>
            </div>
        </div>

        <!-- Aging Tab -->
        <div class="tab-pane fade" id="aging" role="tabpanel">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Aging Summary</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-2">
                            <div class="card text-center">
                                <div class="card-body">
                                    <h6 class="text-muted">Current</h6>
                                    <h4 class="text-success">RM {{ number_format($agingSummary['current'], 2) }}</h4>
                                    <small class="text-muted">Not Due</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="card text-center bg-warning bg-opacity-10">
                                <div class="card-body">
                                    <h6 class="text-muted">1-30 Days</h6>
                                    <h4 class="text-warning">RM {{ number_format($agingSummary['1-30'], 2) }}</h4>
                                    <small class="text-muted">Overdue</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="card text-center bg-danger bg-opacity-10">
                                <div class="card-body">
                                    <h6 class="text-muted">31-60 Days</h6>
                                    <h4 class="text-danger">RM {{ number_format($agingSummary['31-60'], 2) }}</h4>
                                    <small class="text-muted">Overdue</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="card text-center bg-danger bg-opacity-25">
                                <div class="card-body">
                                    <h6 class="text-muted">61-90 Days</h6>
                                    <h4 class="text-danger">RM {{ number_format($agingSummary['61-90'], 2) }}</h4>
                                    <small class="text-muted">Overdue</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="card text-center bg-dark text-white">
                                <div class="card-body">
                                    <h6>Over 90 Days</h6>
                                    <h4>RM {{ number_format($agingSummary['over_90'], 2) }}</h4>
                                    <small>Overdue</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="card text-center border-primary">
                                <div class="card-body">
                                    <h6 class="text-muted">Total</h6>
                                    <h4 class="text-primary">RM {{ number_format($agingSummary['total'], 2) }}</h4>
                                    <small class="text-muted">Outstanding</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
