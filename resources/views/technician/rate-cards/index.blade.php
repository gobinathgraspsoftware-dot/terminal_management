@extends('layouts.app')
@section('title', 'My Commission Rates')
@section('content')
<div class="container-fluid">
    <div class="row mb-3"><div class="col-md-12"><h4><i class="bi bi-cash-coin"></i> My Commission Rates</h4><p class="text-muted">View applicable commission rates for your jobs</p></div></div>
    <div class="card">
        <div class="card-body">
            <table class="table table-hover" id="rateCardsTable">
                <thead><tr><th>Job Type</th><th>Model</th><th>State</th><th>Calculation</th><th>Rate</th><th>Min</th><th>Max</th><th>Valid Until</th></tr></thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
    <div class="card mt-3">
        <div class="card-header bg-info text-white"><h5><i class="bi bi-calculator"></i> Commission Calculator</h5></div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-3"><label>Job Type</label><select class="form-select" id="calc_job_type">@foreach($jobTypes as $k => $v)<option value="{{ $k }}">{{ $v }}</option>@endforeach</select></div>
                <div class="col-md-3"><label>Job Value (RM)</label><input type="number" class="form-control" id="calc_job_value" value="1000"></div>
                <div class="col-md-3"><label>Terminals</label><input type="number" class="form-control" id="calc_terminals" value="1" min="1"></div>
                <div class="col-md-3"><label>&nbsp;</label><button class="btn btn-info w-100" id="calcBtn"><i class="bi bi-calculator"></i> Calculate</button></div>
            </div>
            <div id="calcResult" class="alert alert-success mt-3 d-none"><h5>Commission: <span id="calcAmount"></span></h5></div>
        </div>
    </div>
</div>
@endsection
@push('scripts')
<script>
$(document).ready(function() {
    $('#rateCardsTable').DataTable({processing: true, serverSide: true, ajax: {url: '{{ route('technician.rate-cards.index') }}', data: function(d) {d.effective_only = true;}}, columns: [{data: 'job_type'},{data: 'model'},{data: 'state'},{data: 'calculation_type'},{data: 'rate_amount', className: 'text-end'},{data: function(row){return row.min_amount ? 'RM ' + parseFloat(row.min_amount).toFixed(2) : 'None';}},{data: function(row){return row.max_amount ? 'RM ' + parseFloat(row.max_amount).toFixed(2) : 'None';}},{data: 'effective_to'}], order: [[0, 'asc']]});
    
    $('#calcBtn').on('click', function() {
        $.post('{{ route('technician.rate-cards.calculate-preview') }}', {_token: '{{ csrf_token() }}', calculation_type: 'flat', rate_amount: 50, job_value: $('#calc_job_value').val(), terminal_count: $('#calc_terminals').val()}, function(res) {
            $('#calcAmount').text(res.formatted_amount);
            $('#calcResult').removeClass('d-none');
        });
    });
});
</script>
@endpush
