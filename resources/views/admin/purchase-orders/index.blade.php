@extends('layouts.app')

@section('title', 'Purchase Orders - TMS')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="bi bi-cart-check"></i> Purchase Orders</h2>
        <div>
            <a href="{{ route('admin.purchase-orders.export') }}?{{ http_build_query(request()->all()) }}" 
               class="btn btn-success">
                <i class="bi bi-file-excel"></i> Export to Excel
            </a>
            <a href="{{ route('admin.purchase-orders.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-circle"></i> Create New PO
            </a>
        </div>
    </div>

    <!-- Status Tabs -->
    <ul class="nav nav-tabs mb-3" id="statusTabs" role="tablist">
        <li class="nav-item">
            <a class="nav-link active" data-status="" href="#">
                All <span class="badge bg-secondary ms-1">{{ $statistics['total'] }}</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" data-status="draft" href="#">
                Draft <span class="badge bg-secondary ms-1">{{ $statistics['draft'] }}</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" data-status="pending_approval" href="#">
                Pending <span class="badge bg-warning ms-1">{{ $statistics['pending'] }}</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" data-status="approved" href="#">
                Approved <span class="badge bg-info ms-1">{{ $statistics['approved'] }}</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" data-status="sent" href="#">
                Sent <span class="badge bg-primary ms-1">{{ $statistics['sent'] }}</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" data-status="open" href="#">
                Open <span class="badge bg-success ms-1">{{ $statistics['open'] }}</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" data-status="partially_received" href="#">
                Partial <span class="badge bg-info ms-1">{{ $statistics['partially_received'] }}</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" data-status="fully_received" href="#">
                Received <span class="badge bg-success ms-1">{{ $statistics['fully_received'] }}</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" data-status="closed" href="#">
                Closed <span class="badge bg-dark ms-1">{{ $statistics['closed'] }}</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" data-status="cancelled" href="#">
                Cancelled <span class="badge bg-danger ms-1">{{ $statistics['cancelled'] }}</span>
            </a>
        </li>
    </ul>

    <!-- Filters -->
    <div class="card mb-3">
        <div class="card-body">
            <form id="filter-form">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label for="vendor-filter" class="form-label">Vendor</label>
                        <select id="vendor-filter" name="vendor_id" class="form-select">
                            <option value="">All Vendors</option>
                            @foreach($vendors as $vendor)
                                <option value="{{ $vendor->id }}">{{ $vendor->vendor_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label for="date-from" class="form-label">Date From</label>
                        <input type="date" id="date-from" name="date_from" class="form-control">
                    </div>
                    <div class="col-md-2">
                        <label for="date-to" class="form-label">Date To</label>
                        <input type="date" id="date-to" name="date_to" class="form-control">
                    </div>
                    <div class="col-md-3">
                        <label for="search" class="form-label">Search</label>
                        <input type="text" id="search" name="search" class="form-control" 
                               placeholder="PO No, Reference...">
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="button" id="filter-btn" class="btn btn-primary w-100">
                            <i class="bi bi-funnel"></i> Filter
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- DataTable -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table id="po-table" class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>PO No</th>
                            <th>PO Date</th>
                            <th>Vendor</th>
                            <th>Reference</th>
                            <th>Delivery Date</th>
                            <th class="text-end">Total Amount</th>
                            <th class="text-center">Outstanding</th>
                            <th>Status</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    let currentStatus = '';
    
    // Initialize DataTable
    const table = $('#po-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route('admin.purchase-orders.index') }}',
            data: function(d) {
                d.status = currentStatus;
                d.vendor_id = $('#vendor-filter').val();
                d.date_from = $('#date-from').val();
                d.date_to = $('#date-to').val();
                d.search = $('#search').val();
            }
        },
        columns: [
            { data: 'po_no', name: 'po_no' },
            { data: 'po_date', name: 'po_date' },
            { data: 'vendor_name', name: 'vendor.vendor_name' },
            { data: 'reference', name: 'reference' },
            { data: 'delivery_date', name: 'delivery_date' },
            { data: 'total', name: 'total_amount', className: 'text-end', orderable: false },
            { data: 'outstanding', name: 'outstanding', className: 'text-center', orderable: false, searchable: false },
            { data: 'status_badge', name: 'status', orderable: false, searchable: false },
            { data: 'actions', orderable: false, searchable: false, className: 'text-center' }
        ],
        order: [[1, 'desc']],
        pageLength: 25,
        responsive: true
    });

    // Status tab click handler
    $('#statusTabs .nav-link').on('click', function(e) {
        e.preventDefault();
        $('#statusTabs .nav-link').removeClass('active');
        $(this).addClass('active');
        currentStatus = $(this).data('status') || '';
        table.ajax.reload();
    });

    // Filter button click
    $('#filter-btn').on('click', function() {
        table.ajax.reload();
    });

    // Enter key in search
    $('#search').on('keypress', function(e) {
        if (e.which === 13) {
            e.preventDefault();
            table.ajax.reload();
        }
    });

    // Reset filters
    window.resetFilters = function() {
        $('#filter-form')[0].reset();
        currentStatus = '';
        $('#statusTabs .nav-link').removeClass('active');
        $('#statusTabs .nav-link:first').addClass('active');
        table.ajax.reload();
    };
});
</script>
@endpush
