@extends('layouts.app')

@section('title', 'Purchase Orders - TMS')

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="bi bi-cart-check"></i> Purchase Orders</h2>
        <div>
            <a href="{{ route('supervisor.purchase-orders.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-circle"></i> Create New PO
            </a>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-2">
            <div class="card bg-secondary text-white">
                <div class="card-body text-center py-2">
                    <small class="d-block mb-1">Total</small>
                    <h4 class="mb-0">{{ $statistics['total'] }}</h4>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card bg-warning text-white">
                <div class="card-body text-center py-2">
                    <small class="d-block mb-1">Pending</small>
                    <h4 class="mb-0">{{ $statistics['pending'] }}</h4>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card bg-info text-white">
                <div class="card-body text-center py-2">
                    <small class="d-block mb-1">Approved</small>
                    <h4 class="mb-0">{{ $statistics['approved'] }}</h4>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card bg-primary text-white">
                <div class="card-body text-center py-2">
                    <small class="d-block mb-1">Sent</small>
                    <h4 class="mb-0">{{ $statistics['sent'] }}</h4>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card bg-success text-white">
                <div class="card-body text-center py-2">
                    <small class="d-block mb-1">Open</small>
                    <h4 class="mb-0">{{ $statistics['open'] }}</h4>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card bg-dark text-white">
                <div class="card-body text-center py-2">
                    <small class="d-block mb-1">Closed</small>
                    <h4 class="mb-0">{{ $statistics['closed'] }}</h4>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters Card -->
    <div class="card mb-3">
        <div class="card-header bg-light">
            <h5 class="mb-0"><i class="bi bi-funnel"></i> Filters</h5>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <!-- Status Filter -->
                <div class="col-md-3">
                    <label class="form-label">Status</label>
                    <select id="status-filter" class="form-select select2">
                        <option value="">All Status ({{ $statistics['total'] }})</option>
                        <option value="draft">Draft ({{ $statistics['draft'] }})</option>
                        <option value="pending_approval">Pending Approval ({{ $statistics['pending'] }})</option>
                        <option value="approved">Approved ({{ $statistics['approved'] }})</option>
                        <option value="sent">Sent ({{ $statistics['sent'] }})</option>
                        <option value="open">Open ({{ $statistics['open'] }})</option>
                        <option value="partially_received">Partially Received ({{ $statistics['partially_received'] }})</option>
                        <option value="fully_received">Fully Received ({{ $statistics['fully_received'] }})</option>
                        <option value="closed">Closed ({{ $statistics['closed'] }})</option>
                        <option value="cancelled">Cancelled ({{ $statistics['cancelled'] }})</option>
                    </select>
                </div>

                <!-- Vendor Filter -->
                <div class="col-md-3">
                    <label class="form-label">Vendor</label>
                    <select id="vendor-filter" class="form-select select2">
                        <option value="">All Vendors</option>
                        @foreach($vendors as $vendor)
                            <option value="{{ $vendor->id }}">{{ $vendor->vendor_name }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Date From -->
                <div class="col-md-2">
                    <label class="form-label">Date From</label>
                    <input type="date" id="date-from" class="form-control">
                </div>

                <!-- Date To -->
                <div class="col-md-2">
                    <label class="form-label">Date To</label>
                    <input type="date" id="date-to" class="form-control">
                </div>

                <!-- Filter Button -->
                <div class="col-md-2 d-flex align-items-end">
                    <button id="filter-btn" class="btn btn-primary w-100">
                        <i class="bi bi-funnel-fill"></i> Apply Filters
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- DataTable Card -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table id="po-table" class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>PO No</th>
                            <th>PO Date</th>
                            <th>Vendor</th>
                            <th>Delivery Date</th>
                            <th>Total Amount</th>
                            <th>Outstanding</th>
                            <th>Status</th>
                            <th>Actions</th>
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
    // Initialize Select2
    $('.select2').select2({
        theme: 'bootstrap-5',
        width: '100%'
    });

    // Initialize DataTable
    const table = $('#po-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route('supervisor.purchase-orders.index') }}',
            data: function(d) {
                d.status = $('#status-filter').val();
                d.vendor_id = $('#vendor-filter').val();
                d.date_from = $('#date-from').val();
                d.date_to = $('#date-to').val();
            }
        },
        columns: [
            { data: 'po_no', name: 'po_no' },
            { data: 'po_date', name: 'po_date' },
            { data: 'vendor_name', name: 'vendor.vendor_name' },
            { data: 'delivery_date', name: 'delivery_date' },
            { data: 'total', name: 'total_amount', className: 'text-end', orderable: false },
            { data: 'outstanding', name: 'outstanding', className: 'text-center', orderable: false },
            { data: 'status_badge', name: 'status', orderable: false },
            { data: 'actions', orderable: false, searchable: false, className: 'text-center' }
        ],
        order: [[1, 'desc']],
        pageLength: 25,
        responsive: true
    });

    // Filter button click
    $('#filter-btn').click(function() {
        table.ajax.reload();
    });

    // Auto-reload when status changes
    $('#status-filter').on('change', function() {
        table.ajax.reload();
    });
});
</script>
@endpush
