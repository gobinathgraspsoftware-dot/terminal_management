@extends('layouts.app')

@section('title', 'My Stock - Technician')

@section('content')
<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h1><i class="bi bi-box-seam me-2"></i>My Stock</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('technician.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item active">My Stock</li>
                </ol>
            </nav>
        </div>
        <div class="input-group" style="width: 260px;">
            <span class="input-group-text bg-warning text-dark"><i class="bi bi-upc-scan"></i></span>
            <input type="text" id="barcodeInput" class="form-control" placeholder="Scan barcode..." autofocus autocomplete="off">
        </div>
    </div>
</div>

<!-- Quick Stats -->
<div class="row mb-4">
    <div class="col-6 mb-3">
        <div class="stats-card text-center">
            <div class="stats-icon bg-info bg-opacity-10 text-info mx-auto mb-2">
                <i class="bi bi-box-seam"></i>
            </div>
            <div class="stats-value">{{ $myStockCount }}</div>
            <div class="stats-label">Total Items</div>
        </div>
    </div>
    <div class="col-6 mb-3">
        <div class="stats-card text-center">
            <div class="stats-icon bg-success bg-opacity-10 text-success mx-auto mb-2">
                <i class="bi bi-check-circle"></i>
            </div>
            <div class="stats-value">{{ $myAvailableCount }}</div>
            <div class="stats-label">Available</div>
        </div>
    </div>
</div>

<!-- DataTable -->
<div class="card">
    <div class="card-header">
        <h5 class="mb-0"><i class="bi bi-list-ul me-2"></i>My Issued Stock</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table id="myStockTable" class="table table-hover" style="width:100%">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Serial / Model</th>
                        <th>Status</th>
                        <th>Warranty</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    var table = $('#myStockTable').DataTable({
        processing: true, serverSide: true,
        ajax: {
            url: '{{ route("technician.inventory-serials.datatable") }}',
            error: function(xhr) { if (xhr.status === 401) window.location.href = '{{ route("login") }}'; }
        },
        columns: [
            { data: 'DT_RowIndex', orderable: false, searchable: false },
            { data: 'serial_info', name: 'serial_no' },
            { data: 'status_badge', name: 'current_status' },
            { data: 'warranty', orderable: false },
            { data: 'action', orderable: false, searchable: false }
        ],
        order: [[1, 'asc']], pageLength: 25, responsive: true,
        language: { emptyTable: 'No stock items issued to you.' }
    });

    // Barcode scanner
    $('#barcodeInput').on('keypress', function(e) {
        if (e.which === 13) {
            e.preventDefault();
            var serial = $(this).val().trim();
            if (serial) {
                $.get('{{ route("api.serials.validate") }}', { serial_no: serial }, function(r) {
                    if (r.exists && r.serial) {
                        window.location.href = '{{ route("technician.inventory-serials.show", ":id") }}'.replace(':id', r.serial.id);
                    } else {
                        showToast('Serial not found: ' + serial, 'warning');
                        $('#barcodeInput').val('').focus();
                    }
                });
            }
        }
    });
});
</script>
@endpush
