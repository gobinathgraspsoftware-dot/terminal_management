@extends('layouts.app')

@section('title', 'Vendor Details - TMS')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-0"><i class="bi bi-truck me-2"></i>{{ $vendor->vendor_name }}</h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.vendors.index') }}">Vendors</a></li>
                    <li class="breadcrumb-item active">{{ $vendor->vendor_code }}</li>
                </ol>
            </nav>
        </div>
        <div>
            @can('edit_vendors')
            <a href="{{ route('admin.vendors.edit', $vendor->id) }}" class="btn btn-primary">
                <i class="bi bi-pencil me-2"></i>Edit
            </a>
            @endcan
        </div>
    </div>

    <!-- Statistics -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="bg-primary bg-opacity-10 rounded p-3">
                                <i class="bi bi-cart fs-3 text-primary"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="text-muted mb-1">Total POs</h6>
                            <h3 class="mb-0">{{ $statistics['total_pos'] }}</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="bg-success bg-opacity-10 rounded p-3">
                                <i class="bi bi-box-seam fs-3 text-success"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="text-muted mb-1">GRNs</h6>
                            <h3 class="mb-0">{{ $statistics['total_grns'] }}</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="bg-warning bg-opacity-10 rounded p-3">
                                <i class="bi bi-file-earmark-text fs-3 text-warning"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="text-muted mb-1">Pending Invoices</h6>
                            <h3 class="mb-0">{{ $statistics['pending_invoices'] }}</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="bg-danger bg-opacity-10 rounded p-3">
                                <i class="bi bi-currency-dollar fs-3 text-danger"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="text-muted mb-1">Outstanding</h6>
                            <h4 class="mb-0">RM {{ number_format($statistics['outstanding_amount'], 2) }}</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <!-- Vendor Details -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="bi bi-info-circle me-2"></i>Vendor Information</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <h6 class="text-muted mb-1">Vendor Code</h6>
                            <p class="mb-0"><strong>{{ $vendor->vendor_code }}</strong></p>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-muted mb-1">Vendor Name</h6>
                            <p class="mb-0"><strong>{{ $vendor->vendor_name }}</strong></p>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-muted mb-1">Vendor Type</h6>
                            <p class="mb-0">{{ ucfirst($vendor->vendor_type) }}</p>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-muted mb-1">Status</h6>
                            <p class="mb-0">{!! $vendor->status_badge !!}</p>
                        </div>
                        @if($vendor->company_name)
                        <div class="col-md-6">
                            <h6 class="text-muted mb-1">Company Name</h6>
                            <p class="mb-0">{{ $vendor->company_name }}</p>
                        </div>
                        @endif
                        @if($vendor->registration_no)
                        <div class="col-md-6">
                            <h6 class="text-muted mb-1">Registration No</h6>
                            <p class="mb-0">{{ $vendor->registration_no }}</p>
                        </div>
                        @endif
                        @if($vendor->tax_id)
                        <div class="col-md-6">
                            <h6 class="text-muted mb-1">Tax ID</h6>
                            <p class="mb-0">{{ $vendor->tax_id }}</p>
                        </div>
                        @endif
                        <div class="col-md-6">
                            <h6 class="text-muted mb-1">Payment Terms</h6>
                            <p class="mb-0">{{ $vendor->payment_terms }} days</p>
                        </div>
                    </div>

                    @if($vendor->address || $vendor->city)
                    <hr>
                    <h6 class="text-muted mb-2">Address</h6>
                    <p class="mb-0">{{ $vendor->full_address }}</p>
                    @endif

                    @if($vendor->pic_name)
                    <hr>
                    <div class="row">
                        <div class="col-md-4">
                            <h6 class="text-muted mb-1">PIC Name</h6>
                            <p class="mb-0">{{ $vendor->pic_name }}</p>
                        </div>
                        @if($vendor->pic_email)
                        <div class="col-md-4">
                            <h6 class="text-muted mb-1">PIC Email</h6>
                            <p class="mb-0"><a href="mailto:{{ $vendor->pic_email }}">{{ $vendor->pic_email }}</a></p>
                        </div>
                        @endif
                        @if($vendor->pic_phone)
                        <div class="col-md-4">
                            <h6 class="text-muted mb-1">PIC Phone</h6>
                            <p class="mb-0"><a href="tel:{{ $vendor->pic_phone }}">{{ $vendor->pic_phone }}</a></p>
                        </div>
                        @endif
                    </div>
                    @endif

                    @if($vendor->bank_name)
                    <hr>
                    <div class="row">
                        <div class="col-md-4">
                            <h6 class="text-muted mb-1">Bank Name</h6>
                            <p class="mb-0">{{ $vendor->bank_name }}</p>
                        </div>
                        <div class="col-md-4">
                            <h6 class="text-muted mb-1">Account Number</h6>
                            <p class="mb-0">{{ $vendor->bank_account_no }}</p>
                        </div>
                        <div class="col-md-4">
                            <h6 class="text-muted mb-1">Account Name</h6>
                            <p class="mb-0">{{ $vendor->bank_account_name }}</p>
                        </div>
                    </div>
                    @endif

                    @if($vendor->notes)
                    <hr>
                    <h6 class="text-muted mb-2">Notes</h6>
                    <p class="mb-0">{{ $vendor->notes }}</p>
                    @endif
                </div>
            </div>

            <!-- Tabs -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">
                    <ul class="nav nav-tabs card-header-tabs" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link active" data-bs-toggle="tab" href="#po-history">
                                <i class="bi bi-cart me-2"></i>PO History
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-bs-toggle="tab" href="#ap-aging">
                                <i class="bi bi-currency-dollar me-2"></i>AP Aging
                            </a>
                        </li>
                    </ul>
                </div>
                <div class="card-body">
                    <div class="tab-content">
                        <!-- PO History Tab -->
                        <div class="tab-pane fade show active" id="po-history">
                            @if($vendor->purchaseOrders->count() > 0)
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead class="table-light">
                                        <tr>
                                            <th>PO No</th>
                                            <th>Date</th>
                                            <th>Amount</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($vendor->purchaseOrders as $po)
                                        <tr>
                                            <td><strong>{{ $po->po_no }}</strong></td>
                                            <td>{{ $po->po_date->format('d M Y') }}</td>
                                            <td>RM {{ number_format($po->total_amount, 2) }}</td>
                                            <td>@include('components.status-badge', ['status' => $po->status, 'type' => 'po'])</td>
                                            <td>
                                                <a href="#" class="btn btn-sm btn-info">
                                                    <i class="bi bi-eye"></i>
                                                </a>
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            @else
                            <div class="text-center text-muted py-4">
                                <i class="bi bi-cart-x fs-1"></i>
                                <p class="mt-2">No purchase orders yet</p>
                            </div>
                            @endif
                        </div>

                        <!-- AP Aging Tab -->
                        <div class="tab-pane fade" id="ap-aging">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <div class="card border-success">
                                        <div class="card-body">
                                            <h6 class="text-muted mb-1">Current</h6>
                                            <h4 class="mb-0 text-success">RM {{ number_format($apAging['current'], 2) }}</h4>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="card border-warning">
                                        <div class="card-body">
                                            <h6 class="text-muted mb-1">1-30 Days</h6>
                                            <h4 class="mb-0 text-warning">RM {{ number_format($apAging['1_30'], 2) }}</h4>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="card border-orange">
                                        <div class="card-body">
                                            <h6 class="text-muted mb-1">31-60 Days</h6>
                                            <h4 class="mb-0 text-orange">RM {{ number_format($apAging['31_60'], 2) }}</h4>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="card border-danger">
                                        <div class="card-body">
                                            <h6 class="text-muted mb-1">Over 60 Days</h6>
                                            <h4 class="mb-0 text-danger">RM {{ number_format($apAging['61_90'] + $apAging['over_90'], 2) }}</h4>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <hr>
                            <div class="card border-primary">
                                <div class="card-body">
                                    <h6 class="text-muted mb-1">Total Outstanding</h6>
                                    <h3 class="mb-0 text-primary">RM {{ number_format($apAging['total'], 2) }}</h3>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            @include('components.audit-trail', ['model' => $vendor])
        </div>
    </div>
</div>

@endsection
