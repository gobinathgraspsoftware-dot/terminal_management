@extends('layouts.app')

@section('title', 'Claims Overview')

@section('content')
<div class="container-fluid">
    {{-- Page Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1">Claims Overview</h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('supervisor.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item active">Claims</li>
                </ol>
            </nav>
        </div>
        @if(!$isInternal)
        <a href="{{ route('supervisor.claims.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-circle me-1"></i> Submit Other Claim
        </a>
        @endif
    </div>

    {{-- Internal Supervisor Notice --}}
    @if($isInternal)
    <div class="alert alert-info d-flex align-items-center mb-4">
        <i class="bi bi-info-circle-fill me-2 fs-5"></i>
        <div>
            <strong>Internal Supervisor</strong> — Claims and charges are not applicable for internal supervisors.
            You can view your team's claims for oversight purposes.
        </div>
    </div>
    @endif

    {{-- Stats Cards --}}
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <div class="text-muted small">Ticket Claims</div>
                    <h3 class="mb-0 text-primary">{{ $stats['ticket_total'] }}</h3>
                    <small class="text-warning">{{ $stats['ticket_submitted'] }} submitted</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <div class="text-muted small">Other Claims</div>
                    <h3 class="mb-0 text-success">{{ $stats['other_total'] }}</h3>
                    <small class="text-warning">{{ $stats['other_submitted'] }} submitted</small>
                </div>
            </div>
        </div>
    </div>

    {{-- Tab Navigation --}}
    <ul class="nav nav-tabs mb-3" role="tablist">
        <li class="nav-item">
            <a class="nav-link {{ $activeTab === 'ticket' ? 'active' : '' }}"
               href="{{ route('supervisor.claims.ticket-claims') }}">
                <i class="bi bi-ticket-detailed me-1"></i> Ticket Claims
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ $activeTab === 'other' ? 'active' : '' }}"
               href="{{ route('supervisor.claims.other-claims') }}">
                <i class="bi bi-file-earmark-text me-1"></i> Other Claims
            </a>
        </li>
    </ul>
</div>
@endsection
