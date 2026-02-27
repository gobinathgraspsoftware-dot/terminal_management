@extends('layouts.app')

@section('title', 'Goods Receipt Notes - TMS')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">Goods Receipt Notes</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item active">GRNs</li>
                </ol>
            </nav>
        </div>
        <div>
            @can('create_grns')
            <a href="{{ route('admin.grns.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-lg me-1"></i> Create GRN
            </a>
            @endcan
            <a href="{{ route('admin.grn-reports.index') }}" class="btn btn-outline-info ms-1">
                <i class="bi bi-bar-chart me-1"></i> Reports
            </a>
            <button type="button" class="btn btn-outline-success ms-1" id="btnExport">
                <i class="bi bi-download me-1"></i> Export
            </button>
        </div>
    </div>

    <!-- Filter Card -->
    <div class="card mb-3">
        <div class="card-header d-flex justify-content-between align-items-center py-2">
            <h6 class="mb-0"><i class="bi bi-funnel me-1"></i> Filters</h6>
            <button class="btn btn-sm btn-link text-decoration-none" type="button" data-bs-toggle="collapse" data-bs-target="#filterCollapse">
                <i class="bi bi-chevron-down"></i>
            </button>
        </div>
        <div class="collapse show" id="filterCollapse">
            <div class="card-body py-3">
                <div class="row g-2">
                    <div class="col-md-2">
                        <label class="form-label form-label-sm mb-1">Date From</label>
                        <input type="date" class="form-control form-control-sm" id="filterDateFrom">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label form-label-sm mb-1">Date To</label>
                        <input type="date" class="form-control form-control-sm" id="filterDateTo">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label form-label-sm mb-1">Vendor</label>
                        <select class="form-select form-select-sm select2-filter" id="filterVendor">
                            <option value="">All Vendors</option>
                            @foreach($vendors as $vendor)
                                <option value="{{ $vendor->id }}">{{ $vendor->vendor_name ?? $vendor->company_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label form-label-sm mb-1">Purchase Order</label>
                        <select class="form-select form-select-sm select2-filter" id="filterPO">
                            <option value="">All POs</option>
                            @foreach($purchaseOrders as $po)
                                <option value="{{ $po->id }}">{{ $po->po_no }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label form-label-sm mb-1">Depot</label>
                        <select class="form-select form-select-sm" id="filterDepot">
                            <option value="">All Depots</option>
                            @foreach($depots as $depot)
                                <option value="{{ $depot->id }}">{{ $depot->depot_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label form-label-sm mb-1">Status</label>
                        <select class="form-select form-select-sm" id="filterStatus">
                            <option value="">All Status</option>
                            <option value="draft">Draft</option>
                            <option value="posted">Posted</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                    </div>
                </div>
                <div class="row g-2 mt-1">
                    <div class="col-md-3">
                        <label class="form-label form-label-sm mb-1">Search (GRN No / Delivery Note)</label>
                        <input type="text" class="form-control form-control-sm" id="filterSearch" placeholder="Enter GRN no or delivery note no...">
                    </div>
                    <div class="col-md-9 d-flex align-items-end gap-2">
                        <button type="button" class="btn btn-sm btn-primary" id="btnApplyFilter">
                            <i class="bi bi-search me-1"></i> Apply
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-secondary" id="btnResetFilter">
                            <i class="bi bi-x-circle me-1"></i> Reset
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Data Table -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table id="grnsTable" class="table table-striped table-hover table-sm w-100">
                    <thead>
                        <tr>
                            <th>GRN No</th>
                            <th>Date</th>
                            <th>Vendor</th>
                            <th>PO No</th>
                            <th>Depot</th>
                            <th>Items</th>
                            <th>Status</th>
                            <th>Created By</th>
                            <th class="text-center">Actions</th>
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
    // Initialize Select2 for filter dropdowns
    $('.select2-filter').select2({
        theme: 'bootstrap-5',
        allowClear: true,
        placeholder: 'Select...',
        width: '100%'
    });

    // DataTable initialization
    var table = $('#grnsTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route("admin.grns.index") }}',
            data: function(d) {
                d.filter_status = $('#filterStatus').val();
                d.filter_vendor_id = $('#filterVendor').val();
                d.filter_purchase_order_id = $('#filterPO').val();
                d.filter_receiving_depot_id = $('#filterDepot').val();
                d.filter_date_from = $('#filterDateFrom').val();
                d.filter_date_to = $('#filterDateTo').val();
                d.filter_search = $('#filterSearch').val();
            }
        },
        columns: [
            { data: 'grn_no', name: 'grn_no' },
            { data: 'grn_date', name: 'grn_date' },
            { data: 'vendor_name', name: 'vendor_name', orderable: false },
            { data: 'po_no', name: 'po_no', orderable: false },
            { data: 'depot_name', name: 'depot_name', orderable: false },
            { data: 'total_items', name: 'total_items' },
            { data: 'status', name: 'status' },
            { data: 'created_by_name', name: 'created_by_name', orderable: false },
            { data: 'action', name: 'action', orderable: false, searchable: false, className: 'text-center' }
        ],
        order: [[1, 'desc'], [0, 'desc']],
        pageLength: 25,
        lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
        language: {
            processing: '<div class="spinner-border text-primary spinner-border-sm" role="status"><span class="visually-hidden">Loading...</span></div>',
            emptyTable: 'No GRNs found',
            zeroRecords: 'No matching GRNs found'
        }
    });

    // Apply filters
    $('#btnApplyFilter').on('click', function() {
        table.ajax.reload();
    });

    // Reset filters
    $('#btnResetFilter').on('click', function() {
        $('#filterDateFrom').val('');
        $('#filterDateTo').val('');
        $('#filterVendor').val('').trigger('change');
        $('#filterPO').val('').trigger('change');
        $('#filterDepot').val('');
        $('#filterStatus').val('');
        $('#filterSearch').val('');
        table.ajax.reload();
    });

    // Enter key on search field
    $('#filterSearch').on('keypress', function(e) {
        if (e.which === 13) {
            table.ajax.reload();
        }
    });

    // Export button
    $('#btnExport').on('click', function() {
        var params = $.param({
            date_from: $('#filterDateFrom').val(),
            date_to: $('#filterDateTo').val(),
            vendor_id: $('#filterVendor').val(),
            purchase_order_id: $('#filterPO').val(),
            receiving_depot_id: $('#filterDepot').val(),
            status: $('#filterStatus').val(),
            search: $('#filterSearch').val()
        });
        window.location.href = '{{ route("admin.grns.export") }}?' + params;
    });

    // Post GRN
    $(document).on('click', '.post-grn-btn', function() {
        var id = $(this).data('id');
        var grnNo = $(this).data('grn-no');

        Swal.fire({
            title: 'Post GRN?',
            text: 'Are you sure you want to post ' + grnNo + '? This will update inventory and cannot be undone.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#198754',
            confirmButtonText: 'Yes, Post it!'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '/admin/grns/' + id + '/post',
                    type: 'POST',
                    success: function(response) {
                        if (response.success) {
                            showToast(response.message, 'success');
                            table.ajax.reload();
                        }
                    },
                    error: function(xhr) {
                        var msg = xhr.responseJSON?.message || 'Error posting GRN';
                        showToast(msg, 'error');
                    }
                });
            }
        });
    });

    // Cancel GRN
    $(document).on('click', '.cancel-grn-btn', function() {
        var id = $(this).data('id');
        var grnNo = $(this).data('grn-no');

        Swal.fire({
            title: 'Cancel GRN?',
            text: 'Are you sure you want to cancel ' + grnNo + '?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            confirmButtonText: 'Yes, Cancel it!'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '/admin/grns/' + id + '/cancel',
                    type: 'POST',
                    success: function(response) {
                        if (response.success) {
                            showToast(response.message, 'success');
                            table.ajax.reload();
                        }
                    },
                    error: function(xhr) {
                        var msg = xhr.responseJSON?.message || 'Error cancelling GRN';
                        showToast(msg, 'error');
                    }
                });
            }
        });
    });
});
</script>
@endpush
