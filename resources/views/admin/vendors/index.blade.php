@extends('layouts.app')

@section('title', 'Vendors - TMS')

@push('styles')
<style>
/* Make status badges clickable - like Partner module */
.status-toggle-badge {
    cursor: pointer;
    transition: all 0.2s ease;
}

.status-toggle-badge:hover {
    transform: scale(1.05);
    box-shadow: 0 2px 4px rgba(0,0,0,0.2);
}

.status-toggle-badge.updating {
    cursor: wait;
}
</style>
@endpush

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-0">
                <i class="bi bi-truck me-2"></i>Vendors Management
            </h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item active">Vendors</li>
                </ol>
            </nav>
        </div>
        <div>
            {{-- @can('import_vendors')
            <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#importModal">
                <i class="bi bi-upload"></i> Import
            </button>
            @endcan --}}

            @can('export_vendors')
            <button type="button" class="btn btn-outline-success" id="exportBtn">
                <i class="bi bi-download"></i> Export
            </button>
            @endcan

            @can('create_vendors')
            <a href="{{ route('admin.vendors.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-circle"></i> New Vendor
            </a>
            @endcan
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="bg-primary bg-opacity-10 rounded p-3">
                                <i class="bi bi-truck fs-3 text-primary"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="text-muted mb-1">Total Vendors</h6>
                            <h3 class="mb-0">{{ $statistics['total'] }}</h3>
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
                                <i class="bi bi-check-circle fs-3 text-success"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="text-muted mb-1">Active</h6>
                            <h3 class="mb-0">{{ $statistics['active'] }}</h3>
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
                                <i class="bi bi-building fs-3 text-warning"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="text-muted mb-1">Suppliers</h6>
                            <h3 class="mb-0">{{ $statistics['suppliers'] }}</h3>
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
                            <div class="bg-info bg-opacity-10 rounded p-3">
                                <i class="bi bi-cart fs-3 text-info"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="text-muted mb-1">Active POs</h6>
                            <h3 class="mb-0">{{ $statistics['active_purchase_orders'] }}</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters & Table Card -->
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white">
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label small">Status</label>
                    <select class="form-select" id="statusFilter">
                        <option value="">All Statuses</option>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small">Vendor Type</label>
                    <select class="form-select" id="vendorTypeFilter">
                        <option value="">All Types</option>
                        <option value="supplier">Supplier</option>
                        <option value="subcon">Sub-contractor</option>
                        <option value="courier">Courier</option>
                        <option value="other">Other</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small">State</label>
                    <select class="form-select" id="stateFilter">
                        <option value="">All States</option>
                        <option value="Johor">Johor</option>
                        <option value="Kedah">Kedah</option>
                        <option value="Kelantan">Kelantan</option>
                        <option value="Melaka">Melaka</option>
                        <option value="Negeri Sembilan">Negeri Sembilan</option>
                        <option value="Pahang">Pahang</option>
                        <option value="Penang">Penang</option>
                        <option value="Perak">Perak</option>
                        <option value="Perlis">Perlis</option>
                        <option value="Sabah">Sabah</option>
                        <option value="Sarawak">Sarawak</option>
                        <option value="Selangor">Selangor</option>
                        <option value="Terengganu">Terengganu</option>
                        <option value="Kuala Lumpur">Kuala Lumpur</option>
                        <option value="Labuan">Labuan</option>
                        <option value="Putrajaya">Putrajaya</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small">Show</label>
                    <div class="form-check form-switch mt-2">
                        <input class="form-check-input" type="checkbox" id="showTrashed">
                        <label class="form-check-label" for="showTrashed">
                            Include Deleted
                        </label>
                    </div>
                </div>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover" id="vendorsTable">
                    <thead class="table-light">
                        <tr>
                            <th>Code</th>
                            <th>Name</th>
                            <th>Type</th>
                            <th>PIC</th>
                            <th>Bank Details</th>
                            <th>Payment Terms</th>
                            <th>POs</th>
                            <th>Status</th>
                            <th>Created</th>
                            <th width="150">Actions</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Import Modal -->
