@extends('layouts.app')

@section('title', 'Partners - TMS')

@section('content')
<!-- Page Header -->
<div class="page-header">
    <div class="row align-items-center">
        <div class="col">
            <h1><i class="bi bi-building me-2"></i>Partners</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item active">Partners</li>
                </ol>
            </nav>
        </div>
        <div class="col-auto">
            @can('partners.create')
            <a href="{{ route('admin.partners.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-lg me-1"></i> Add Partner
            </a>
            @endcan
        </div>
    </div>
</div>

<!-- Statistics Cards -->
<div class="row mb-4">
    <div class="col-md-3 col-sm-6 mb-3">
        <div class="stats-card">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="stats-value">{{ $statistics['total'] ?? 0 }}</div>
                    <div class="stats-label">Total Partners</div>
                </div>
                <div class="stats-icon bg-primary bg-opacity-10 text-primary">
                    <i class="bi bi-building"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6 mb-3">
        <div class="stats-card">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="stats-value text-success">{{ $statistics['active'] ?? 0 }}</div>
                    <div class="stats-label">Active Partners</div>
                </div>
                <div class="stats-icon bg-success bg-opacity-10 text-success">
                    <i class="bi bi-check-circle"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6 mb-3">
        <div class="stats-card">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="stats-value text-warning">{{ $statistics['inactive'] ?? 0 }}</div>
                    <div class="stats-label">Inactive Partners</div>
                </div>
                <div class="stats-icon bg-warning bg-opacity-10 text-warning">
                    <i class="bi bi-pause-circle"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6 mb-3">
        <div class="stats-card">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="stats-value text-info">{{ $statistics['with_clients'] ?? 0 }}</div>
                    <div class="stats-label">With Clients</div>
                </div>
                <div class="stats-icon bg-info bg-opacity-10 text-info">
                    <i class="bi bi-people"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Filters Card -->
<div class="card mb-4">
    <div class="card-body">
        <div class="row g-3 align-items-end">
            <div class="col-md-2">
                <label class="form-label">Status</label>
                <select id="filterStatus" class="form-select">
                    <option value="">All Status</option>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Job Intake Method</label>
                <select id="filterJobIntake" class="form-select">
                    <option value="">All Methods</option>
                    <option value="manual">Manual</option>
                    <option value="import">Import</option>
                    <option value="api">API</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">State</label>
                <select id="filterState" class="form-select">
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
            <div class="col-md-2">
                <label class="form-label">Show Deleted</label>
                <div class="form-check form-switch mt-2">
                    <input class="form-check-input" type="checkbox" id="showTrashed">
                    <label class="form-check-label" for="showTrashed">Include deleted</label>
                </div>
            </div>
            <div class="col-md-4 text-end">
                <button type="button" id="btnResetFilters" class="btn btn-outline-secondary me-2">
                    <i class="bi bi-x-lg me-1"></i> Reset
                </button>
                @can('partners.export')
                <button type="button" id="btnExport" class="btn btn-success me-2">
                    <i class="bi bi-download me-1"></i> Export
                </button>
                @endcan
                @can('partners.import')
                <button type="button" id="btnImport" class="btn btn-info" data-bs-toggle="modal" data-bs-target="#importModal">
                    <i class="bi bi-upload me-1"></i> Import
                </button>
                @endcan
            </div>
        </div>
    </div>
</div>

<!-- Partners Table -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="bi bi-table me-2"></i>Partners List</span>
        <span class="badge bg-secondary" id="tableCount">0 records</span>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table id="partnersTable" class="table table-striped table-hover" style="width:100%">
                <thead>
                    <tr>
                        <th>Partner Code</th>
                        <th>Partner Name</th>
                        <th>PIC</th>
                        <th>Location</th>
                        <th>Job Intake</th>
                        <th>Clients</th>
                        <th>Jobs</th>
                        <th>Status</th>
                        <th>Created</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Import Modal -->
