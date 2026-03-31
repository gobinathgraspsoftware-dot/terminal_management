@extends('layouts.app')

@section('title', 'Vendors — Admin')

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
                        <a href="{{ route('admin.dashboard') }}">Dashboard</a>
                    </li>
                    <li class="breadcrumb-item active">Vendors</li>
                </ol>
            </nav>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            @can('create_vendors')
            <a href="{{ route('admin.vendors.create') }}" class="btn btn-primary btn-sm">
                <i class="bi bi-plus-circle me-1"></i>Add Vendor
            </a>
            @endcan
            @can('create_vendors')
            <button type="button" id="btnImport" class="btn btn-outline-primary btn-sm">
                <i class="bi bi-upload me-1"></i>Import
            </button>
            @endcan
            <button type="button" id="btnExport" class="btn btn-outline-success btn-sm">
                <i class="bi bi-file-earmark-excel me-1"></i>Export
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
                    <div class="text-primary mb-1"><i class="bi bi-truck fs-3"></i></div>
                    <div class="fw-bold fs-4">{{ number_format($statistics['total'] ?? 0) }}</div>
                    <div class="text-muted small">Total Vendors</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center py-3">
                    <div class="text-success mb-1"><i class="bi bi-check-circle fs-3"></i></div>
                    <div class="fw-bold fs-4">{{ number_format($statistics['active'] ?? 0) }}</div>
                    <div class="text-muted small">Active</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center py-3">
                    <div class="text-secondary mb-1"><i class="bi bi-pause-circle fs-3"></i></div>
                    <div class="fw-bold fs-4">{{ number_format($statistics['inactive'] ?? 0) }}</div>
                    <div class="text-muted small">Inactive</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center py-3">
                    <div class="text-info mb-1"><i class="bi bi-building fs-3"></i></div>
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
                <div class="col-6 col-md-2">
                    <label class="form-label small fw-semibold mb-1">Status</label>
                    <select id="filterStatus" class="form-select form-select-sm">
                        <option value="">All Statuses</option>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>

                {{-- Vendor Type Filter — DYNAMIC from DB ──────────────────────────────
                     $vendorTypes passed by Admin\VendorController@index() from DB.
                     Value sent is $type->id (numeric) → controller filters by vendor_type_id FK.
                ─────────────────────────────────────────────────────────────────── --}}
                <div class="col-6 col-md-2">
                    <label class="form-label small fw-semibold mb-1">Vendor Type</label>
                    <select id="filterVendorType" class="form-select form-select-sm">
                        <option value="">All Types</option>
                        @foreach($vendorTypes as $type)
                            <option value="{{ $type->id }}">{{ $type->title }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Show Trashed --}}
                @can('restore_vendors')
                <div class="col-6 col-md-2">
                    <label class="form-label small fw-semibold mb-1">Show</label>
                    <select id="filterTrashed" class="form-select form-select-sm">
                        <option value="false">Active Only</option>
                        <option value="true">Include Deleted</option>
                    </select>
                </div>
                @endcan

                {{-- Clear --}}
                <div class="col-6 col-md-2">
                    <button type="button" id="btnClearFilters"
                            class="btn btn-outline-secondary btn-sm w-100">
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
            </h6>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table id="vendorsTable"
                       class="table table-hover align-middle mb-0"
                       style="width:100%">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3">#</th>
                            <th>Vendor Code</th>
                            <th>Vendor Name</th>
                            <th>Type</th>
                            <th>PIC</th>
                            <th>Bank</th>
                            <th>Branches</th>
                            <th>Terms</th>
                            <th>Status</th>
                            <th>Created</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>

</div>

