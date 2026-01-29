@extends('layouts.app')

@section('title', 'Clients Management')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">Clients Management</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item active">Clients</li>
                </ol>
            </nav>
        </div>
        <div>
            @can('create_clients')
            <a href="{{ route('admin.clients.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-circle me-1"></i> Add New Client
            </a>
            @endcan
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card border-start border-primary border-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-1">Total Clients</h6>
                            <h3 class="mb-0">{{ $statistics['total'] }}</h3>
                        </div>
                        <div class="text-primary">
                            <i class="bi bi-people fs-1"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-start border-success border-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-1">Active</h6>
                            <h3 class="mb-0">{{ $statistics['active'] }}</h3>
                        </div>
                        <div class="text-success">
                            <i class="bi bi-check-circle fs-1"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-start border-warning border-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-1">With Outstanding</h6>
                            <h3 class="mb-0">{{ $statistics['with_outstanding'] }}</h3>
                        </div>
                        <div class="text-warning">
                            <i class="bi bi-exclamation-triangle fs-1"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-start border-danger border-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-1">Suspended</h6>
                            <h3 class="mb-0">{{ $statistics['suspended'] }}</h3>
                        </div>
                        <div class="text-danger">
                            <i class="bi bi-pause-circle fs-1"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters Card -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="card-title mb-0">
                <i class="bi bi-funnel me-1"></i> Filters
            </h5>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Partner</label>
                    <select class="form-select" id="filter_partner_id">
                        <option value="">All Partners</option>
                        @foreach($partners as $partner)
                        <option value="{{ $partner->id }}">[{{ $partner->partner_code }}] {{ $partner->partner_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Status</label>
                    <select class="form-select" id="filter_status">
                        <option value="">All Statuses</option>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                        <option value="suspended">Suspended</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">State</label>
                    <select class="form-select" id="filter_state">
                        <option value="">All States</option>
                        <option value="Johor">Johor</option>
                        <option value="Kedah">Kedah</option>
                        <option value="Kelantan">Kelantan</option>
                        <option value="Kuala Lumpur">Kuala Lumpur</option>
                        <option value="Penang">Penang</option>
                        <option value="Selangor">Selangor</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">&nbsp;</label>
                    <div class="d-grid">
                        <button type="button" class="btn btn-secondary" id="btn_clear_filters">
                            <i class="bi bi-x-circle me-1"></i> Clear Filters
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Clients Table Card -->
    <div class="card">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">Clients List</h5>
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" id="show_trashed">
                    <label class="form-check-label" for="show_trashed">Show Deleted</label>
                </div>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover" id="clients_table">
                    <thead>
                        <tr>
                            <th>Client Code</th>
                            <th>Client Name</th>
                            <th>Partner</th>
                            <th>PIC Info</th>
                            <th>Location</th>
                            <th>Financial Info</th>
                            <th>Stats</th>
                            <th>Status</th>
                            <th>Created</th>
                            <th width="120">Actions</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
@endpush

@push('scripts')
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>

<script>
$(document).ready(function() {
    // Initialize DataTable
    const table = $('#clients_table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route("admin.clients.datatable") }}',
            data: function(d) {
                d.partner_id = $('#filter_partner_id').val();
                d.status = $('#filter_status').val();
                d.state = $('#filter_state').val();
                d.show_trashed = $('#show_trashed').is(':checked') ? 'true' : 'false';
            }
        },
        columns: [
            { data: 'client_code', name: 'client_code' },
            { data: 'client_name', name: 'client_name' },
            { data: 'partner_name', name: 'partner.partner_name' },
            { data: 'pic_info', name: 'pic_name', orderable: false, searchable: false },
            { data: 'address_info', name: 'billing_city', orderable: false, searchable: false },
            { data: 'financial_info', name: 'payment_terms', orderable: false, searchable: false },
            { data: 'stats', name: 'stats', orderable: false, searchable: false },
            { data: 'status_badge', name: 'status' },
            { data: 'created_info', name: 'created_at' },
            { data: 'actions', name: 'actions', orderable: false, searchable: false }
        ],
        order: [[0, 'desc']],
        pageLength: 25,
        responsive: true
    });

    // Filter change events
    $('#filter_partner_id, #filter_status, #filter_state, #show_trashed').on('change', function() {
        table.draw();
    });

    // Clear filters
    $('#btn_clear_filters').on('click', function() {
        $('#filter_partner_id').val('');
        $('#filter_status').val('');
        $('#filter_state').val('');
        table.draw();
    });

    // Toggle Status
    $(document).on('click', '.toggle-status', function() {
        const clientId = $(this).data('id');
        
        if (confirm('Are you sure you want to change the status of this client?')) {
            $.ajax({
                url: `/admin/clients/${clientId}/toggle-status`,
                type: 'POST',
                data: {
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    if (response.success) {
                        table.draw(false);
                        showToast('Success', response.message, 'success');
                    } else {
                        showToast('Error', response.message, 'error');
                    }
                },
                error: function(xhr) {
                    showToast('Error', 'Failed to update client status', 'error');
                }
            });
        }
    });

    // Delete Client
    $(document).on('click', '.delete-client', function() {
        const clientId = $(this).data('id');
        
        if (confirm('Are you sure you want to delete this client? This action can be undone by restoring the client.')) {
            $.ajax({
                url: `/admin/clients/${clientId}`,
                type: 'DELETE',
                data: {
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    if (response.success) {
                        table.draw(false);
                        showToast('Success', response.message, 'success');
                    } else {
                        showToast('Error', response.message, 'error');
                    }
                },
                error: function(xhr) {
                    const message = xhr.responseJSON?.message || 'Failed to delete client';
                    showToast('Error', message, 'error');
                }
            });
        }
    });

    // Restore Client
    $(document).on('click', '.restore-client', function() {
        const clientId = $(this).data('id');
        
        if (confirm('Are you sure you want to restore this client?')) {
            $.ajax({
                url: `/admin/clients/${clientId}/restore`,
                type: 'POST',
                data: {
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    if (response.success) {
                        table.draw(false);
                        showToast('Success', response.message, 'success');
                    } else {
                        showToast('Error', response.message, 'error');
                    }
                },
                error: function(xhr) {
                    showToast('Error', 'Failed to restore client', 'error');
                }
            });
        }
    });

    // Toast notification helper
    function showToast(title, message, type) {
        // You can replace this with your preferred toast notification library
        alert(`${title}: ${message}`);
    }
});
</script>
@endpush
