@extends('layouts.app')

@section('title', 'Quotations')

@section('content')
<div class="pagetitle">
    <h1>Quotations</h1>
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Home</a></li>
            <li class="breadcrumb-item active">Quotations</li>
        </ol>
    </nav>
</div>

<section class="section">
    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3 mt-3">
                        <h5 class="card-title mb-0">Quotation List</h5>
                        <div>
                            @can('export_quotations')
                            <a href="{{ route('admin.quotations.export') }}" class="btn btn-success btn-sm">
                                <i class="bi bi-file-excel"></i> Export
                            </a>
                            @endcan
                            @can('create_quotations')
                            <a href="{{ route('admin.quotations.create') }}" class="btn btn-primary btn-sm">
                                <i class="bi bi-plus-circle"></i> Create Quotation
                            </a>
                            @endcan
                        </div>
                    </div>

                    <!-- Filters -->
                    <div class="row mb-3">
                        <div class="col-md-2">
                            <label class="form-label">Type</label>
                            <select id="filter-type" class="form-select form-select-sm">
                                <option value="">All Types</option>
                                @foreach($types as $key => $label)
                                    <option value="{{ $key }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Status</label>
                            <select id="filter-status" class="form-select form-select-sm">
                                <option value="">All Statuses</option>
                                @foreach($statuses as $key => $label)
                                    <option value="{{ $key }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Client</label>
                            <select id="filter-client" class="form-select form-select-sm">
                                <option value="">All Clients</option>
                                @foreach($clients as $client)
                                    <option value="{{ $client->id }}">{{ $client->client_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Vendor</label>
                            <select id="filter-vendor" class="form-select form-select-sm">
                                <option value="">All Vendors</option>
                                @foreach($vendors as $vendor)
                                    <option value="{{ $vendor->id }}">{{ $vendor->vendor_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Date From</label>
                            <input type="date" id="filter-date-from" class="form-control form-control-sm">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Date To</label>
                            <input type="date" id="filter-date-to" class="form-control form-control-sm">
                        </div>
                    </div>

                    <!-- DataTable -->
                    <div class="table-responsive">
                        <table id="quotations-table" class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>Quotation No</th>
                                    <th>Date</th>
                                    <th>Type</th>
                                    <th>Client/Vendor</th>
                                    <th>Valid Until</th>
                                    <th>Amount (MYR)</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Initialize DataTable
    var table = $('#quotations-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route("admin.quotations.index") }}',
            data: function(d) {
                d.type = $('#filter-type').val();
                d.status = $('#filter-status').val();
                d.client_id = $('#filter-client').val();
                d.vendor_id = $('#filter-vendor').val();
                d.date_from = $('#filter-date-from').val();
                d.date_to = $('#filter-date-to').val();
            }
        },
        columns: [
            { data: 'quotation_no', name: 'quotation_no' },
            { data: 'quotation_date', name: 'quotation_date' },
            { data: 'type_badge', name: 'quotation_type' },
            { data: 'party_name', name: 'party_name' },
            { data: 'valid_until', name: 'valid_until' },
            { data: 'total_amount', name: 'total_amount', className: 'text-end' },
            { data: 'status_badge', name: 'status' },
            { data: 'actions', name: 'actions', orderable: false, searchable: false }
        ],
        order: [[1, 'desc']]
    });

    // Filter change handlers
    $('#filter-type, #filter-status, #filter-client, #filter-vendor, #filter-date-from, #filter-date-to').change(function() {
        table.draw();
    });

    // Delete quotation
    $(document).on('click', '.delete-btn', function() {
        var id = $(this).data('id');
        var url = '{{ route("admin.quotations.destroy", ":id") }}'.replace(':id', id);

        Swal.fire({
            title: 'Are you sure?',
            text: "You won't be able to revert this!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: url,
                    type: 'DELETE',
                    data: {
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        if (response.success) {
                            Swal.fire('Deleted!', response.message, 'success');
                            table.draw();
                        }
                    },
                    error: function(xhr) {
                        Swal.fire('Error!', xhr.responseJSON?.message || 'An error occurred', 'error');
                    }
                });
            }
        });
    });

    // Submit for approval
    $(document).on('click', '.submit-approval-btn', function() {
        var id = $(this).data('id');
        var url = '{{ route("admin.quotations.submit-approval", ":id") }}'.replace(':id', id);

        Swal.fire({
            title: 'Submit for Approval?',
            text: "This quotation will be sent for approval.",
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Yes, submit!'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: url,
                    type: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        if (response.success) {
                            Swal.fire('Success!', response.message, 'success');
                            table.draw();
                        }
                    },
                    error: function(xhr) {
                        Swal.fire('Error!', xhr.responseJSON?.message || 'An error occurred', 'error');
                    }
                });
            }
        });
    });
});
</script>
@endpush