{{-- ══════════════════════════════════════════════════════════════
     IMPORT MODAL
══════════════════════════════════════════════════════════════ --}}
@can('create_vendors')
<div class="modal fade" id="importModal" tabindex="-1" aria-labelledby="importModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-bottom">
                <h5 class="modal-title fw-semibold" id="importModalLabel">
                    <i class="bi bi-upload me-2 text-primary"></i>Import Vendors
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted small mb-3">
                    Upload an Excel file (.xlsx / .xls / .csv) to bulk-import vendors.
                    Download the
                    <a href="{{ route('admin.vendors.import-template') }}" class="text-primary">
                        <i class="bi bi-file-earmark-arrow-down me-1"></i>import template
                    </a>
                    first.
                </p>
                <div class="mb-3">
                    <label for="importFile" class="form-label fw-semibold small">Select File</label>
                    <input type="file" class="form-control" id="importFile"
                           accept=".xlsx,.xls,.csv">
                </div>
                <div id="importProgress" class="d-none">
                    <div class="progress mb-2">
                        <div class="progress-bar progress-bar-striped progress-bar-animated w-100"></div>
                    </div>
                    <small class="text-muted">Uploading and processing…</small>
                </div>
                <div id="importResult" class="d-none"></div>
            </div>
            <div class="modal-footer border-top">
                <button type="button" class="btn btn-outline-secondary btn-sm"
                        data-bs-dismiss="modal">Cancel</button>
                <button type="button" id="btnDoImport"
                        class="btn btn-primary btn-sm">
                    <i class="bi bi-upload me-1"></i>Import
                </button>
            </div>
        </div>
    </div>
</div>
@endcan
@endsection

