@extends('layouts.app')

@section('title', 'My Other Claims')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1"><i class="bi bi-file-earmark-text me-2"></i>My Other Claims</h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('technician.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('technician.claims.index') }}">Claims</a></li>
                    <li class="breadcrumb-item active">Other Claims</li>
                </ol>
            </nav>
        </div>
        <a href="{{ route('technician.claims.create') }}" class="btn btn-primary btn-sm">
            <i class="bi bi-plus-circle me-1"></i> New Other Claim
        </a>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table id="otherClaimsTable" class="table table-striped table-hover" style="width:100%">
                    <thead>
                        <tr>
                            <th>Claim No</th>
                            <th>Type</th>
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
    $('#otherClaimsTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: '{{ route("technician.claims.other-claims-data") }}',
        columns: [
            { data: 'claim_no' },
            { data: 'claim_type' },
            { data: 'description' },
            { data: 'total_amount', className: 'text-end' },
            { data: 'status', orderable: false },
            { data: 'submitted_at' },
            { data: 'has_attachments', orderable: false, searchable: false },
            { data: 'actions', orderable: false, searchable: false },
        ],
        order: [[5, 'desc']],
        responsive: true,
    });
});
</script>
@endpush
