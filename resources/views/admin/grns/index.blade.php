@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">Goods Receipt Notes (GRN)</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item active">GRNs</li>
                </ol>
            </nav>
        </div>
        <div>
            @can('create', App\Models\Grn::class)
            <a href="{{ route('admin.grns.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-circle me-1"></i> Create GRN
            </a>
            @endcan
        </div>
    </div>

    <div class="card">
        <div class="card-header bg-white">
            <div class="row align-items-center">
                <div class="col-md-3">
                    <label class="form-label mb-0">Status Filter</label>
                    <select class="form-select form-select-sm" id="statusFilter">
                        <option value="">All Status</option>
                        <option value="draft">Draft</option>
                        <option value="posted">Posted</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label mb-0">Date Range</label>
                    <input type="date" class="form-control form-control-sm" id="dateFrom" placeholder="From">
                </div>
                <div class="col-md-3">
                    <label class="form-label mb-0">&nbsp;</label>
                    <input type="date" class="form-control form-control-sm" id="dateTo" placeholder="To">
                </div>
                <div class="col-md-3">
                    <label class="form-label mb-0">&nbsp;</label>
                    <button type="button" class="btn btn-sm btn-secondary w-100" id="resetFilters">
                        <i class="bi bi-arrow-clockwise me-1"></i> Reset
                    </button>
                </div>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover" id="grnsTable">
                    <thead>
                        <tr>
                            <th>GRN No</th>
                            <th>Date</th>
                            <th>PO No</th>
                            <th>Vendor</th>
                            <th>Depot</th>
                            <th>Total Items</th>
                            <th>Status</th>
                            <th>Created By</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    const table = $('#grnsTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route("admin.grns.index") }}',
            data: function(d) {
                d.status = $('#statusFilter').val();
                d.date_from = $('#dateFrom').val();
                d.date_to = $('#dateTo').val();
            }
        },
        columns: [
            { data: 'grn_no', name: 'grn_no' },
            { data: 'grn_date', name: 'grn_date' },
            { data: 'po_no', name: 'purchaseOrder.po_no' },
            { data: 'vendor_name', name: 'vendor.name' },
            { data: 'depot_name', name: 'receivingDepot.name' },
            { data: 'total_items', name: 'total_items', className: 'text-end' },
            { data: 'status', name: 'status' },
            { data: 'created_by_name', name: 'createdBy.name' },
            { data: 'action', name: 'action', orderable: false, searchable: false }
        ],
        order: [[1, 'desc']]
    });

    // Filters
    $('#statusFilter, #dateFrom, #dateTo').on('change', function() {
        table.draw();
    });

    $('#resetFilters').on('click', function() {
        $('#statusFilter').val('');
        $('#dateFrom').val('');
        $('#dateTo').val('');
        table.draw();
    });

    // Post GRN
    $(document).on('click', '.post-grn-btn', function() {
        const grnId = $(this).data('id');
        const grnNo = $(this).data('grn-no');

        Swal.fire({
            title: 'Post GRN?',
            text: `Post GRN ${grnNo}? This will update inventory and cannot be undone.`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#198754',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, Post It'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: `/admin/grns/${grnId}/post`,
                    method: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        if (response.success) {
                            Swal.fire('Posted!', response.message, 'success');
                            table.ajax.reload();
                        }
                    },
                    error: function(xhr) {
                        Swal.fire('Error', xhr.responseJSON?.message || 'Failed to post GRN', 'error');
                    }
                });
            }
        });
    });

    // Delete GRN
    $(document).on('click', '.delete-grn-btn', function() {
        const grnId = $(this).data('id');
        const grnNo = $(this).data('grn-no');

        Swal.fire({
            title: 'Delete GRN?',
            text: `Delete GRN ${grnNo}? This action cannot be undone.`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, Delete It'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: `/admin/grns/${grnId}`,
                    method: 'DELETE',
                    data: {
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        if (response.success) {
                            Swal.fire('Deleted!', response.message, 'success');
                            table.ajax.reload();
                        }
                    },
                    error: function(xhr) {
                        Swal.fire('Error', xhr.responseJSON?.message || 'Failed to delete GRN', 'error');
                    }
                });
            }
        });
    });
});
</script>
@endpush
@endsection
