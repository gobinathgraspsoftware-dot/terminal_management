@extends('layouts.app')

@section('title', 'My Stock Issues')

@section('content')
<div class="container-fluid">
    <div class="mb-4">
        <h1 class="h3 mb-0">
            <i class="bi bi-box-arrow-right text-primary"></i> My Stock Issues
        </h1>
        <p class="text-muted mb-0">View stock issued to you or returned to depot</p>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table id="stockIssuesTable" class="table table-hover">
                    <thead>
                        <tr>
                            <th>Issue No</th>
                            <th>Date</th>
                            <th>Type</th>
                            <th>Location</th>
                            <th>Items</th>
                            <th>Status</th>
                            <th>Action</th>
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
$(document).ready(function() {
    $('#stockIssuesTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: '{{ route("technician.stock-issues.index") }}',
        columns: [
            { data: 'issue_no', name: 'issue_no' },
            { data: 'issue_date', name: 'issue_date' },
            { data: 'issue_type_badge', name: 'issue_type', orderable: false },
            { data: 'location', name: 'location', orderable: false },
            { data: 'total_items', name: 'total_items' },
            { data: 'status_badge', name: 'status', orderable: false },
            { data: 'action', name: 'action', orderable: false }
        ],
        order: [[1, 'desc']],
        responsive: true,
        pageLength: 15
    });
});
</script>
@endpush
