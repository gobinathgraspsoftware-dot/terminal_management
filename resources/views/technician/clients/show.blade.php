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
                    <li class="breadcrumb-item"><a href="{{ route('technician.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('technician.clients.index') }}">Clients</a></li>
                    <li class="breadcrumb-item active">{{ $client->client_code }}</li>
                </ol>
            </nav>
        </div>
        <div>
            <a href="{{ route('technician.clients.index') }}" class="btn btn-secondary">
                <i class="bi bi-arrow-left me-1"></i> Back to List
            </a>
        </div>
    </div>

    <!-- Client Information -->
    <div class="row">
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Client Information</h5>
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
                            <td><strong>Partner:</strong></td>
                            <td>
                                @if($client->partner)
                                {{ $client->partner->partner_name }}
                                @else
                                -
                                @endif
                            </td>
                        </tr>
                    </table>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Address</h5>
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
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Contact Information</h5>
                </div>
                <div class="card-body">
                    @if($client->contacts->count() > 0)
                    <div class="list-group list-group-flush">
                        @foreach($client->contacts->where('status', 'active') as $contact)
                        <div class="list-group-item px-0">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <h6 class="mb-1">
                                        {{ $contact->contact_name }}
                                        @if($contact->is_primary)
                                        <span class="badge bg-success ms-1">Primary</span>
                                        @endif
                                    </h6>
                                    @if($contact->contact_title)
                                    <small class="text-muted d-block">{{ $contact->contact_title }}</small>
                                    @endif
                                    @if($contact->contact_email)
                                    <small class="text-muted d-block">
                                        <i class="bi bi-envelope me-1"></i>
                                        <a href="mailto:{{ $contact->contact_email }}">{{ $contact->contact_email }}</a>
                                    </small>
                                    @endif
                                    @if($contact->contact_phone)
                                    <small class="text-muted d-block">
                                        <i class="bi bi-telephone me-1"></i>
                                        <a href="tel:{{ $contact->contact_phone }}">{{ $contact->contact_phone }}</a>
                                    </small>
                                    @endif
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                    @else
                    <p class="text-muted mb-0">No contacts available</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Partner Contact (if available) -->
    @if($client->partner && $client->partner->pic_name)
    <div class="row mt-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Partner Contact</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <p class="mb-1"><strong>Partner:</strong> {{ $client->partner->partner_name }}</p>
                        </div>
                        <div class="col-md-4">
                            <p class="mb-1"><strong>PIC:</strong> {{ $client->partner->pic_name ?? '-' }}</p>
                        </div>
                        <div class="col-md-4">
                            <p class="mb-1"><strong>Phone:</strong> 
                                @if($client->partner->pic_phone)
                                <a href="tel:{{ $client->partner->pic_phone }}">{{ $client->partner->pic_phone }}</a>
                                @else
                                -
                                @endif
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
@endsection
