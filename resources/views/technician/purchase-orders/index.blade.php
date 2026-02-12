@extends('layouts.app')

@section('title', 'My Purchase Orders - TMS')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="bi bi-cart-check"></i> My Purchase Orders</h2>
    </div>

    <!-- Status Summary -->
    <div class="row mb-3">
        <div class="col-md-3">
            <div class="card">
                <div class="card-body text-center">
                    <h3 class="mb-0">{{ $statistics['total'] }}</h3>
                    <small class="text-muted">Total POs</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body text-center">
                    <h3 class="mb-0 text-warning">{{ $statistics['pending'] }}</h3>
                    <small class="text-muted">Pending</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body text-center">
                    <h3 class="mb-0 text-success">{{ $statistics['approved'] }}</h3>
                    <small class="text-muted">Approved</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body text-center">
                    <h3 class="mb-0 text-info">{{ $statistics['open'] }}</h3>
                    <small class="text-muted">Open</small>
                </div>
            </div>
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
                            <th>Delivery Date</th>
                            <th class="text-end">Total Amount</th>
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
    // Initialize DataTable
    const table = $('#po-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route('technician.purchase-orders.index') }}'
        },
        columns: [
            { data: 'po_no', name: 'po_no' },
            { data: 'po_date', name: 'po_date' },
            { data: 'vendor_name', name: 'vendor.vendor_name' },
            { data: 'delivery_date', name: 'delivery_date' },
            { data: 'total', name: 'total_amount', className: 'text-end', orderable: false },
            { data: 'status_badge', name: 'status', orderable: false, searchable: false },
            { data: 'actions', orderable: false, searchable: false, className: 'text-center' }
        ],
        order: [[1, 'desc']],
        pageLength: 25,
        responsive: true
    });
});
</script>
@endpush
