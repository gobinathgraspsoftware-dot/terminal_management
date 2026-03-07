@extends('layouts.app')

@section('title', 'Vendors')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">Vendors</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item active">Vendors</li>
                </ol>
            </nav>
        </div>
        <div class="d-flex gap-2">
            @can('export_vendors')
            <a href="{{ route('admin.vendors.export') }}" class="btn btn-outline-success">
                <i class="bi bi-download me-1"></i> Export
            </a>
            @endcan
            @can('import_vendors')
            <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#importModal">
                <i class="bi bi-upload me-1"></i> Import
            </button>
            @endcan
            @can('create_vendors')
            <a href="{{ route('admin.vendors.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-circle me-1"></i> Add Vendor
            </a>
            @endcan
        </div>
    </div>

    {{-- Statistics Cards --}}
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-2">
            <div class="card text-center">
                <div class="card-body py-3">
                    <div class="h4 mb-0 text-primary">{{ $statistics['total'] ?? 0 }}</div>
                    <small class="text-muted">Total</small>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-2">
            <div class="card text-center">
                <div class="card-body py-3">
                    <div class="h4 mb-0 text-success">{{ $statistics['active'] ?? 0 }}</div>
                    <small class="text-muted">Active</small>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-2">
            <div class="card text-center">
                <div class="card-body py-3">
                    <div class="h4 mb-0 text-info">{{ $statistics['suppliers'] ?? 0 }}</div>
                    <small class="text-muted">Suppliers</small>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-2">
            <div class="card text-center">
                <div class="card-body py-3">
                    <div class="h4 mb-0 text-warning">{{ $statistics['subcontractors'] ?? 0 }}</div>
                    <small class="text-muted">Sub-cons</small>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-2">
            <div class="card text-center">
                <div class="card-body py-3">
                    <div class="h4 mb-0 text-secondary">{{ $statistics['couriers'] ?? 0 }}</div>
                    <small class="text-muted">Couriers</small>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-2">
            <div class="card text-center">
                <div class="card-body py-3">
                    <div class="h4 mb-0 text-dark">{{ $statistics['total_branches'] ?? 0 }}</div>
                    <small class="text-muted">Branches</small>
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
                    <select class="form-select" id="filterStatus">
                        <option value="">All Status</option>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Vendor Type</label>
                    <select class="form-select" id="filterType">
                        <option value="">All Types</option>
                        <option value="supplier">Supplier</option>
                        <option value="subcon">Sub-contractor</option>
                        <option value="courier">Courier</option>
                        <option value="other">Other</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">State (Branch)</label>
                    <select class="form-select" id="filterState">
                        <option value="">All States</option>
                        @php
                            $states = ['Johor','Kedah','Kelantan','Melaka','Negeri Sembilan','Pahang','Penang','Perak','Perlis','Sabah','Sarawak','Selangor','Terengganu','Kuala Lumpur','Labuan','Putrajaya'];
                        @endphp
                        @foreach($states as $state)
                            <option value="{{ $state }}">{{ $state }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="showTrashed">
                        <label class="form-check-label" for="showTrashed">Show Deleted</label>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- DataTable --}}
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table id="vendorsTable" class="table table-hover table-striped w-100">
                    <thead>
                        <tr>
                            <th>Code</th>
                            <th>Name</th>
                            <th>Type</th>
                            <th>PIC</th>
                            <th>Branches</th>
                            <th>Bank</th>
                            <th>Terms</th>
                            <th>POs</th>
                            <th>Status</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
</div>

{{-- Import Modal --}}
@can('import_vendors')
<div class="modal fade" id="importModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Import Vendors</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="importForm" enctype="multipart/form-data">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">Excel File</label>
                        <input type="file" class="form-control" name="file" accept=".xlsx,.xls,.csv" required>
                    </div>
                    <div class="mb-3">
                        <a href="{{ route('admin.vendors.import-template') }}" class="text-primary">
                            <i class="bi bi-download me-1"></i> Download Template
                        </a>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="importBtn">
                    <i class="bi bi-upload me-1"></i> Import
                </button>
            </div>
        </div>
    </div>
</div>
@endcan

