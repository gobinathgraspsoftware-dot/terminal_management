@extends('layouts.app')

@section('title', 'Job Types')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">Job Types</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item active">Job Types</li>
                </ol>
            </nav>
        </div>
        @can('create', App\Models\JobType::class)
        <a href="{{ route('admin.job-types.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-lg me-1"></i> Add Job Type
        </a>
        @endcan
    </div>

    <div class="card bg-info-subtle">
        <div class="card-body">
            <i class="bi bi-info-circle me-2"></i>
            Add a Job Type first. Once created, you can proceed to Charge Catalog Management to configure charges.
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <h4 class="mb-0 text-primary">{{ $stats['total'] }}</h4>
                    <small class="text-muted">Total Job Types</small>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <h4 class="mb-0 text-success">{{ $stats['active'] }}</h4>
                    <small class="text-muted">Active</small>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <h4 class="mb-0 text-danger">{{ $stats['inactive'] }}</h4>
                    <small class="text-muted">Inactive</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body py-2">
            <div class="row align-items-center">
                <div class="col-md-3">
                    <select id="filter-status" class="form-select form-select-sm">
                        <option value="">All Status</option>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="button" id="btn-reset-filter" class="btn btn-sm btn-outline-secondary">
                        <i class="bi bi-arrow-counterclockwise me-1"></i> Reset
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- DataTable -->
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <table id="job-types-table" class="table table-hover w-100">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Job Title</th>
                        <th>Slug</th>
                        <th>Description</th>
                        <th>Status</th>
                        <th>Created</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(function() {
    // DataTable
    var table = $('#job-types-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route("admin.job-types.datatable") }}',
            data: function(d) {
                d.status = $('#filter-status').val();
            }
        },
        columns: [
            { data: 'id', name: 'id', width: '50px' },
            { data: 'job_title', name: 'job_title' },
            { data: 'slug', name: 'slug' },
            {
                data: 'description', name: 'description',
                render: function(data) {
                    if (!data) return '<span class="text-muted">—</span>';
                    return data.length > 60 ? data.substring(0, 60) + '...' : data;
                }
            },
            { data: 'status_badge', name: 'status', searchable: false, orderable: false },
            {
                data: 'created_at', name: 'created_at',
                render: function(data) {
                    return data ? new Date(data).toLocaleDateString('en-MY') : '—';
                }
            },
            { data: 'action', name: 'action', orderable: false, searchable: false, className: 'text-center' }
        ],
        order: [[1, 'asc']],
        responsive: true,
        language: {
            emptyTable: "No job types found"
        }
    });

    // Filter
    $('#filter-status').on('change', function() { table.draw(); });
    $('#btn-reset-filter').on('click', function() {
        $('#filter-status').val('');
        table.draw();
    });

    // Toggle Status
    $(document).on('click', '.btn-toggle-status', function() {
        var id = $(this).data('id');
        var currentStatus = $(this).data('status');
        var newStatus = currentStatus === 'active' ? 'inactive' : 'active';

        Swal.fire({
            title: 'Toggle Status?',
            text: 'Change status to ' + newStatus + '?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Yes, change it',
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '/admin/job-types/' + id + '/toggle-status',
                    type: 'PATCH',
                    headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                    success: function(response) {
                        if (response.success) {
                            showToast('success', response.message);
                            table.draw(false);
                        }
                    },
                    error: function(xhr) {
                        showToast('error', xhr.responseJSON?.message || 'Failed to update status');
                    }
                });
            }
        });
    });

    // Delete
    $(document).on('click', '.btn-delete', function() {
        var id = $(this).data('id');
        var name = $(this).data('name');

        Swal.fire({
            title: 'Delete Job Type?',
            html: 'Are you sure you want to delete <strong>' + name + '</strong>?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            confirmButtonText: 'Yes, delete it',
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '/admin/job-types/' + id,
                    type: 'DELETE',
                    headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                    success: function(response) {
                        if (response.success) {
                            showToast('success', response.message);
                            table.draw(false);
                        }
                    },
                    error: function(xhr) {
                        showToast('error', xhr.responseJSON?.message || 'Failed to delete job type');
                    }
                });
            }
        });
    });
});
</script>
@endpush
