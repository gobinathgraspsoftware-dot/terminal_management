@extends('layouts.app')

@section('title', 'Vendor Details — ' . $vendor->vendor_name)

@section('content')
<div class="container-fluid">

    {{-- ── Page Header ──────────────────────────────────────────── --}}
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h4 class="mb-1 fw-bold">
                <i class="bi bi-truck me-2 text-primary"></i>Vendor Details
            </h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 small">
                    <li class="breadcrumb-item">
                        <a href="{{ route('supervisor.dashboard') }}">Dashboard</a>
                    </li>
                    <li class="breadcrumb-item">
                        <a href="{{ route('supervisor.vendors.index') }}">Vendors</a>
                    </li>
                    <li class="breadcrumb-item active">{{ $vendor->vendor_code }}</li>
                </ol>
            </nav>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('supervisor.vendors.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left me-1"></i>Back to List
            </a>
        </div>
    </div>

    {{-- ── Flash Messages ────────────────────────────────────────── --}}
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row g-4">

        {{-- ════════════════════════════════════════════
             LEFT COLUMN — main details
        ════════════════════════════════════════════ --}}
        <div class="col-lg-8">

            {{-- ── Vendor Info Card ─────────────────────────────── --}}
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-bottom py-3 d-flex align-items-center justify-content-between">
                    <h6 class="mb-0 fw-semibold">
                        <i class="bi bi-info-circle me-2 text-primary"></i>Vendor Information
                    </h6>
                    {{-- Status badge — null-safe: $vendor->status is an enum with a default, but guard anyway --}}
                    @if($vendor->status === 'active')
                        <span class="badge bg-success">Active</span>
                    @else
                        <span class="badge bg-secondary">Inactive</span>
                    @endif
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small text-muted fw-semibold mb-1">Vendor Code</label>
                            <div class="fw-bold font-monospace">{{ $vendor->vendor_code }}</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small text-muted fw-semibold mb-1">Vendor Name</label>
                            <div class="fw-semibold">{{ $vendor->vendor_name }}</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small text-muted fw-semibold mb-1">Company / Legal Name</label>
                            <div>{{ $vendor->company_name ?: '—' }}</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small text-muted fw-semibold mb-1">Vendor Type</label>
                            <div>
                                {{--
                                    FIX: $vendor->vendorType is a belongsTo — it can be NULL when no
                                    vendor_type_id is set. Calling ->isEmpty() on null was the crash.
                                    Guard with a simple @if($vendor->vendorType) check instead.
                                --}}
                                @if($vendor->vendorType)
                                    <span class="badge bg-primary">{{ $vendor->vendorType->title }}</span>
                                @elseif($vendor->vendor_type)
                                    {{-- Legacy enum fallback --}}
                                    @php
                                        $legacyLabels = [
                                            'supplier' => ['bg-primary',   'Supplier'],
                                            'subcon'   => ['bg-info',      'Sub-contractor'],
                                            'courier'  => ['bg-warning',   'Courier'],
                                            'other'    => ['bg-secondary', 'Other'],
                                        ];
                                        [$cls, $lbl] = $legacyLabels[$vendor->vendor_type] ?? ['bg-dark', ucfirst($vendor->vendor_type)];
                                    @endphp
                                    <span class="badge {{ $cls }}">{{ $lbl }}</span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small text-muted fw-semibold mb-1">Registration No.</label>
                            <div>{{ $vendor->registration_no ?: '—' }}</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small text-muted fw-semibold mb-1">Tax ID</label>
                            <div>{{ $vendor->tax_id ?: '—' }}</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small text-muted fw-semibold mb-1">Payment Terms</label>
                            <div>{{ $vendor->payment_terms ? $vendor->payment_terms . ' days' : '—' }}</div>
                        </div>
                        @if($vendor->notes)
                        <div class="col-12">
                            <label class="form-label small text-muted fw-semibold mb-1">Notes</label>
                            <div class="p-2 bg-light rounded small">{{ $vendor->notes }}</div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- ── PIC Card ─────────────────────────────────────── --}}
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-bottom py-3">
                    <h6 class="mb-0 fw-semibold">
                        <i class="bi bi-person-badge me-2 text-primary"></i>Person-in-Charge (PIC)
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label small text-muted fw-semibold mb-1">Name</label>
                            <div>{{ $vendor->pic_name ?: '—' }}</div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small text-muted fw-semibold mb-1">Email</label>
                            <div>
                                @if($vendor->pic_email)
                                    <a href="mailto:{{ $vendor->pic_email }}" class="text-decoration-none">
                                        <i class="bi bi-envelope me-1"></i>{{ $vendor->pic_email }}
                                    </a>
                                @else
                                    —
                                @endif
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small text-muted fw-semibold mb-1">Phone</label>
                            <div>
                                @if($vendor->pic_phone)
                                    <a href="tel:{{ $vendor->pic_phone }}" class="text-decoration-none">
                                        <i class="bi bi-telephone me-1"></i>{{ $vendor->pic_phone }}
                                    </a>
                                @else
                                    —
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ── Branches Card ────────────────────────────────── --}}
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-bottom py-3 d-flex align-items-center justify-content-between">
                    <h6 class="mb-0 fw-semibold">
                        <i class="bi bi-building me-2 text-primary"></i>Branches
                    </h6>
                    {{--
                        FIX: use ->count() on a HasMany relation (always returns int, never null).
                        Never use ->isEmpty() on a belongsTo that might be null.
                    --}}
                    <span class="badge bg-info text-dark">
                        {{ $vendor->branches->count() }} branch{{ $vendor->branches->count() !== 1 ? 'es' : '' }}
                    </span>
                </div>
                <div class="card-body p-0">
                    @if($vendor->branches->isEmpty())
                        <div class="text-center text-muted py-4">
                            <i class="bi bi-building-x fs-2 d-block mb-2"></i>
                            No branches registered for this vendor.
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="ps-3">Branch Name</th>
                                        <th>State / City</th>
                                        <th>Contact</th>
                                        <th class="text-center">Primary</th>
                                        <th class="text-center">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($vendor->branches as $branch)
                                    <tr>
                                        <td class="ps-3">
                                            <div class="fw-semibold">{{ $branch->branch_name }}</div>
                                            @if($branch->address)
                                                <small class="text-muted">{{ $branch->address }}</small>
                                            @endif
                                        </td>
                                        <td>
                                            {{--
                                                FIX: $branch->state and $branch->city are eager-loaded
                                                belongsTo — guard with ?-> to avoid null method calls.
                                            --}}
                                            @if($branch->state)
                                                <div class="small">{{ $branch->state->name }}</div>
                                            @endif
                                            @if($branch->city)
                                                <small class="text-muted">{{ $branch->city->name }}</small>
                                            @endif
                                            @if(!$branch->state && !$branch->city)
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($branch->contact_person)
                                                <div class="small fw-semibold">{{ $branch->contact_person }}</div>
                                            @endif
                                            @if($branch->contact_email)
                                                <small class="text-muted d-block">
                                                    <i class="bi bi-envelope me-1"></i>{{ $branch->contact_email }}
                                                </small>
                                            @endif
                                            @if($branch->contact_phone)
                                                <small class="text-muted d-block">
                                                    <i class="bi bi-telephone me-1"></i>{{ $branch->contact_phone }}
                                                </small>
                                            @endif
                                            @if(!$branch->contact_person && !$branch->contact_email && !$branch->contact_phone)
                                                <span class="text-muted small">—</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            @if($branch->is_primary)
                                                <span class="badge bg-primary"><i class="bi bi-star-fill me-1"></i>Primary</span>
                                            @else
                                                <span class="text-muted small">—</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            @if($branch->status === 'active')
                                                <span class="badge bg-success">Active</span>
                                            @else
                                                <span class="badge bg-secondary">Inactive</span>
                                            @endif
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>

        </div>{{-- /col-lg-8 --}}

        {{-- ════════════════════════════════════════════
             RIGHT COLUMN — sidebar info
        ════════════════════════════════════════════ --}}
        <div class="col-lg-4">

            {{-- ── Statistics Card ─────────────────────────────── --}}
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-bottom py-3">
                    <h6 class="mb-0 fw-semibold">
                        <i class="bi bi-bar-chart me-2 text-primary"></i>Statistics
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row g-3 text-center">
                        <div class="col-6">
                            <div class="fw-bold fs-4 text-primary">{{ $statistics['total_branches'] ?? 0 }}</div>
                            <div class="text-muted small">Total Branches</div>
                        </div>
                        <div class="col-6">
                            <div class="fw-bold fs-4 text-success">{{ $statistics['active_branches'] ?? 0 }}</div>
                            <div class="text-muted small">Active Branches</div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ── Bank Details Card ────────────────────────────── --}}
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-bottom py-3">
                    <h6 class="mb-0 fw-semibold">
                        <i class="bi bi-bank me-2 text-primary"></i>Bank Details
                    </h6>
                </div>
                <div class="card-body">
                    @if($vendor->bank_name || $vendor->bank_account_no)
                        <div class="mb-2">
                            <label class="form-label small text-muted fw-semibold mb-1">Bank Name</label>
                            <div>{{ $vendor->bank_name ?: '—' }}</div>
                        </div>
                        <div class="mb-2">
                            <label class="form-label small text-muted fw-semibold mb-1">Account Number</label>
                            <div class="font-monospace">{{ $vendor->bank_account_no ?: '—' }}</div>
                        </div>
                        <div>
                            <label class="form-label small text-muted fw-semibold mb-1">Account Name</label>
                            <div>{{ $vendor->bank_account_name ?: '—' }}</div>
                        </div>
                    @else
                        <div class="text-center text-muted py-2">
                            <i class="bi bi-bank2 d-block mb-1"></i>
                            <small>No bank details on record.</small>
                        </div>
                    @endif
                </div>
            </div>

            {{-- ── Record Audit Card ────────────────────────────── --}}
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-bottom py-3">
                    <h6 class="mb-0 fw-semibold">
                        <i class="bi bi-clock-history me-2 text-primary"></i>Record Info
                    </h6>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label small text-muted fw-semibold mb-1">Created At</label>
                        <div class="small">
                            {{ $vendor->created_at ? $vendor->created_at->format('d M Y, h:i A') : '—' }}
                        </div>
                        {{--
                            FIX: $vendor->createdBy is a belongsTo — guard with ?-> to avoid
                            calling ->name on null when created_by is NULL.
                        --}}
                        @if($vendor->createdBy)
                            <small class="text-muted">by {{ $vendor->createdBy->name }}</small>
                        @endif
                    </div>
                    <div>
                        <label class="form-label small text-muted fw-semibold mb-1">Last Updated</label>
                        <div class="small">
                            {{ $vendor->updated_at ? $vendor->updated_at->format('d M Y, h:i A') : '—' }}
                        </div>
                        {{--
                            FIX: same pattern — guard before accessing ->name on updatedBy.
                        --}}
                        @if($vendor->updatedBy)
                            <small class="text-muted">by {{ $vendor->updatedBy->name }}</small>
                        @endif
                    </div>
                </div>
            </div>

            {{-- ── Quick Actions Card ───────────────────────────── --}}
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom py-3">
                    <h6 class="mb-0 fw-semibold">
                        <i class="bi bi-lightning me-2 text-primary"></i>Quick Actions
                    </h6>
                </div>
                <div class="card-body d-grid gap-2">
                    <a href="{{ route('supervisor.vendors.index') }}" class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-arrow-left me-1"></i>Back to Vendor List
                    </a>
                    <a href="{{ route('supervisor.vendors.export') }}"
                       class="btn btn-outline-success btn-sm">
                        <i class="bi bi-file-earmark-excel me-1"></i>Export Vendors
                    </a>
                </div>
            </div>

        </div>{{-- /col-lg-4 --}}
    </div>{{-- /row --}}

</div>
@endsection

@push('scripts')
<script>
$(function () {
    'use strict';
    // No interactive actions on this view-only page.
    // SweetAlert2 + showToast() available from app.blade.php if needed in future.
});
</script>
@endpush