<div class="modal fade" id="importModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-upload me-2"></i>Import Vendors
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="importForm" enctype="multipart/form-data">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Excel File</label>
                        <input type="file" class="form-control" name="file" accept=".xlsx,.xls,.csv" required>
                        <div class="form-text">
                            Supported formats: XLSX, XLS, CSV (Max: 10MB)
                        </div>
                    </div>
                    <div class="mb-3">
                        <a href="{{ route('admin.vendors.import-template') }}" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-download"></i> Download Template
                        </a>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-upload"></i> Import
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Initialize DataTable
    const table = $('#vendorsTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route('admin.vendors.datatable') }}',
            data: function(d) {
                d.status = $('#statusFilter').val();
                d.vendor_type = $('#vendorTypeFilter').val();
                d.state = $('#stateFilter').val();
                d.show_trashed = $('#showTrashed').is(':checked');
            }
        },
        columns: [
            { data: 'vendor_code', name: 'vendor_code' },
            {
                data: 'vendor_name',
                name: 'vendor_name',
                render: function(data, type, row) {
                    return '<strong>' + data + '</strong>' +
                           (row.company_name ? '<br><small class="text-muted">' + row.company_name + '</small>' : '');
                }
            },
            { data: 'vendor_type_badge', name: 'vendor_type', orderable: false },
            { data: 'pic_info', name: 'pic_name', orderable: false },
            { data: 'bank_info', name: 'bank_name', orderable: false },
            { data: 'payment_terms_display', name: 'payment_terms' },
            { data: 'purchase_orders_count', name: 'purchase_orders_count', searchable: false },
            { data: 'status_badge', name: 'status', orderable: false },
            { data: 'created_info', name: 'created_at' },
            { data: 'actions', name: 'actions', orderable: false, searchable: false }
        ],
        order: [[1, 'asc']],
        pageLength: 25,
        responsive: true,
        language: {
            emptyTable: "No vendors found"
        }
    });

    // Filter change handlers
    $('#statusFilter, #vendorTypeFilter, #stateFilter').change(function() {
        table.draw();
    });

    $('#showTrashed').change(function() {
        table.draw();
    });

    // Delete vendor
    $(document).on('click', '.delete-vendor', function() {
        const id = $(this).data('id');

        Swal.fire({
            title: 'Are you sure?',
            text: 'This vendor will be soft deleted.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: `/admin/vendors/${id}`,
                    type: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(response) {
                        if (response.success) {
                            showToast(response.message, 'success');
                            table.draw();
                        } else {
                            showToast(response.message, 'error');
                        }
                    },
                    error: function(xhr) {
                        showToast(xhr.responseJSON?.message || 'Failed to delete vendor', 'error');
                    }
                });
            }
        });
    });

    // Restore vendor
    $(document).on('click', '.restore-vendor', function() {
        const id = $(this).data('id');

        Swal.fire({
            title: 'Restore Vendor?',
            text: 'This vendor will be restored.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Yes, restore it!'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: `/admin/vendors/${id}/restore`,
                    type: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(response) {
                        if (response.success) {
                            showToast(response.message, 'success');
                            table.draw();
                        } else {
                            showToast(response.message, 'error');
                        }
                    },
                    error: function(xhr) {
                        showToast(xhr.responseJSON?.message || 'Failed to restore vendor', 'error');
                    }
                });
            }
        });
    });

    // Toggle status - Click on status badge
    $(document).on('click', '.status-toggle-badge', function(e) {
        e.preventDefault();

        const badge = $(this);
        const vendorId = badge.data('vendor-id');

        if (badge.hasClass('updating')) {
            return;
        }

        const originalClass = badge.attr('class');
        const originalText = badge.text();

        badge.addClass('updating')
             .removeClass('bg-success bg-secondary')
             .addClass('bg-warning')
             .html('<i class="spinner-border spinner-border-sm me-1"></i>Updating...');

        $.ajax({
            url: `/admin/vendors/${vendorId}/toggle-status`,
            type: 'POST',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                if (response.success) {
                    showToast(response.message, 'success');
                    table.draw(false);
                } else {
                    showToast(response.message, 'error');
                    badge.attr('class', originalClass).text(originalText);
                }
            },
            error: function(xhr) {
                showToast(xhr.responseJSON?.message || 'Failed to update status', 'error');
                badge.attr('class', originalClass).text(originalText);
            }
        });
    });

    // Toggle status - Click on toggle button in actions column
    $(document).on('click', '.toggle-status', function(e) {
        e.preventDefault();

        const button = $(this);
        const vendorId = button.data('id');

        if (button.prop('disabled')) {
            return;
        }

        const originalHtml = button.html();

        button.prop('disabled', true)
              .html('<i class="spinner-border spinner-border-sm"></i>');

        $.ajax({
            url: `/admin/vendors/${vendorId}/toggle-status`,
            type: 'POST',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                if (response.success) {
                    showToast(response.message, 'success');
                    table.draw(false);
                } else {
                    showToast(response.message, 'error');
                    button.prop('disabled', false).html(originalHtml);
                }
            },
            error: function(xhr) {
                showToast(xhr.responseJSON?.message || 'Failed to update status', 'error');
                button.prop('disabled', false).html(originalHtml);
            }
        });
    });

    // Export
    $('#exportBtn').click(function() {
        const filters = {
            status: $('#statusFilter').val(),
            vendor_type: $('#vendorTypeFilter').val(),
            state: $('#stateFilter').val()
        };

        const queryString = $.param(filters);
        window.location.href = '{{ route('admin.vendors.export') }}?' + queryString;
    });

    // Import
    $('#importForm').submit(function(e) {
        e.preventDefault();

        const formData = new FormData(this);

        $.ajax({
            url: '{{ route('admin.vendors.import') }}',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    showToast(response.message, 'success');
                    $('#importModal').modal('hide');
                    $('#importForm')[0].reset();
                    table.draw();

                    if (response.results.failed > 0) {
                        console.log('Import errors:', response.results.errors);
                    }
                } else {
                    showToast(response.message, 'error');
                }
            },
            error: function(xhr) {
                showToast(xhr.responseJSON?.message || 'Import failed', 'error');
            }
        });
    });
});
</script>
@endpush