@can('partners.import')
<div class="modal fade" id="importModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-upload me-2"></i>Import Partners</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="importForm" enctype="multipart/form-data">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Select Excel File</label>
                        <input type="file" class="form-control" id="importFile" name="file" 
                            accept=".xlsx,.xls,.csv" required>
                        <div class="form-text">Accepted formats: .xlsx, .xls, .csv (Max 10MB)</div>
                    </div>
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle me-2"></i>
                        <strong>Import Instructions:</strong>
                        <ul class="mb-0 mt-2">
                            <li>Download the <a href="{{ route('admin.partners.import-template') }}" class="alert-link">import template</a> first</li>
                            <li>Fill in the required fields (Partner Name is mandatory)</li>
                            <li>Existing partners will be updated based on Partner Code</li>
                            <li>New partners will have codes auto-generated</li>
                        </ul>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-upload me-1"></i> Import
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endcan
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Initialize DataTable
    let partnersTable = $('#partnersTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: "{{ route('admin.partners.datatable') }}",
            data: function(d) {
                d.status = $('#filterStatus').val();
                d.job_intake_method = $('#filterJobIntake').val();
                d.state = $('#filterState').val();
                d.show_trashed = $('#showTrashed').is(':checked') ? 'true' : 'false';
            }
        },
        columns: [
            { data: 'partner_code', name: 'partner_code' },
            { data: 'partner_name', name: 'partner_name' },
            { data: 'pic_info', name: 'pic_name', orderable: false },
            { 
                data: null, 
                name: 'city',
                render: function(data) {
                    let parts = [];
                    if (data.city) parts.push(data.city);
                    if (data.state) parts.push(data.state);
                    return parts.join(', ') || '-';
                }
            },
            { data: 'job_intake_badge', name: 'job_intake_method', orderable: false },
            { 
                data: 'clients_count', 
                name: 'clients_count',
                className: 'text-center',
                render: function(data) {
                    return '<span class="badge bg-info">' + data + '</span>';
                }
            },
            { 
                data: 'job_orders_count', 
                name: 'job_orders_count',
                className: 'text-center',
                render: function(data) {
                    return '<span class="badge bg-secondary">' + data + '</span>';
                }
            },
            { data: 'status_badge', name: 'status', orderable: false },
            { data: 'created_info', name: 'created_at', orderable: true },
            { data: 'actions', name: 'actions', orderable: false, searchable: false, className: 'text-center' }
        ],
        order: [[0, 'asc']],
        pageLength: 25,
        lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
        drawCallback: function(settings) {
            let info = this.api().page.info();
            $('#tableCount').text(info.recordsTotal + ' records');
        },
        language: {
            processing: '<div class="spinner-border spinner-border-sm text-primary" role="status"><span class="visually-hidden">Loading...</span></div> Loading...',
            emptyTable: 'No partners found',
            zeroRecords: 'No matching partners found'
        }
    });

    // Filter change handlers
    $('#filterStatus, #filterJobIntake, #filterState').on('change', function() {
        partnersTable.ajax.reload();
    });

    $('#showTrashed').on('change', function() {
        partnersTable.ajax.reload();
    });

    // Reset filters
    $('#btnResetFilters').on('click', function() {
        $('#filterStatus').val('');
        $('#filterJobIntake').val('');
        $('#filterState').val('');
        $('#showTrashed').prop('checked', false);
        partnersTable.ajax.reload();
    });

    // Export functionality
    @can('partners.export')
    $('#btnExport').on('click', function() {
        let params = new URLSearchParams({
            status: $('#filterStatus').val(),
            job_intake_method: $('#filterJobIntake').val(),
            state: $('#filterState').val(),
            show_trashed: $('#showTrashed').is(':checked') ? 'true' : 'false'
        });
        window.location.href = "{{ route('admin.partners.export') }}?" + params.toString();
    });
    @endcan

    // Import functionality
    @can('partners.import')
    $('#importForm').on('submit', function(e) {
        e.preventDefault();
        
        let formData = new FormData(this);
        
        Swal.fire({
            title: 'Importing...',
            text: 'Please wait while we process your file',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        $.ajax({
            url: "{{ route('admin.partners.import') }}",
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                Swal.fire({
                    icon: 'success',
                    title: 'Import Complete',
                    html: response.message + '<br><br>' +
                        '<strong>Success:</strong> ' + response.results.success + '<br>' +
                        '<strong>Failed:</strong> ' + response.results.failed,
                });
                $('#importModal').modal('hide');
                $('#importForm')[0].reset();
                partnersTable.ajax.reload();
            },
            error: function(xhr) {
                let message = xhr.responseJSON?.message || 'Import failed';
                Swal.fire({
                    icon: 'error',
                    title: 'Import Failed',
                    text: message
                });
            }
        });
    });
    @endcan

    // Toggle status
    $(document).on('click', '.toggle-status', function() {
        let id = $(this).data('id');
        
        $.ajax({
            url: "{{ url('admin/partners') }}/" + id + "/toggle-status",
            type: 'POST',
            success: function(response) {
                showToast(response.message, 'success');
                partnersTable.ajax.reload(null, false);
            },
            error: function(xhr) {
                showToast(xhr.responseJSON?.message || 'Failed to update status', 'error');
            }
        });
    });

    // Delete partner
    $(document).on('click', '.delete-partner', function() {
        let id = $(this).data('id');
        
        Swal.fire({
            title: 'Delete Partner?',
            text: 'This partner will be soft-deleted and can be restored later.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: "{{ url('admin/partners') }}/" + id,
                    type: 'DELETE',
                    success: function(response) {
                        showToast(response.message, 'success');
                        partnersTable.ajax.reload(null, false);
                    },
                    error: function(xhr) {
                        showToast(xhr.responseJSON?.message || 'Failed to delete partner', 'error');
                    }
                });
            }
        });
    });

    // Restore partner
    $(document).on('click', '.restore-partner', function() {
        let id = $(this).data('id');
        
        Swal.fire({
            title: 'Restore Partner?',
            text: 'This partner will be restored and become active again.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#28a745',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, restore it!'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: "{{ url('admin/partners') }}/" + id + "/restore",
                    type: 'POST',
                    success: function(response) {
                        showToast(response.message, 'success');
                        partnersTable.ajax.reload(null, false);
                    },
                    error: function(xhr) {
                        showToast(xhr.responseJSON?.message || 'Failed to restore partner', 'error');
                    }
                });
            }
        });
    });
});
</script>
@endpush
