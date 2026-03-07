@extends('layouts.app')

@section('title', 'Vendor - ' . $vendor->vendor_name)

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">{{ $vendor->vendor_name }}</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.vendors.index') }}">Vendors</a></li>
                    <li class="breadcrumb-item active">{{ $vendor->vendor_code }}</li>
                </ol>
            </nav>
        </div>
        <div class="d-flex gap-2">
            @can('edit_vendors')
            <a href="{{ route('admin.vendors.edit', $vendor->id) }}" class="btn btn-primary">
                <i class="bi bi-pencil me-1"></i> Edit
            </a>
            @endcan
            <a href="{{ route('admin.vendors.index') }}" class="btn btn-secondary">
                <i class="bi bi-arrow-left me-1"></i> Back
            </a>
        </div>
    </div>

    {{-- Statistics --}}
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-2">
            <div class="card text-center">
                <div class="card-body py-3">
                    <div class="h4 mb-0 text-primary">{{ $statistics['total_pos'] ?? 0 }}</div>
                    <small class="text-muted">Total POs</small>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-2">
            <div class="card text-center">
                <div class="card-body py-3">
                    <div class="h4 mb-0 text-warning">{{ $statistics['active_pos'] ?? 0 }}</div>
                    <small class="text-muted">Active POs</small>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-2">
            <div class="card text-center">
                <div class="card-body py-3">
                    <div class="h4 mb-0 text-success">{{ $statistics['total_grns'] ?? 0 }}</div>
                    <small class="text-muted">GRNs</small>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-2">
            <div class="card text-center">
                <div class="card-body py-3">
                    <div class="h4 mb-0 text-info">{{ $statistics['total_branches'] ?? 0 }}</div>
                    <small class="text-muted">Branches</small>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-2">
            <div class="card text-center">
                <div class="card-body py-3">
                    <div class="h4 mb-0 text-danger">RM {{ number_format($statistics['outstanding_amount'] ?? 0, 2) }}</div>
                    <small class="text-muted">Outstanding</small>
                </div>
            </div>
        </div>
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
        <li class="nav-item">
            <a class="nav-link" data-bs-toggle="tab" href="#grns">
                <i class="bi bi-box-seam me-1"></i> GRNs
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" data-bs-toggle="tab" href="#aging">
                <i class="bi bi-clock-history me-1"></i> AP Aging
            </a>
        </li>
    </ul>

    <div class="tab-content">
        {{-- Details Tab --}}
        <div class="tab-pane fade show active" id="details">
            <div class="row g-4">
                <div class="col-md-6">
                    <div class="card h-100">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Basic Information</h5>
                        </div>
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
                                <tr><th class="text-muted">Registration No</th><td>{{ $vendor->registration_no ?? '-' }}</td></tr>
                                <tr><th class="text-muted">Tax ID</th><td>{{ $vendor->tax_id ?? '-' }}</td></tr>
                                <tr><th class="text-muted">Status</th><td>{!! $vendor->status_badge !!}</td></tr>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Person In Charge</h5>
                        </div>
                        <div class="card-body">
                            <table class="table table-borderless mb-0">
                                <tr><th class="text-muted" style="width:35%">Name</th><td>{{ $vendor->pic_name ?? '-' }}</td></tr>
                                <tr><th class="text-muted">Email</th><td>{{ $vendor->pic_email ?? '-' }}</td></tr>
                                <tr><th class="text-muted">Phone</th><td>{{ $vendor->pic_phone ?? '-' }}</td></tr>
                            </table>
                        </div>
                    </div>
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Bank & Payment</h5>
                        </div>
                        <div class="card-body">
                            <table class="table table-borderless mb-0">
                                <tr><th class="text-muted" style="width:35%">Bank</th><td>{{ $vendor->bank_name ?? '-' }}</td></tr>
                                <tr><th class="text-muted">Account No</th><td>{{ $vendor->bank_account_no ?? '-' }}</td></tr>
                                <tr><th class="text-muted">Account Name</th><td>{{ $vendor->bank_account_name ?? '-' }}</td></tr>
                                <tr><th class="text-muted">Payment Terms</th><td>{{ $vendor->payment_terms }} days</td></tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            @if($vendor->notes)
            <div class="card mt-4">
                <div class="card-header"><h5 class="card-title mb-0">Notes</h5></div>
                <div class="card-body">{{ $vendor->notes }}</div>
            </div>
            @endif
            <div class="card mt-4">
                <div class="card-body">
                    <small class="text-muted">
                        Created: {{ $vendor->created_at?->format('Y-m-d H:i') }}
                        @if($vendor->createdBy) by {{ $vendor->createdBy->name }} @endif
                        | Updated: {{ $vendor->updated_at?->format('Y-m-d H:i') }}
                        @if($vendor->updatedBy) by {{ $vendor->updatedBy->name }} @endif
                    </small>
                </div>
            </div>
        </div>

        {{-- Branches Tab --}}
        <div class="tab-pane fade" id="branches">
            @if($vendor->branches->isEmpty())
                <div class="alert alert-info">
                    <i class="bi bi-info-circle me-2"></i> No branches found for this vendor.
                </div>
            @else
                <div class="row g-3">
                    @foreach($vendor->branches as $branch)
                    <div class="col-md-6">
                        <div class="card h-100 {{ $branch->is_primary ? 'border-primary' : '' }}">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h6 class="mb-0">
                                    <i class="bi bi-geo-alt me-1"></i> {{ $branch->branch_name }}
                                </h6>
                                <div>
                                    @if($branch->is_primary)
                                        <span class="badge bg-primary me-1">Primary</span>
                                    @endif
                                    <span class="badge {{ $branch->status === 'active' ? 'bg-success' : 'bg-secondary' }}">
                                        {{ ucfirst($branch->status) }}
                                    </span>
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

        {{-- Purchase Orders Tab --}}
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
                                        <td>
                                            <a href="{{ route('admin.purchase-orders.show', $po->id) }}">
                                                {{ $po->po_number }}
                                            </a>
                                        </td>
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

        {{-- GRNs Tab --}}
        <div class="tab-pane fade" id="grns">
            @if($vendor->grns->isEmpty())
                <div class="alert alert-info"><i class="bi bi-info-circle me-2"></i> No GRNs found.</div>
            @else
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>GRN Number</th>
                                        <th>Date</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($vendor->grns as $grn)
                                    <tr>
                                        <td>
                                            <a href="{{ route('admin.grns.show', $grn->id) }}">
                                                {{ $grn->grn_number }}
                                            </a>
                                        </td>
                                        <td>{{ $grn->grn_date ? \Carbon\Carbon::parse($grn->grn_date)->format('Y-m-d') : '-' }}</td>
                                        <td><span class="badge bg-info">{{ ucfirst($grn->status) }}</span></td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @endif
        </div>

        {{-- AP Aging Tab --}}
        <div class="tab-pane fade" id="aging">
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered text-center">
                            <thead class="table-light">
                                <tr>
                                    <th>Current</th>
                                    <th>1-30 Days</th>
                                    <th>31-60 Days</th>
                                    <th>61-90 Days</th>
                                    <th>Over 90 Days</th>
                                    <th class="table-warning">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>RM {{ number_format($apAging['current'] ?? 0, 2) }}</td>
                                    <td>RM {{ number_format($apAging['1_30'] ?? 0, 2) }}</td>
                                    <td>RM {{ number_format($apAging['31_60'] ?? 0, 2) }}</td>
                                    <td>RM {{ number_format($apAging['61_90'] ?? 0, 2) }}</td>
                                    <td class="text-danger">RM {{ number_format($apAging['over_90'] ?? 0, 2) }}</td>
                                    <td class="table-warning fw-bold">RM {{ number_format($apAging['total'] ?? 0, 2) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
