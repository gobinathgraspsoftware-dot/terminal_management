@extends('layouts.app')

@section('title', 'Vendors — Supervisor View')

@section('content')
<div class="container-fluid">

    {{-- ── Page Header ──────────────────────────────────────────── --}}
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h4 class="mb-1 fw-bold">
                <i class="bi bi-truck me-2 text-primary"></i>Vendors
            </h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 small">
                    <li class="breadcrumb-item">
                        <a href="{{ route('supervisor.dashboard') }}">Dashboard</a>
                    </li>
                    <li class="breadcrumb-item active">Vendors</li>
                </ol>
            </nav>
        </div>
        <div class="d-flex gap-2">
            <button type="button" id="btnExport" class="btn btn-outline-success btn-sm">
                <i class="bi bi-file-earmark-excel me-1"></i>Export Excel
            </button>
        </div>
    </div>

    {{-- ── Flash Messages ────────────────────────────────────────── --}}
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- ── Statistics Cards ──────────────────────────────────────── --}}
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center py-3">
                    <div class="text-primary mb-1">
                        <i class="bi bi-truck fs-3"></i>
                    </div>
                    <div class="fw-bold fs-4">{{ number_format($statistics['total'] ?? 0) }}</div>
                    <div class="text-muted small">Total Vendors</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center py-3">
                    <div class="text-success mb-1">
                        <i class="bi bi-check-circle fs-3"></i>
                    </div>
                    <div class="fw-bold fs-4">{{ number_format($statistics['active'] ?? 0) }}</div>
                    <div class="text-muted small">Active</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center py-3">
                    <div class="text-secondary mb-1">
                        <i class="bi bi-pause-circle fs-3"></i>
                    </div>
                    <div class="fw-bold fs-4">{{ number_format($statistics['inactive'] ?? 0) }}</div>
                    <div class="text-muted small">Inactive</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center py-3">
                    <div class="text-info mb-1">
                        <i class="bi bi-building fs-3"></i>
                    </div>
                    <div class="fw-bold fs-4">{{ number_format($statistics['total_branches'] ?? 0) }}</div>
                    <div class="text-muted small">Total Branches</div>
                </div>
            </div>
        </div>
    </div>

    {{-- ── Filter Card ───────────────────────────────────────────── --}}
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body py-3">
            <div class="row g-2 align-items-end">

                {{-- Status Filter --}}
                <div class="col-6 col-md-3">
                    <label class="form-label small fw-semibold mb-1">Status</label>
                    <select id="filterStatus" class="form-select form-select-sm">
                        <option value="">All Statuses</option>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>

                {{-- Vendor Type Filter — DYNAMIC from DB ──────────────────────────────
                     $vendorTypes is passed by Supervisor\VendorController@index().
                     Using the DB collection prevents stale hardcoded data.
                ──────────────────────────────────────────────────────────────────── --}}
                <div class="col-6 col-md-3">
                    <label class="form-label small fw-semibold mb-1">Vendor Type</label>
                    <select id="filterVendorType" class="form-select form-select-sm">
                        <option value="">All Types</option>
                        @foreach($vendorTypes as $type)
                            <option value="{{ $type->id }}">{{ $type->title }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Clear --}}
                <div class="col-6 col-md-2">
                    <button type="button" id="btnClearFilters" class="btn btn-outline-secondary btn-sm w-100">
                        <i class="bi bi-x-circle me-1"></i>Clear
                    </button>
                </div>

            </div>
        </div>
    </div>

    {{-- ── DataTable Card ────────────────────────────────────────── --}}
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-bottom py-3">
            <h6 class="mb-0 fw-semibold">
                <i class="bi bi-list-ul me-2 text-primary"></i>Vendor List
                <small class="text-muted fw-normal">(view-only)</small>
            </h6>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table id="vendorsTable" class="table table-hover align-middle mb-0" style="width:100%">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3">#</th>
                            <th>Vendor Code</th>
                            <th>Vendor Name</th>
                            <th>Type</th>
                            <th>PIC</th>
                            <th>Branches</th>
                            <th>Status</th>
                            <th class="text-center">Action</th>
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
$(function () {
    'use strict';

    // ── DataTable initialisation ───────────────────────────────────
    var table = $('#vendorsTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route("supervisor.vendors.datatable") }}',
            data: function (d) {
                d.status      = $('#filterStatus').val();
                d.vendor_type = $('#filterVendorType').val();
            },
            error: function (xhr) {
                console.error('DataTable AJAX error:', xhr.responseText);
                showToast('Failed to load vendor data. Please refresh.', 'danger');
            }
        },
        columns: [
            { data: 'DT_RowIndex',            name: 'DT_RowIndex',     orderable: false, searchable: false, className: 'ps-3' },
            { data: 'vendor_code',             name: 'vendor_code' },
            { data: 'vendor_name',             name: 'vendor_name' },
            { data: 'vendor_type_badge',       name: 'vendor_type',     orderable: true,  searchable: false },
            { data: 'pic_info',                name: 'pic_name',        orderable: false, searchable: false },
            { data: 'branches_count_display',  name: 'branches_count',  orderable: true,  searchable: false },
            { data: 'status_badge',            name: 'status',          orderable: true,  searchable: false },
            { data: 'actions',                 name: 'actions',         orderable: false, searchable: false, className: 'text-center' },
        ],
        order: [[2, 'asc']],
        pageLength: 25,
        responsive: true,
        dom: '<"row align-items-center"<"col-md-6"l><"col-md-6 text-end"f>>rtip',
        language: {
            processing: '<div class="text-center py-3"><div class="spinner-border spinner-border-sm text-primary"></div> Loading…</div>',
            emptyTable: 'No vendors found.',
            zeroRecords: 'No vendors match your search / filter.',
        }
    });

    // ── Filter change → reload ────────────────────────────────────
    $('#filterStatus, #filterVendorType').on('change', function () {
        table.ajax.reload();
    });

    // ── Clear filters ─────────────────────────────────────────────
    $('#btnClearFilters').on('click', function () {
        $('#filterStatus, #filterVendorType').val('').trigger('change');
    });

    // ── Export ────────────────────────────────────────────────────
    $('#btnExport').on('click', function () {
        var params = $.param({
            status:      $('#filterStatus').val(),
            vendor_type: $('#filterVendorType').val(),
        });
        window.location.href = '{{ route("supervisor.vendors.export") }}?' + params;
    });
});
</script>
@endpush
