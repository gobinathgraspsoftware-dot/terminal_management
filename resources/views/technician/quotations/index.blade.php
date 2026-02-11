@extends('layouts.app')

@section('title', 'My Quotations')

@section('content')
<div class="pagetitle">
    <h1>My Quotations</h1>
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('technician.dashboard') }}">Home</a></li>
            <li class="breadcrumb-item active">Quotations</li>
        </ol>
    </nav>
</div>

<section class="section">
    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">My Quotation List</h5>

                    <!-- Filters -->
                    <div class="row mb-3">
                        <div class="col-md-3">
                            <label class="form-label">Type</label>
                            <select id="filter-type" class="form-select form-select-sm">
                                <option value="">All Types</option>
                                @foreach($types as $key => $label)
                                    <option value="{{ $key }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Status</label>
                            <select id="filter-status" class="form-select form-select-sm">
                                <option value="">All Statuses</option>
                                @foreach($statuses as $key => $label)
                                    <option value="{{ $key }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Date From</label>
                            <input type="date" id="filter-date-from" class="form-control form-control-sm">
                        </div>
                        <div class="col-md-3">
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
            url: '{{ route("technician.quotations.index") }}',
            data: function(d) {
                d.type = $('#filter-type').val();
                d.status = $('#filter-status').val();
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
            { data: 'amount', name: 'total_amount', className: 'text-end' },
            { data: 'status_badge', name: 'status' },
            { data: 'actions', name: 'actions', orderable: false, searchable: false }
        ],
        order: [[1, 'desc']]
    });

    // Filter change handlers
    $('#filter-type, #filter-status, #filter-date-from, #filter-date-to').change(function() {
        table.draw();
    });
});
</script>
@endpush
