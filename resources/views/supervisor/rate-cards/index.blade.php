@extends('layouts.app')
@section('title', 'Rate Cards')
@section('content')
<div class="container-fluid">
    <div class="row mb-3"><div class="col-md-12"><h4><i class="bi bi-credit-card-2-front"></i> Rate Cards (View Only)</h4></div></div>
    <div class="card">
        <div class="card-body">
            <table class="table table-hover" id="rateCardsTable">
                <thead><tr><th>Code</th><th>Name</th><th>Job Type</th><th>Model</th><th>State</th><th>Calc Type</th><th>Rate</th><th>Effective</th><th>Status</th><th>Actions</th></tr></thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>
@endsection
@push('scripts')
<script>
$(document).ready(function() {
    $('#rateCardsTable').DataTable({processing: true, serverSide: true, ajax: '{{ route('supervisor.rate-cards.index') }}', columns: [{data: 'rate_card_code'},{data: 'rate_card_name'},{data: 'job_type'},{data: 'model'},{data: 'state'},{data: 'calculation_type'},{data: 'rate_amount', className: 'text-end'},{data: 'effective_from'},{data: 'status_badge'},{data: 'id', orderable: false, searchable: false, render: function(data) {return '<a href="/supervisor/rate-cards/' + data + '" class="btn btn-sm btn-info"><i class="bi bi-eye"></i></a>';}}], order: [[0, 'desc']]});
});
</script>
@endpush
