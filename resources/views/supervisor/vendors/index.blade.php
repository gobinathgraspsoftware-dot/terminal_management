@extends('layouts.app')

@section('title', 'Vendors - TMS')

@section('content')
<div class="container-fluid">
    {{-- Page Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1"><i class="bi bi-truck"></i> Vendors</h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('supervisor.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item active">Vendors</li>
                </ol>
            </nav>
        </div>
        @can('export_vendors')
        <a href="{{ route('supervisor.vendors.export') }}" class="btn btn-outline-success">
            <i class="bi bi-download"></i> Export
        </a>
        @endcan
    </div>

    {{-- Statistics Cards --}}
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <h3 class="fw-bold text-primary mb-0">{{ $statistics['total'] ?? 0 }}</h3>
                    <small class="text-muted">Total Vendors</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <h3 class="fw-bold text-success mb-0">{{ $statistics['active'] ?? 0 }}</h3>
                    <small class="text-muted">Active</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <h3 class="fw-bold text-info mb-0">{{ $statistics['total_branches'] ?? 0 }}</h3>
                    <small class="text-muted">Total Branches</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <h3 class="fw-bold text-warning mb-0">{{ $statistics['active_purchase_orders'] ?? 0 }}</h3>
                    <small class="text-muted">Active POs</small>
                </div>
            </div>
        </div>
    </div>

    {{-- Filters --}}
    <div class="card mb-4">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Status</label>
                    <select class="form-select form-select-sm" id="filterStatus">
                        <option value="">All Status</option>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Vendor Type</label>
                    <select class="form-select form-select-sm" id="filterVendorType">
                        <option value="">All Types</option>
                        @foreach($vendorTypes as $type)
                            <option value="{{ $type->id }}">{{ $type->title }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button type="button" class="btn btn-sm btn-outline-secondary" id="btnResetFilters">
                        <i class="bi bi-x-circle"></i> Reset
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Vendors Table --}}
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table id="vendorsTable" class="table table-hover table-bordered w-100">
                    <thead class="table-light">
                        <tr>
                            <th>Code</th>
                            <th>Vendor Name</th>
                            <th>Type</th>
                            <th>PIC</th>
                            <th>Branches</th>
                            <th>Status</th>
                            <th width="60">Action</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    let table = $('#vendorsTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route("supervisor.vendors.datatable") }}',
            data: function(d) {
                d.status = $('#filterStatus').val();
                d.vendor_type = $('#filterVendorType').val();
            }
        },
        columns: [
            { data: 'vendor_code', name: 'vendor_code' },
            { data: 'vendor_name', name: 'vendor_name' },
            { data: 'vendor_type_badge', name: 'vendor_type', searchable: false, orderable: false },
            { data: 'pic_info', name: 'pic_name', searchable: false, orderable: false },
            { data: 'branches_count_display', name: 'branches_count', searchable: false, orderable: false },
            { data: 'status_badge', name: 'status', searchable: false, orderable: false },
            { data: 'actions', name: 'actions', searchable: false, orderable: false, className: 'text-center' }
        ],
        order: [[1, 'asc']],
        pageLength: 25,
        language: {
            emptyTable: 'No vendors found',
            processing: '<div class="spinner-border spinner-border-sm text-primary" role="status"><span class="visually-hidden">Loading...</span></div> Loading...'
        }
    });

    // Filter events
    $('#filterStatus, #filterVendorType').on('change', function() {
        table.ajax.reload();
    });

    $('#btnResetFilters').on('click', function() {
        $('#filterStatus').val('');
        $('#filterVendorType').val('');
        table.ajax.reload();
    });
});
</script>
@endpush
