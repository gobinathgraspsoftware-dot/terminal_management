@extends('layouts.app')

@section('title', 'Partners')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">Partners</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('technician.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item active">Partners</li>
                </ol>
            </nav>
        </div>
    </div>

    <!-- Statistics Card -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm bg-primary text-white">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <i class="bi bi-building fs-1 opacity-75"></i>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="mb-1 text-white-50">Active Partners</h6>
                            <h3 class="mb-0" id="totalPartners">-</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <label for="filter_state" class="form-label">State</label>
                    <select id="filter_state" class="form-select">
                        <option value="">All States</option>
                        <option value="Johor">Johor</option>
                        <option value="Kedah">Kedah</option>
                        <option value="Kelantan">Kelantan</option>
                        <option value="Kuala Lumpur">Kuala Lumpur</option>
                        <option value="Labuan">Labuan</option>
                        <option value="Melaka">Melaka</option>
                        <option value="Negeri Sembilan">Negeri Sembilan</option>
                        <option value="Pahang">Pahang</option>
                        <option value="Penang">Penang</option>
                        <option value="Perak">Perak</option>
                        <option value="Perlis">Perlis</option>
                        <option value="Putrajaya">Putrajaya</option>
                        <option value="Sabah">Sabah</option>
                        <option value="Sarawak">Sarawak</option>
                        <option value="Selangor">Selangor</option>
                        <option value="Terengganu">Terengganu</option>
                    </select>
                </div>
                <div class="col-md-4 d-flex align-items-end">
                    <button type="button" id="btn_clear_filters" class="btn btn-outline-secondary">
                        <i class="bi bi-x-circle me-1"></i> Clear Filters
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Partners Table -->
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table id="partnersTable" class="table table-hover" style="width: 100%;">
                    <thead class="table-light">
                        <tr>
                            <th>Code</th>
                            <th>Partner Name</th>
                            <th>Contact Person</th>
                            <th>Phone</th>
                            <th>Location</th>
                            <th class="text-center">Actions</th>
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
    // Initialize DataTable
    var table = $('#partnersTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route("technician.partners.datatable") }}',
            data: function(d) {
                d.state = $('#filter_state').val();
            }
        },
        columns: [
            { 
                data: 'partner_code', 
                name: 'partner_code',
                render: function(data) {
                    return '<code class="text-primary">' + data + '</code>';
                }
            },
            { data: 'partner_name', name: 'partner_name' },
            { 
                data: 'pic_name', 
                name: 'pic_name',
                render: function(data) {
                    return data || '<span class="text-muted">-</span>';
                }
            },
            { 
                data: 'pic_phone', 
                name: 'pic_phone',
                render: function(data) {
                    if (data) {
                        return '<a href="tel:' + data + '" class="text-decoration-none">' +
                               '<i class="bi bi-telephone me-1"></i>' + data + '</a>';
                    }
                    return '<span class="text-muted">-</span>';
                }
            },
            { 
                data: 'location', 
                name: 'city',
                orderable: false,
                render: function(data) {
                    return data || '<span class="text-muted">-</span>';
                }
            },
            { 
                data: 'actions', 
                name: 'actions', 
                orderable: false, 
                searchable: false,
                className: 'text-center'
            }
        ],
        order: [[1, 'asc']],
        pageLength: 10,
        lengthMenu: [[10, 25, 50], [10, 25, 50]],
        language: {
            processing: '<div class="spinner-border spinner-border-sm text-primary" role="status"><span class="visually-hidden">Loading...</span></div>',
            emptyTable: 'No partners found',
            zeroRecords: 'No matching partners found'
        },
        drawCallback: function(settings) {
            // Update statistics
            var json = settings.json;
            if (json && json.recordsTotal !== undefined) {
                $('#totalPartners').text(json.recordsTotal);
            }
        }
    });

    // Filter change events
    $('#filter_state').on('change', function() {
        table.draw();
    });

    // Clear filters
    $('#btn_clear_filters').on('click', function() {
        $('#filter_state').val('');
        table.draw();
    });
});
</script>
@endpush

@push('styles')
<style>
    .card {
        border-radius: 0.5rem;
    }
    code {
        font-size: 0.9em;
    }
    /* Mobile responsiveness */
    @media (max-width: 768px) {
        .table-responsive {
            font-size: 0.875rem;
        }
    }
</style>
@endpush
