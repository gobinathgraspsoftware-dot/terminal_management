@extends('layouts.app')

@section('title', 'Vendor Types')

@section('content')
<div class="container-fluid">
    {{-- Page Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">Vendor Types</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item active">Vendor Types</li>
                </ol>
            </nav>
        </div>
        @can('create_vendor_types')
        <a href="{{ route('admin.vendor-types.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-lg me-1"></i> Add Vendor Type
        </a>
        @endcan
    </div>

    {{-- Stats Cards --}}
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <div class="text-muted small">Total</div>
                    <div class="h4 mb-0 fw-bold">{{ $stats['total'] }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <div class="text-muted small">Active</div>
                    <div class="h4 mb-0 fw-bold text-success">{{ $stats['active'] }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <div class="text-muted small">Inactive</div>
                    <div class="h4 mb-0 fw-bold text-danger">{{ $stats['inactive'] }}</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Filter --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <div class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label">Status</label>
                    <select id="filterStatus" class="form-select">
                        <option value="">All</option>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="button" id="btnResetFilters" class="btn btn-outline-secondary w-100">
                        <i class="bi bi-arrow-counterclockwise me-1"></i> Reset
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- DataTable --}}
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table id="vendorTypesTable" class="table table-hover align-middle w-100">
                    <thead>
                        <tr>
                            <th width="60">#</th>
                            <th>Title</th>
                            <th>Description</th>
                            <th width="100">Status</th>
                            <th width="120">Created</th>
                            <th width="120">Actions</th>
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
$(function() {
    // DataTable
    var table = $('#vendorTypesTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route("admin.vendor-types.index") }}',
            data: function(d) {
                d.status = $('#filterStatus').val();
            }
        },
        columns: [
            { data: 'id', name: 'id' },
            { data: 'title', name: 'title' },
            { data: 'description', name: 'description' },
            { data: 'status_badge', name: 'is_active', orderable: true, searchable: false },
            { data: 'created_at', name: 'created_at' },
            { data: 'actions', name: 'actions', orderable: false, searchable: false }
        ],
        order: [[0, 'desc']],
        pageLength: 10,
        language: {
            emptyTable: 'No vendor types found',
            zeroRecords: 'No matching vendor types found'
        }
    });

    // Filter change
    $('#filterStatus').on('change', function() {
        table.ajax.reload();
    });

    // Reset filters
    $('#btnResetFilters').on('click', function() {
        $('#filterStatus').val('');
        table.ajax.reload();
    });

    // Toggle status
    $(document).on('click', '.toggle-status', function() {
        var id = $(this).data('id');
        var btn = $(this);

        $.ajax({
            url: '{{ url("admin/vendor-types") }}/' + id + '/toggle-status',
            type: 'POST',
            data: { _token: '{{ csrf_token() }}' },
            success: function(response) {
                if (response.success) {
                    showToast(response.message, 'success');
                    table.ajax.reload(null, false);
                }
            },
            error: function(xhr) {
                showToast(xhr.responseJSON?.message || 'Failed to toggle status', 'error');
            }
        });
    });

    // Delete
    $(document).on('click', '.delete-vendor-type', function() {
        var id = $(this).data('id');

        Swal.fire({
            title: 'Are you sure?',
            text: 'This vendor type will be deleted.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            confirmButtonText: 'Yes, delete it!'
        }).then(function(result) {
            if (result.isConfirmed) {
                $.ajax({
                    url: '{{ url("admin/vendor-types") }}/' + id,
                    type: 'DELETE',
                    data: { _token: '{{ csrf_token() }}' },
                    success: function(response) {
                        if (response.success) {
                            showToast(response.message, 'success');
                            table.ajax.reload(null, false);
                        }
                    },
                    error: function(xhr) {
                        showToast(xhr.responseJSON?.message || 'Failed to delete', 'error');
                    }
                });
            }
        });
    });
});
</script>
@endpush
