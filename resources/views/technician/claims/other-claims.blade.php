@extends('layouts.app')

@section('title', 'My Other Claims')

@section('content')
<div class="container-fluid">
    {{-- Page Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1">My Other Claims</h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('technician.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('technician.claims.index') }}">Claims</a></li>
                    <li class="breadcrumb-item active">Other Claims</li>
                </ol>
            </nav>
        </div>
        <a href="{{ route('technician.claims.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-circle me-1"></i> Submit Other Claim
        </a>
    </div>

    {{-- Status Filter --}}
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body py-2">
            <div class="row align-items-center">
                <div class="col-auto">
                    <label class="form-label mb-0 small fw-bold">Filter by Status:</label>
                </div>
                <div class="col-md-3">
                    <select id="statusFilter" class="form-select form-select-sm">
                        <option value="">All Statuses</option>
                        @foreach(\App\Models\Claim::getStatuses() as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>
    </div>

    {{-- DataTable --}}
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table id="otherClaimsTable" class="table table-hover table-sm align-middle w-100">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Claim No</th>
                            <th>Claim Type</th>
                            <th>Description</th>
                            <th>Amount (RM)</th>
                            <th>Status</th>
                            <th>Submitted</th>
                            <th><i class="bi bi-paperclip"></i></th>
                            <th>Actions</th>
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
$(function() {
    const table = $('#otherClaimsTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route("technician.claims.other-claims-data") }}',
            data: function(d) {
                d.status = $('#statusFilter').val();
            }
        },
        columns: [
            { data: null, orderable: false, searchable: false, render: function(data, type, row, meta) {
                return meta.row + meta.settings._iDisplayStart + 1;
            }},
            { data: 'claim_no' },
            { data: 'claim_type' },
            { data: 'description' },
            { data: 'total_amount', className: 'text-end' },
            { data: 'status', orderable: false },
            { data: 'submitted_at' },
            { data: 'has_attachments', className: 'text-center', orderable: false },
            { data: 'actions', orderable: false, searchable: false, className: 'text-nowrap' },
        ],
        order: [[6, 'desc']],
        pageLength: 25,
        language: { emptyTable: 'No other claims found.' }
    });

    $('#statusFilter').on('change', function() {
        table.ajax.reload();
    });
});
</script>
@endpush
