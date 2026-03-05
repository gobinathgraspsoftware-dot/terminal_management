@extends('layouts.app')

@section('title', 'Rate Cards Management')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="row mb-3">
        <div class="col-md-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h4 class="mb-0"><i class="bi bi-credit-card-2-front"></i> Rate Cards Management</h4>
                    <p class="text-muted mb-0">Manage commission rate cards for technicians</p>
                </div>
                <div>
                    @can('create_rate_cards')
                        <a href="{{ route('admin.rate-cards.create') }}" class="btn btn-primary">
                            <i class="bi bi-plus-circle"></i> Create Rate Card
                        </a>
                    @endcan
                    @can('view_rate_cards')
                        <button type="button" class="btn btn-success" id="exportBtn">
                            <i class="bi bi-file-earmark-excel"></i> Export
                        </button>
                    @endcan
                </div>
            </div>
        </div>
    </div>

    <!-- Filters Card -->
    <div class="card mb-3">
        <div class="card-body">
            <form id="filterForm">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Job Type</label>
                        <select class="form-select" id="filterJobType" name="job_type">
                            <option value="">All Job Types</option>
                            @foreach($jobTypes as $key => $value)
                                <option value="{{ $key }}">{{ $value }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Terminal Model</label>
                        <select class="form-select" id="filterModel" name="model_id">
                            <option value="">All Models</option>
                            @foreach($models as $model)
                                <option value="{{ $model->id }}">{{ $model->model_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">State</label>
                        <select class="form-select" id="filterState" name="state">
                            <option value="">All States</option>
                            @foreach($states as $key => $value)
                                <option value="{{ $value }}">{{ $value }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Status</label>
                        <select class="form-select" id="filterStatus" name="status">
                            <option value="">All Status</option>
                            @foreach($statuses as $key => $value)
                                <option value="{{ $key }}">{{ $value }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="row mt-3">
                    <div class="col-md-12">
                        <button type="button" class="btn btn-sm btn-primary" id="applyFilters">
                            <i class="bi bi-funnel"></i> Apply Filters
                        </button>
                        <button type="button" class="btn btn-sm btn-secondary" id="resetFilters">
                            <i class="bi bi-x-circle"></i> Reset
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Rate Cards Table -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover" id="rateCardsTable" style="width:100%">
                    <thead>
                        <tr>
                            <th>Code</th>
                            <th>Rate Card Name</th>
                            <th>Job Type</th>
                            <th>Model</th>
                            <th>State</th>
                            <th>Calculation Type</th>
                            <th>Rate Amount</th>
                            <th>Effective From</th>
                            <th>Effective To</th>
                            <th width="130">Status</th>
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
$(document).ready(function() {
    let table;
    let deleteId = null;

    // Initialize DataTable
    function initDataTable() {
        if ($.fn.DataTable.isDataTable('#rateCardsTable')) {
            $('#rateCardsTable').DataTable().destroy();
        }

        table = $('#rateCardsTable').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: '{{ route('admin.rate-cards.index') }}',
                data: function(d) {
                    d.job_type = $('#filterJobType').val();
                    d.model_id = $('#filterModel').val();
                    d.state = $('#filterState').val();
                    d.status = $('#filterStatus').val();
                }
            },
            columns: [
                { data: 'rate_card_code', name: 'rate_card_code' },
                { data: 'rate_card_name', name: 'rate_card_name' },
                { data: 'job_type', name: 'job_type' },
                { data: 'model', name: 'model' },
                { data: 'state', name: 'state' },
                { data: 'calculation_type', name: 'calculation_type' },
                { data: 'rate_amount', name: 'rate_amount', className: 'text-nowrap' },
                { data: 'effective_from', name: 'effective_from' },
                {
                    data: 'effective_to',
                    name: 'effective_to',
                    render: function(data, type, row) {
                        if (row.is_expired) {
                            return '<span class="badge bg-danger">' + data + '</span>';
                        }
                        return data;
                    }
                },
                {
                    data: 'status_badge',
                    name: 'status',
                    className: 'text-center text-nowrap',
                    render: function(data, type, row) {
                        let badge = data;
                        if (row.is_effective && row.status === 'active') {
                            badge += ' <span class="badge bg-success ms-1">Effective</span>';
                        }
                        return badge;
                    }
                },
                {
                    data: 'id',
                    name: 'actions',
                    orderable: false,
                    searchable: false,
                    className: 'text-center text-nowrap',
                    render: function(data, type, row) {
                        let actions = '<div class="btn-group btn-group-sm" role="group">';

                        actions += '<a href="/admin/rate-cards/' + data + '" class="btn btn-info" title="View">' +
                                  '<i class="bi bi-eye"></i></a>';

                        @can('edit_rate_cards')
                        actions += '<a href="/admin/rate-cards/' + data + '/edit" class="btn btn-warning" title="Edit">' +
                                  '<i class="bi bi-pencil"></i></a>';
                        @endcan

                        @can('delete_rate_cards')
                        actions += '<button type="button" class="btn btn-danger delete-btn" data-id="' + data + '" title="Delete">' +
                                  '<i class="bi bi-trash"></i></button>';
                        @endcan

                        actions += '</div>';
                        return actions;
                    }
                }
            ],
            order: [[0, 'desc']],
            pageLength: 25,
            responsive: true
        });
    }

    initDataTable();

    // Apply Filters
    $('#applyFilters').on('click', function() {
        table.ajax.reload();
    });

    // Reset Filters
    $('#resetFilters').on('click', function() {
        $('#filterForm')[0].reset();
        table.ajax.reload();
    });

    // Export
    $('#exportBtn').on('click', function() {
        let params = new URLSearchParams({
            job_type: $('#filterJobType').val(),
            model_id: $('#filterModel').val(),
            state: $('#filterState').val(),
            status: $('#filterStatus').val(),
        });

        window.location.href = '{{ route('admin.rate-cards.export') }}?' + params.toString();
    });

    // Delete Rate Card
    $(document).on('click', '.delete-btn', function() {
        deleteId = $(this).data('id');

        Swal.fire({
            title: 'Are you sure?',
            text: 'This action cannot be undone.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '/admin/rate-cards/' + deleteId,
                    type: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(response) {
                        showToast(response.message, 'success');
                        table.ajax.reload();
                        deleteId = null;
                    },
                    error: function(xhr) {
                        showToast(xhr.responseJSON?.message || 'Failed to delete rate card.', 'error');
                        deleteId = null;
                    }
                });
            } else {
                deleteId = null;
            }
        });
    });
});
</script>
@endpush