@push('scripts')
<script>
$(function () {
    'use strict';

    var CSRF = $('meta[name="csrf-token"]').attr('content');

    // ── DataTable ─────────────────────────────────────────────────
    var table = $('#vendorsTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route("admin.vendors.datatable") }}',
            data: function (d) {
                d.status       = $('#filterStatus').val();
                d.vendor_type  = $('#filterVendorType').val();
                d.show_trashed = $('#filterTrashed').val() || 'false';
            },
            error: function (xhr) {
                console.error('DataTable AJAX error:', xhr.responseText);
                showToast('Failed to load vendor data. Please refresh.', 'danger');
            }
        },
        columns: [
            { data: 'DT_RowIndex',           name: 'DT_RowIndex',     orderable: false, searchable: false, className: 'ps-3' },
            { data: 'vendor_code',            name: 'vendor_code' },
            { data: 'vendor_name',            name: 'vendor_name' },
            { data: 'vendor_type_badge',      name: 'vendor_type',     orderable: true,  searchable: false },
            { data: 'pic_info',               name: 'pic_name',        orderable: false, searchable: false },
            { data: 'bank_info',              name: 'bank_name',       orderable: false, searchable: false },
            { data: 'branches_count_display', name: 'branches_count',  orderable: true,  searchable: false },
            { data: 'payment_terms_display',  name: 'payment_terms',   orderable: true,  searchable: false },
            { data: 'status_badge',           name: 'status',          orderable: true,  searchable: false },
            { data: 'created_info',           name: 'created_at',      orderable: true,  searchable: false },
            { data: 'actions',                name: 'actions',         orderable: false, searchable: false, className: 'text-center' },
        ],
        order: [[2, 'asc']],
        pageLength: 25,
        responsive: true,
        dom: '<"row align-items-center"<"col-md-6"l><"col-md-6 text-end"f>>rtip',
        language: {
            processing:   '<div class="text-center py-3"><div class="spinner-border spinner-border-sm text-primary"></div> Loading…</div>',
            emptyTable:   'No vendors found.',
            zeroRecords:  'No vendors match your search / filter.',
        }
    });

    // ── Filters ───────────────────────────────────────────────────
    $('#filterStatus, #filterVendorType, #filterTrashed').on('change', function () {
        table.ajax.reload();
    });

    $('#btnClearFilters').on('click', function () {
        $('#filterStatus, #filterVendorType').val('');
        $('#filterTrashed').val('false');
        table.ajax.reload();
    });

    // ── Export ────────────────────────────────────────────────────
    $('#btnExport').on('click', function () {
        var params = $.param({
            status:      $('#filterStatus').val(),
            vendor_type: $('#filterVendorType').val(),
        });
        window.location.href = '{{ route("admin.vendors.export") }}?' + params;
    });

    // ── Import Modal ──────────────────────────────────────────────
    $('#btnImport').on('click', function () {
        $('#importFile').val('');
        $('#importProgress').addClass('d-none');
        $('#importResult').addClass('d-none').html('');
        $('#importModal').modal('show');
    });

    $('#btnDoImport').on('click', function () {
        var file = $('#importFile')[0].files[0];
        if (!file) {
            showToast('Please select a file to import.', 'warning');
            return;
        }

        var formData = new FormData();
        formData.append('file', file);
        formData.append('_token', CSRF);

        $('#btnDoImport').prop('disabled', true);
        $('#importProgress').removeClass('d-none');
        $('#importResult').addClass('d-none').html('');

        $.ajax({
            url:         '{{ route("admin.vendors.import") }}',
            method:      'POST',
            data:        formData,
            processData: false,
            contentType: false,
            success: function (res) {
                $('#importProgress').addClass('d-none');
                if (res.success) {
                    var html = '<div class="alert alert-success mb-0">'
                        + '<i class="bi bi-check-circle me-2"></i>' + res.message
                        + '</div>';
                    $('#importResult').removeClass('d-none').html(html);
                    table.ajax.reload();
                } else {
                    $('#importResult').removeClass('d-none').html(
                        '<div class="alert alert-danger mb-0"><i class="bi bi-exclamation-triangle me-2"></i>' + res.message + '</div>'
                    );
                }
            },
            error: function (xhr) {
                $('#importProgress').addClass('d-none');
                var msg = xhr.responseJSON ? xhr.responseJSON.message : 'Import failed. Please try again.';
                $('#importResult').removeClass('d-none').html(
                    '<div class="alert alert-danger mb-0"><i class="bi bi-exclamation-triangle me-2"></i>' + msg + '</div>'
                );
            },
            complete: function () {
                $('#btnDoImport').prop('disabled', false);
            }
        });
    });

    // ── Status Badge Click (inline toggle) ───────────────────────
    $(document).on('click', '.status-toggle-badge', function () {
        var id = $(this).data('vendor-id');
        doToggleStatus(id);
    });

    // ── Toggle Status Button ──────────────────────────────────────
    $(document).on('click', '.toggle-status', function () {
        var id = $(this).data('id');
        doToggleStatus(id);
    });

    function doToggleStatus(id) {
        confirmAction('Toggle vendor status?', 'The vendor status will be changed.', 'warning')
            .then(function (result) {
                if (!result.isConfirmed) return;
                $.ajax({
                    url:    '/admin/vendors/' + id + '/toggle-status',
                    method: 'POST',
                    data:   { _token: CSRF },
                    success: function (res) {
                        if (res.success) {
                            showToast(res.message, 'success');
                            table.ajax.reload(null, false);
                        } else {
                            showToast(res.message, 'danger');
                        }
                    },
                    error: function () {
                        showToast('Failed to update status.', 'danger');
                    }
                });
            });
    }

    // ── Delete ────────────────────────────────────────────────────
    $(document).on('click', '.delete-vendor', function () {
        var id = $(this).data('id');
        confirmAction('Delete this vendor?', 'The vendor will be soft-deleted and can be restored.', 'danger')
            .then(function (result) {
                if (!result.isConfirmed) return;
                $.ajax({
                    url:    '/admin/vendors/' + id,
                    method: 'DELETE',
                    data:   { _token: CSRF },
                    success: function (res) {
                        if (res.success) {
                            showToast(res.message, 'success');
                            table.ajax.reload(null, false);
                        } else {
                            showToast(res.message, 'danger');
                        }
                    },
                    error: function () {
                        showToast('Failed to delete vendor.', 'danger');
                    }
                });
            });
    });

    // ── Restore ───────────────────────────────────────────────────
    $(document).on('click', '.restore-vendor', function () {
        var id = $(this).data('id');
        confirmAction('Restore this vendor?', 'The vendor will be made active again.', 'info')
            .then(function (result) {
                if (!result.isConfirmed) return;
                $.ajax({
                    url:    '/admin/vendors/' + id + '/restore',
                    method: 'POST',
                    data:   { _token: CSRF },
                    success: function (res) {
                        if (res.success) {
                            showToast(res.message, 'success');
                            table.ajax.reload(null, false);
                        } else {
                            showToast(res.message, 'danger');
                        }
                    },
                    error: function () {
                        showToast('Failed to restore vendor.', 'danger');
                    }
                });
            });
    });

});
</script>
@endpush
