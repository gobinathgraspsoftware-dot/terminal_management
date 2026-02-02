@extends('layouts.app')

@section('title', 'Charge Catalog Reference')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="mb-4">
        <h1 class="h3 mb-0">Charge Catalog Reference</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('supervisor.dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item active">Charge Catalog</li>
            </ol>
        </nav>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <i class="bi bi-tag-fill text-primary fs-2"></i>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="text-muted mb-1">Active Charges</h6>
                            <h3 class="mb-0">{{ $stats['total_charges'] }}</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <i class="bi bi-percent text-warning fs-2"></i>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="text-muted mb-1">Taxable</h6>
                            <h3 class="mb-0">{{ $stats['taxable_charges'] }}</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <i class="bi bi-grid-3x3-gap-fill text-info fs-2"></i>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="text-muted mb-1">Charge Types</h6>
                            <h3 class="mb-0">{{ $stats['types'] }}</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Charge Catalog Table -->
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-3">
            <div class="row align-items-center">
                <div class="col">
                    <h5 class="mb-0">Charge Catalog (Active Only)</h5>
                </div>
                <div class="col-auto">
                    <div class="btn-group btn-group-sm" role="group">
                        <button type="button" class="btn btn-outline-primary active" id="filterAll">All</button>
                        <button type="button" class="btn btn-outline-primary" data-type="installation">Installation</button>
                        <button type="button" class="btn btn-outline-primary" data-type="service">Service</button>
                        <button type="button" class="btn btn-outline-primary" data-type="hardware">Hardware</button>
                        <button type="button" class="btn btn-outline-primary" data-type="accessory">Accessory</button>
                        <button type="button" class="btn btn-outline-primary" data-type="labour">Labour</button>
                        <button type="button" class="btn btn-outline-primary" data-type="transport">Transport</button>
                        <button type="button" class="btn btn-outline-primary" data-type="other">Other</button>
                    </div>
                </div>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table id="chargesTable" class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Code</th>
                            <th>Charge Name</th>
                            <th>Type</th>
                            <th>Price</th>
                            <th>Tax</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- DataTable will populate this -->
                    </tbody>
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
    let currentType = '';

    // Initialize DataTable
    const table = $('#chargesTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route('supervisor.charge-catalog.datatable') }}',
            data: function(d) {
                d.charge_type = currentType;
            }
        },
        columns: [
            { data: 'charge_code', name: 'charge_code' },
            { 
                data: 'charge_name', 
                name: 'charge_name',
                render: function(data, type, row) {
                    let html = '<strong>' + data + '</strong>';
                    if (row.description) {
                        html += '<br><small class="text-muted">' + row.description + '</small>';
                    }
                    return html;
                }
            },
            { data: 'type_badge', name: 'charge_type', orderable: false, searchable: false },
            { data: 'price_display', name: 'default_price', orderable: false, searchable: false },
            { data: 'tax_info', name: 'tax_rate', orderable: false, searchable: false }
        ],
        order: [[0, 'asc']],
        pageLength: 25,
        language: {
            processing: '<div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div>'
        }
    });

    // Filter by type
    $('.btn-group button[data-type]').on('click', function() {
        currentType = $(this).data('type');
        $('.btn-group button').removeClass('active');
        $(this).addClass('active');
        table.ajax.reload();
    });

    $('#filterAll').on('click', function() {
        currentType = '';
        $('.btn-group button').removeClass('active');
        $(this).addClass('active');
        table.ajax.reload();
    });
});
</script>
@endpush
