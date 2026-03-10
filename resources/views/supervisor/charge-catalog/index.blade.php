@extends('layouts.app')

@section('title', 'Charge Catalog')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">Charge Catalog</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('supervisor.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item active">Charge Catalog</li>
                </ol>
            </nav>
        </div>
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
                    <h5 class="mb-0">Charge Catalog <small class="text-muted">(View Only)</small></h5>
                </div>
                <div class="col-auto">
                    <div class="btn-group btn-group-sm flex-wrap" role="group">
                        <button type="button" class="btn btn-outline-primary active" id="filterAll">All</button>
                        @foreach($jobTypes as $jobType)
                        <button type="button" class="btn btn-outline-primary" data-type-id="{{ $jobType->id }}">{{ $jobType->job_title }}</button>
                        @endforeach
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

@push('scripts')
<script>
$(document).ready(function() {
    var currentTypeId = '';

    var table = $('#chargesTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route("supervisor.charge-catalog.datatable") }}',
            data: function(d) {
                d.job_type_id = currentTypeId;
            }
        },
        columns: [
            { data: 'charge_code', name: 'charge_code' },
            {
                data: 'charge_name',
                name: 'charge_name',
                render: function(data, type, row) {
                    var html = '<strong>' + data + '</strong>';
                    if (row.description) {
                        html += '<br><small class="text-muted">' + row.description + '</small>';
                    }
                    return html;
                }
            },
            { data: 'type_badge', name: 'job_type_id', orderable: false, searchable: false },
            { data: 'price_display', name: 'default_price', orderable: false, searchable: false },
            { data: 'tax_info', name: 'tax_rate', orderable: false, searchable: false }
        ],
        order: [[0, 'asc']],
        pageLength: 25,
        language: {
            processing: '<div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div>'
        }
    });

    // Filter by job type (dynamic buttons)
    $('.btn-group button[data-type-id]').on('click', function() {
        currentTypeId = $(this).data('type-id');
        $('.btn-group button').removeClass('active');
        $(this).addClass('active');
        table.ajax.reload();
    });

    $('#filterAll').on('click', function() {
        currentTypeId = '';
        $('.btn-group button').removeClass('active');
        $(this).addClass('active');
        table.ajax.reload();
    });
});
</script>
@endpush
