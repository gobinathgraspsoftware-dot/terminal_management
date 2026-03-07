@extends('layouts.app')

@section('title', 'Vendor - ' . $vendor->vendor_name)

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">{{ $vendor->vendor_name }}</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('supervisor.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('supervisor.vendors.index') }}">Vendors</a></li>
                    <li class="breadcrumb-item active">{{ $vendor->vendor_code }}</li>
                </ol>
            </nav>
        </div>
        <a href="{{ route('supervisor.vendors.index') }}" class="btn btn-secondary">
            <i class="bi bi-arrow-left me-1"></i> Back
        </a>
    </div>

    {{-- Tabs --}}
    <ul class="nav nav-tabs mb-4" role="tablist">
        <li class="nav-item">
            <a class="nav-link active" data-bs-toggle="tab" href="#details">
                <i class="bi bi-info-circle me-1"></i> Details
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" data-bs-toggle="tab" href="#branches">
                <i class="bi bi-building me-1"></i> Branches
                <span class="badge bg-primary ms-1">{{ $vendor->branches->count() }}</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" data-bs-toggle="tab" href="#purchaseOrders">
                <i class="bi bi-file-earmark-text me-1"></i> Purchase Orders
            </a>
        </li>
    </ul>

    <div class="tab-content">
        {{-- Details --}}
        <div class="tab-pane fade show active" id="details">
            <div class="row g-4">
                <div class="col-md-6">
                    <div class="card h-100">
                        <div class="card-header"><h5 class="card-title mb-0">Basic Information</h5></div>
                        <div class="card-body">
                            <table class="table table-borderless mb-0">
                                <tr><th class="text-muted" style="width:35%">Vendor Code</th><td>{{ $vendor->vendor_code }}</td></tr>
                                <tr><th class="text-muted">Vendor Name</th><td>{{ $vendor->vendor_name }}</td></tr>
                                <tr><th class="text-muted">Type</th><td>{!! match($vendor->vendor_type) {
                                    'supplier' => '<span class="badge bg-primary">Supplier</span>',
                                    'subcon' => '<span class="badge bg-info">Sub-contractor</span>',
                                    'courier' => '<span class="badge bg-warning">Courier</span>',
                                    default => '<span class="badge bg-secondary">Other</span>',
                                } !!}</td></tr>
                                <tr><th class="text-muted">Company Name</th><td>{{ $vendor->company_name ?? '-' }}</td></tr>
                                <tr><th class="text-muted">Status</th><td>{!! $vendor->status_badge !!}</td></tr>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card h-100">
                        <div class="card-header"><h5 class="card-title mb-0">Contact (PIC)</h5></div>
                        <div class="card-body">
                            <table class="table table-borderless mb-0">
                                <tr><th class="text-muted" style="width:35%">Name</th><td>{{ $vendor->pic_name ?? '-' }}</td></tr>
                                <tr><th class="text-muted">Email</th><td>{{ $vendor->pic_email ?? '-' }}</td></tr>
                                <tr><th class="text-muted">Phone</th><td>{{ $vendor->pic_phone ?? '-' }}</td></tr>
                                <tr><th class="text-muted">Payment Terms</th><td>{{ $vendor->payment_terms }} days</td></tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Branches --}}
        <div class="tab-pane fade" id="branches">
            @if($vendor->branches->isEmpty())
                <div class="alert alert-info"><i class="bi bi-info-circle me-2"></i> No branches found.</div>
            @else
                <div class="row g-3">
                    @foreach($vendor->branches as $branch)
                    <div class="col-md-6">
                        <div class="card h-100 {{ $branch->is_primary ? 'border-primary' : '' }}">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h6 class="mb-0"><i class="bi bi-geo-alt me-1"></i> {{ $branch->branch_name }}</h6>
                                <div>
                                    @if($branch->is_primary)
                                        <span class="badge bg-primary me-1">Primary</span>
                                    @endif
                                    <span class="badge {{ $branch->status === 'active' ? 'bg-success' : 'bg-secondary' }}">{{ ucfirst($branch->status) }}</span>
                                </div>
                            </div>
                            <div class="card-body">
                                <table class="table table-sm table-borderless mb-0">
                                    <tr><th class="text-muted" style="width:30%">Address</th><td>{{ $branch->address ?? '-' }}</td></tr>
                                    <tr><th class="text-muted">State</th><td>{{ $branch->state?->name ?? '-' }}</td></tr>
                                    <tr><th class="text-muted">City</th><td>{{ $branch->city?->name ?? '-' }}</td></tr>
                                    <tr><th class="text-muted">Postcode</th><td>{{ $branch->postcode ?? '-' }}</td></tr>
                                    <tr><th class="text-muted">Country</th><td>{{ $branch->country ?? 'Malaysia' }}</td></tr>
                                    <tr><th class="text-muted">Contact</th><td>{{ $branch->contact_person ?? '-' }}</td></tr>
                                    <tr><th class="text-muted">Email</th><td>{{ $branch->contact_email ?? '-' }}</td></tr>
                                    <tr><th class="text-muted">Phone</th><td>{{ $branch->contact_phone ?? '-' }}</td></tr>
                                </table>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- Purchase Orders --}}
        <div class="tab-pane fade" id="purchaseOrders">
            @if($vendor->purchaseOrders->isEmpty())
                <div class="alert alert-info"><i class="bi bi-info-circle me-2"></i> No purchase orders found.</div>
            @else
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>PO Number</th>
                                        <th>Date</th>
                                        <th>Total</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($vendor->purchaseOrders as $po)
                                    <tr>
                                        <td>{{ $po->po_number }}</td>
                                        <td>{{ $po->po_date ? \Carbon\Carbon::parse($po->po_date)->format('Y-m-d') : '-' }}</td>
                                        <td>RM {{ number_format($po->total_amount, 2) }}</td>
                                        <td><span class="badge bg-info">{{ ucfirst(str_replace('_', ' ', $po->status)) }}</span></td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
