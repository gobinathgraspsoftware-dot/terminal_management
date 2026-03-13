@extends('layouts.app')

@section('title', 'View Vendor - TMS')

@section('content')
<div class="container-fluid">
    {{-- Page Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1"><i class="bi bi-truck"></i> {{ $vendor->vendor_name }}</h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('supervisor.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('supervisor.vendors.index') }}">Vendors</a></li>
                    <li class="breadcrumb-item active">{{ $vendor->vendor_code }}</li>
                </ol>
            </nav>
        </div>
        <a href="{{ route('supervisor.vendors.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Back to List
        </a>
    </div>

    <div class="row">
        {{-- Left Column --}}
        <div class="col-md-8">
            {{-- Basic Information --}}
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0"><i class="bi bi-info-circle"></i> Basic Information</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label text-muted">Vendor Code</label>
                            <p class="fw-bold mb-0">{{ $vendor->vendor_code }}</p>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label text-muted">Vendor Name</label>
                            <p class="fw-bold mb-0">{{ $vendor->vendor_name }}</p>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label text-muted">Vendor Type</label>
                            <p class="mb-0">
                                @if($vendor->vendorType)
                                    <span class="badge bg-primary">{{ $vendor->vendorType->title }}</span>
                                @else
                                    <span class="badge bg-secondary">{{ ucfirst($vendor->vendor_type ?? 'N/A') }}</span>
                                @endif
                            </p>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label text-muted">Company Name</label>
                            <p class="mb-0">{{ $vendor->company_name ?? '-' }}</p>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label text-muted">Registration No</label>
                            <p class="mb-0">{{ $vendor->registration_no ?? '-' }}</p>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label text-muted">Tax ID</label>
                            <p class="mb-0">{{ $vendor->tax_id ?? '-' }}</p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Contact Information --}}
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0"><i class="bi bi-person-lines-fill"></i> Person In Charge</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label text-muted">PIC Name</label>
                            <p class="mb-0">{{ $vendor->pic_name ?? '-' }}</p>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label text-muted">PIC Email</label>
                            <p class="mb-0">
                                @if($vendor->pic_email)
                                    <a href="mailto:{{ $vendor->pic_email }}">{{ $vendor->pic_email }}</a>
                                @else
                                    -
                                @endif
                            </p>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label text-muted">PIC Phone</label>
                            <p class="mb-0">
                                @if($vendor->pic_phone)
                                    <a href="tel:{{ $vendor->pic_phone }}">{{ $vendor->pic_phone }}</a>
                                @else
                                    -
                                @endif
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Branches --}}
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-geo-alt"></i> Branches
                        <span class="badge bg-primary ms-2">{{ $vendor->branches->count() }}</span>
                    </h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Branch Name</th>
                                    <th>Address</th>
                                    <th>State / City</th>
                                    <th>Contact</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($vendor->branches as $branch)
                                <tr>
                                    <td>
                                        <strong>{{ $branch->branch_name }}</strong>
                                        @if($branch->is_primary)
                                            <span class="badge bg-warning text-dark ms-1">Primary</span>
                                        @endif
                                    </td>
                                    <td>
                                        {{ $branch->address ?? '-' }}
                                        @if($branch->postcode)
                                            <br><small class="text-muted">{{ $branch->postcode }}</small>
                                        @endif
                                    </td>
                                    <td>
                                        {{ $branch->state?->name ?? '-' }}
                                        @if($branch->city)
                                            / {{ $branch->city->name }}
                                        @endif
                                    </td>
                                    <td>
                                        @if($branch->contact_person)
                                            <strong>{{ $branch->contact_person }}</strong><br>
                                        @endif
                                        @if($branch->contact_phone)
                                            <small class="text-muted"><i class="bi bi-telephone"></i> {{ $branch->contact_phone }}</small><br>
                                        @endif
                                        @if($branch->contact_email)
                                            <small class="text-muted"><i class="bi bi-envelope"></i> {{ $branch->contact_email }}</small>
                                        @endif
                                        @if(!$branch->contact_person && !$branch->contact_phone && !$branch->contact_email)
                                            -
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge bg-{{ ($branch->status ?? 'active') === 'active' ? 'success' : 'secondary' }}">
                                            {{ ucfirst($branch->status ?? 'active') }}
                                        </span>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-3">No branches found</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {{-- Recent Purchase Orders --}}
            @if($vendor->purchaseOrders && $vendor->purchaseOrders->count() > 0)
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0"><i class="bi bi-cart"></i> Recent Purchase Orders</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
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
                                    <td>{{ $po->po_number ?? '-' }}</td>
                                    <td>{{ $po->po_date ? \Carbon\Carbon::parse($po->po_date)->format('d/m/Y') : '-' }}</td>
                                    <td>RM {{ number_format($po->total_amount ?? 0, 2) }}</td>
                                    <td><span class="badge bg-info">{{ ucfirst($po->status ?? '-') }}</span></td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            @endif

            {{-- Recent GRNs --}}
            @if($vendor->grns && $vendor->grns->count() > 0)
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0"><i class="bi bi-box-seam"></i> Recent GRNs</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>GRN Number</th>
                                    <th>Date</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($vendor->grns as $grn)
                                <tr>
                                    <td>{{ $grn->grn_number ?? '-' }}</td>
                                    <td>{{ $grn->grn_date ? \Carbon\Carbon::parse($grn->grn_date)->format('d/m/Y') : '-' }}</td>
                                    <td><span class="badge bg-info">{{ ucfirst($grn->status ?? '-') }}</span></td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            @endif
        </div>

        {{-- Right Column --}}
        <div class="col-md-4">
            {{-- Status Card --}}
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0"><i class="bi bi-gear"></i> Status & Payment</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label text-muted">Status</label>
                        <div>
                            <span class="badge bg-{{ $vendor->status === 'active' ? 'success' : 'secondary' }} fs-6">
                                {{ ucfirst($vendor->status ?? 'N/A') }}
                            </span>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-muted">Payment Terms</label>
                        <p class="mb-0">{{ $vendor->payment_terms ? $vendor->payment_terms . ' days' : 'Not Set' }}</p>
                    </div>
                </div>
            </div>

            {{-- Bank Details --}}
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0"><i class="bi bi-bank"></i> Bank Details</h5>
                </div>
                <div class="card-body">
                    @if($vendor->bank_name)
                    <div class="mb-2">
                        <label class="form-label text-muted">Bank Name</label>
                        <p class="mb-0">{{ $vendor->bank_name }}</p>
                    </div>
                    <div class="mb-2">
                        <label class="form-label text-muted">Account No</label>
                        <p class="mb-0">{{ $vendor->bank_account_no ?? '-' }}</p>
                    </div>
                    <div class="mb-0">
                        <label class="form-label text-muted">Account Name</label>
                        <p class="mb-0">{{ $vendor->bank_account_name ?? '-' }}</p>
                    </div>
                    @else
                    <p class="text-muted mb-0">No bank details provided</p>
                    @endif
                </div>
            </div>

            {{-- Statistics --}}
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0"><i class="bi bi-graph-up"></i> Statistics</h5>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Total POs</span>
                        <strong>{{ $statistics['total_pos'] ?? 0 }}</strong>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Active POs</span>
                        <strong>{{ $statistics['active_pos'] ?? 0 }}</strong>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Total GRNs</span>
                        <strong>{{ $statistics['total_grns'] ?? 0 }}</strong>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Total Branches</span>
                        <strong>{{ $statistics['total_branches'] ?? 0 }}</strong>
                    </div>
                    <hr>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Total PO Amount</span>
                        <strong>RM {{ number_format($statistics['total_amount_po'] ?? 0, 2) }}</strong>
                    </div>
                </div>
            </div>

            {{-- Notes --}}
            @if($vendor->notes)
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0"><i class="bi bi-sticky"></i> Notes</h5>
                </div>
                <div class="card-body">
                    <p class="mb-0">{{ $vendor->notes }}</p>
                </div>
            </div>
            @endif

            {{-- Audit Info --}}
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0"><i class="bi bi-clock-history"></i> Audit</h5>
                </div>
                <div class="card-body">
                    <div class="mb-2">
                        <small class="text-muted">Created</small>
                        <p class="mb-0">
                            {{ $vendor->created_at?->format('d/m/Y H:i') ?? '-' }}
                            @if($vendor->createdBy)
                                <br><small class="text-muted">by {{ $vendor->createdBy->name }}</small>
                            @endif
                        </p>
                    </div>
                    <div class="mb-0">
                        <small class="text-muted">Last Updated</small>
                        <p class="mb-0">
                            {{ $vendor->updated_at?->format('d/m/Y H:i') ?? '-' }}
                            @if($vendor->updatedBy)
                                <br><small class="text-muted">by {{ $vendor->updatedBy->name }}</small>
                            @endif
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
