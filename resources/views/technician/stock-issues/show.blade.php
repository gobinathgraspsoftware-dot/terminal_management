@extends('layouts.app')

@section('title', 'Stock Issue Details')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="mb-4">
        <h1 class="h3 mb-0">
            <i class="bi bi-file-text text-primary"></i> Stock Issue Details
        </h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('technician.dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('technician.stock-issues.index') }}">My Stock Issues</a></li>
                <li class="breadcrumb-item active">{{ $stockIssue->issue_no }}</li>
            </ol>
        </nav>
    </div>

    <!-- Status Alert -->
    @if($stockIssue->status === 'cancelled')
    <div class="alert alert-danger">
        <i class="bi bi-exclamation-triangle"></i> This stock issue has been cancelled.
    </div>
    @endif

    <!-- Header Information -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title mb-0"><i class="bi bi-info-circle"></i> Issue Information</h5>
                </div>
                <div class="card-body">
                    <table class="table table-sm mb-0">
                        <tr>
                            <th width="40%">Issue No:</th>
                            <td><strong>{{ $stockIssue->issue_no }}</strong></td>
                        </tr>
                        <tr>
                            <th>Issue Date:</th>
                            <td>{{ $stockIssue->issue_date->format('d M Y') }}</td>
                        </tr>
                        <tr>
                            <th>Type:</th>
                            <td>
                                @if($stockIssue->issue_type === 'issue_to_tech')
                                    <span class="badge bg-success">Received from Depot</span>
                                @else
                                    <span class="badge bg-primary">Returned to Depot</span>
                                @endif
                            </td>
                        </tr>
                        @if($stockIssue->issue_type === 'issue_to_tech')
                            <tr>
                                <th>From Depot:</th>
                                <td>
                                    <i class="bi bi-building text-primary"></i> 
                                    {{ $stockIssue->fromDepot->depot_name ?? '-' }}
                                </td>
                            </tr>
                        @else
                            <tr>
                                <th>Returned to Depot:</th>
                                <td>
                                    <i class="bi bi-building text-success"></i> 
                                    {{ $stockIssue->toDepot->depot_name ?? '-' }}
                                </td>
                            </tr>
                        @endif
                        <tr>
                            <th>Total Items:</th>
                            <td><span class="badge bg-info">{{ $stockIssue->total_items }}</span></td>
                        </tr>
                        <tr>
                            <th>Status:</th>
                            <td>
                                @if($stockIssue->status === 'draft')
                                    <span class="badge bg-secondary">Draft</span>
                                @elseif($stockIssue->status === 'posted')
                                    <span class="badge bg-success">Posted</span>
                                @else
                                    <span class="badge bg-danger">Cancelled</span>
                                @endif
                            </td>
                        </tr>
                        @if($stockIssue->remarks)
                        <tr>
                            <th>Remarks:</th>
                            <td>{{ $stockIssue->remarks }}</td>
                        </tr>
                        @endif
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Line Items -->
    <div class="card mb-4">
        <div class="card-header bg-light">
            <h5 class="card-title mb-0"><i class="bi bi-list-ul"></i> Items ({{ $stockIssue->lines->count() }})</h5>
        </div>
        <div class="card-body p-0">
            <div class="list-group list-group-flush">
                @forelse($stockIssue->lines as $line)
                <div class="list-group-item">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="flex-grow-1">
                            <h6 class="mb-1">{{ $line->model->model_name ?? '-' }}</h6>
                            <p class="mb-1">
                                @if($line->serial_no)
                                    <i class="bi bi-upc-scan"></i> <code>{{ $line->serial_no }}</code>
                                @else
                                    <span class="text-muted">Non-Serialized</span>
                                @endif
                            </p>
                            @if($line->remarks)
                                <small class="text-muted"><i class="bi bi-chat-left-text"></i> {{ $line->remarks }}</small>
                            @endif
                        </div>
                        <div class="text-end">
                            <span class="badge bg-info" style="font-size: 1rem;">x{{ number_format($line->quantity, 0) }}</span>
                        </div>
                    </div>
                </div>
                @empty
                <div class="list-group-item text-center text-muted">
                    No items found
                </div>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Back Button -->
    <div class="card">
        <div class="card-body">
            <a href="{{ route('technician.stock-issues.index') }}" class="btn btn-secondary w-100">
                <i class="bi bi-arrow-left"></i> Back to My Stock Issues
            </a>
        </div>
    </div>

    <!-- Audit Information -->
    <div class="card mt-3">
        <div class="card-body">
            <small class="text-muted d-block mb-1">
                <i class="bi bi-calendar"></i> Created: {{ $stockIssue->created_at->format('d M Y H:i') }}
            </small>
            @if($stockIssue->posted_at)
            <small class="text-muted d-block">
                <i class="bi bi-check-circle"></i> Posted: {{ $stockIssue->posted_at->format('d M Y H:i') }}
            </small>
            @endif
        </div>
    </div>
</div>
@endsection