@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Initialize DataTable
    var table = $('#vendorsTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route("admin.vendors.datatable") }}',
            data: function(d) {
                d.status = $('#filterStatus').val();
                d.vendor_type = $('#filterType').val();
                d.state = $('#filterState').val();
                d.show_trashed = $('#showTrashed').is(':checked') ? 'true' : 'false';
            }
        },
        columns: [
            { data: 'vendor_code', name: 'vendor_code' },
            { data: 'vendor_name', name: 'vendor_name' },
            { data: 'vendor_type_badge', name: 'vendor_type', searchable: false, orderable: false },
            { data: 'pic_info', name: 'pic_name', searchable: false, orderable: false },
            { data: 'branches_count_display', name: 'branches_count', searchable: false, orderable: false },
            { data: 'bank_info', name: 'bank_name', searchable: false, orderable: false },
            { data: 'payment_terms_display', name: 'payment_terms' },
            { data: 'purchase_orders_count', name: 'purchase_orders_count', searchable: false, orderable: false },
            { data: 'status_badge', name: 'status', searchable: false, orderable: false },
            { data: 'created_info', name: 'created_at', searchable: false },
            { data: 'actions', name: 'actions', orderable: false, searchable: false }
        ],
        order: [[0, 'desc']],
        responsive: true
    });

    // Filter changes
    $('#filterStatus, #filterType, #filterState').on('change', function() {
        table.draw();
    });

    $('#showTrashed').on('change', function() {
        table.draw();
    });

    // Toggle status via badge click
    $(document).on('click', '.status-toggle-badge', function() {
        var vendorId = $(this).data('vendor-id');
        if (!vendorId) return;

        Swal.fire({
            title: 'Toggle Status?',
            text: 'Are you sure you want to change this vendor\'s status?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Yes, toggle it'
        }).then((result) => {
            if (result.isConfirmed) {
                $.post('{{ url("admin/vendors") }}/' + vendorId + '/toggle-status', {
                    _token: '{{ csrf_token() }}'
                }).done(function(response) {
                    if (response.success) {
                        showToast('success', response.message);
                        table.draw(false);
                    }
                }).fail(function(xhr) {
                    showToast('error', xhr.responseJSON?.message || 'Failed to toggle status.');
                });
            }
        });
    });

    // Toggle status via button
    $(document).on('click', '.toggle-status', function() {
        var vendorId = $(this).data('id');
        Swal.fire({
            title: 'Toggle Status?',
            text: 'Are you sure you want to change this vendor\'s status?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Yes, toggle it'
        }).then((result) => {
            if (result.isConfirmed) {
                $.post('{{ url("admin/vendors") }}/' + vendorId + '/toggle-status', {
                    _token: '{{ csrf_token() }}'
                }).done(function(response) {
                    if (response.success) {
                        showToast('success', response.message);
                        table.draw(false);
                    }
                }).fail(function(xhr) {
                    showToast('error', xhr.responseJSON?.message || 'Failed to toggle status.');
                });
            }
        });
    });

    // Delete vendor
    $(document).on('click', '.delete-vendor', function() {
        var vendorId = $(this).data('id');
        Swal.fire({
            title: 'Delete Vendor?',
            text: 'This action can be undone by restoring the vendor.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            confirmButtonText: 'Yes, delete it'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '{{ url("admin/vendors") }}/' + vendorId,
                    method: 'DELETE',
                    data: { _token: '{{ csrf_token() }}' }
                }).done(function(response) {
                    if (response.success) {
                        showToast('success', response.message);
                        table.draw(false);
                    }
                }).fail(function(xhr) {
                    showToast('error', xhr.responseJSON?.message || 'Failed to delete vendor.');
                });
            }
        });
    });

    // Restore vendor
    $(document).on('click', '.restore-vendor', function() {
        var vendorId = $(this).data('id');
        Swal.fire({
            title: 'Restore Vendor?',
            text: 'This will restore the deleted vendor.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Yes, restore it'
        }).then((result) => {
            if (result.isConfirmed) {
                $.post('{{ url("admin/vendors") }}/' + vendorId + '/restore', {
                    _token: '{{ csrf_token() }}'
                }).done(function(response) {
                    if (response.success) {
                        showToast('success', response.message);
                        table.draw(false);
                    }
                }).fail(function(xhr) {
                    showToast('error', xhr.responseJSON?.message || 'Failed to restore vendor.');
                });
            }
        });
    });

    // Import
    @can('import_vendors')
    $('#importBtn').on('click', function() {
        var formData = new FormData($('#importForm')[0]);
        var btn = $(this);
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Importing...');

        $.ajax({
            url: '{{ route("admin.vendors.import") }}',
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false
        }).done(function(response) {
            if (response.success) {
                showToast('success', response.message);
                $('#importModal').modal('hide');
                table.draw();
            }
        }).fail(function(xhr) {
            showToast('error', xhr.responseJSON?.message || 'Import failed.');
        }).always(function() {
            btn.prop('disabled', false).html('<i class="bi bi-upload me-1"></i> Import');
        });
    });
    @endcan
});
</script>
@endpush
