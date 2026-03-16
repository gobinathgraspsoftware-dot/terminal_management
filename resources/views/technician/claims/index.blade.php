@extends('layouts.app')

@section('title', 'My Claims')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1">My Claims</h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('technician.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item active">My Claims</li>
                </ol>
            </nav>
        </div>
        <div>
            @can('create_claims')
            <a href="{{ route('technician.claims.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-lg me-1"></i> Submit Other Claim
            </a>
            @endcan
        </div>
    </div>

    <!-- Filters -->
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body py-2">
            <div class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label mb-1 small">Category</label>
                    <select id="filter-category" class="form-select form-select-sm">
                        <option value="">All</option>
                        <option value="ticket">Ticket Claims</option>
                        <option value="other">Other Claims</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label mb-1 small">Status</label>
                    <select id="filter-status" class="form-select form-select-sm">
                        <option value="">All</option>
                        @foreach(\App\Models\Claim::getStatuses() as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <button id="btn-reset" class="btn btn-sm btn-outline-secondary w-100">
                        <i class="bi bi-arrow-counterclockwise me-1"></i> Reset
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table id="claims-table" class="table table-hover table-sm align-middle w-100">
                    <thead class="table-light">
                        <tr>
                            <th>Claim ID</th>
                            <th>Category</th>
                            <th>Ticket</th>
                            <th>Description</th>
                            <th class="text-end">Amount (RM)</th>
                            <th>Status</th>
                            <th>Submitted</th>
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
$(function() {
    var table = $('#claims-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route("technician.claims.data") }}',
            data: function(d) {
                d.category = $('#filter-category').val();
                d.status   = $('#filter-status').val();
            }
        },
        columns: [
            { data: 'claim_no' },
            { data: 'category', orderable: false },
            { data: 'ticket_no' },
            { data: 'description' },
            { data: 'total_amount', className: 'text-end' },
            { data: 'status', orderable: false },
            { data: 'submitted_at' },
            { data: 'actions', orderable: false, searchable: false, className: 'text-center' }
        ],
        order: [[6, 'desc']],
        pageLength: 25
    });

    $('#filter-category, #filter-status').on('change', function() { table.ajax.reload(); });
    $('#btn-reset').on('click', function() {
        $('#filter-category, #filter-status').val('');
        table.ajax.reload();
    });
});
</script>
@endpush
