@php use App\Models\Ticket; @endphp
@extends('layouts.app')

@section('title', 'Edit Ticket - ' . $ticket->ticket_no)

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="mb-0"><i class="bi bi-pencil-square me-2"></i>Edit Ticket: {{ $ticket->ticket_no }}</h4>
        <a href="{{ route('supervisor.tickets.show', $ticket->id) }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left me-1"></i>Back</a>
    </div>

    <form id="ticketForm" method="POST" action="{{ route('supervisor.tickets.update', $ticket->id) }}">
        @csrf
        @method('PUT')
        <input type="hidden" name="supervisor_id" value="{{ auth()->id() }}">

        <div class="row g-4">
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white"><h6 class="mb-0"><i class="bi bi-building me-2"></i>Vendor & Location</h6></div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Vendor <span class="text-danger">*</span></label>
                                <select name="vendor_id" id="vendor_id" class="form-select" required>
                                    <option value="">Select Vendor</option>
                                    @foreach($vendors as $v)
                                        <option value="{{ $v->id }}" {{ $ticket->vendor_id == $v->id ? 'selected' : '' }}>{{ $v->vendor_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Vendor Ticket Ref No</label>
                                <input type="text" name="vendor_ticket_ref_no" class="form-control" value="{{ $ticket->vendor_ticket_ref_no }}" placeholder="Optional">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Branch <span class="text-danger">*</span></label>
                                <select name="vendor_branch_id" id="vendor_branch_id" class="form-select" required>
                                    @foreach($branches as $b)
                                        <option value="{{ $b->id }}" {{ $ticket->vendor_branch_id == $b->id ? 'selected' : '' }} data-state="{{ $b->state_id }}" data-city="{{ $b->city_id }}">{{ $b->branch_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">State <span class="text-danger">*</span></label>
                                <select name="state_id" id="state_id" class="form-select" required>
                                    <option value="">Select State</option>
                                    @foreach($states as $st)
                                        <option value="{{ $st->id }}" {{ $ticket->state_id == $st->id ? 'selected' : '' }}>{{ $st->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">District <span class="text-danger">*</span></label>
                                <select name="city_id" id="city_id" class="form-select" required>
                                    @foreach($cities as $c)
                                        <option value="{{ $c->id }}" {{ $ticket->city_id == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white"><h6 class="mb-0"><i class="bi bi-shop me-2"></i>Merchant Details</h6></div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6"><label class="form-label">TID <span class="text-danger">*</span></label><input type="text" name="tid" class="form-control" value="{{ $ticket->tid }}" required></div>
                            <div class="col-md-6"><label class="form-label">Merchant Name <span class="text-danger">*</span></label><input type="text" name="merchant_name" class="form-control" value="{{ $ticket->merchant_name }}" required></div>
                            <div class="col-md-8"><label class="form-label">Address <span class="text-danger">*</span></label><textarea name="merchant_address" class="form-control" rows="2" required>{{ $ticket->merchant_address }}</textarea></div>
                            <div class="col-md-4"><label class="form-label">Contact <span class="text-danger">*</span></label><input type="text" name="contact_number" class="form-control" value="{{ $ticket->contact_number }}" required></div>
                        </div>
                    </div>
                </div>

                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white"><h6 class="mb-0"><i class="bi bi-gear me-2"></i>Job Details</h6></div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">Job Type <span class="text-danger">*</span></label>
                                <select name="job_type_id" id="job_type_id" class="form-select" required>
                                    @foreach($jobTypes as $jt)
                                        <option value="{{ $jt->id }}" {{ $ticket->job_type_id == $jt->id ? 'selected' : '' }}>{{ $jt->job_title }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Charge</label>
                                <select name="charge_id" id="charge_id" class="form-select">
                                    <option value="">Select Charge</option>
                                    @foreach($charges as $ch)
                                        <option value="{{ $ch->id }}" {{ $ticket->charge_id == $ch->id ? 'selected' : '' }}>{{ $ch->charge_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Priority <span class="text-danger">*</span></label>
                                <select name="priority" class="form-select" required>
                                    @foreach(Ticket::getPriorities() as $val => $label)
                                        <option value="{{ $val }}" {{ $ticket->priority === $val ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4"><label class="form-label">SLA Hours</label><input type="number" name="sla_hours" class="form-control" value="{{ $ticket->sla_hours }}" min="1" max="720"></div>
                            <div class="col-12"><label class="form-label">Description <span class="text-danger">*</span></label><textarea name="description" class="form-control" rows="3" required>{{ $ticket->description }}</textarea></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white"><h6 class="mb-0"><i class="bi bi-people me-2"></i>Assignment</h6></div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Supervisor</label>
                            <input type="text" class="form-control" value="{{ auth()->user()->name }}" readonly>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Technician</label>
                            <select name="technician_id" id="technician_id" class="form-select">
                                <option value="">Unassigned</option>
                                @foreach($technicians as $t)
                                    <option value="{{ $t->id }}" {{ $ticket->technician_id == $t->id ? 'selected' : '' }}>{{ $t->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white"><h6 class="mb-0"><i class="bi bi-cash-coin me-2"></i>Claim</h6></div>
                    <div class="card-body">
                        <div class="mb-3"><label class="form-label">Mileage (KM)</label><input type="number" name="mileage" id="mileage" class="form-control" step="0.01" min="0" value="{{ $ticket->mileage ?? 0 }}"></div>
                        <div class="mb-3"><label class="form-label">Rate</label><input type="text" id="mileage_rate_display" class="form-control" readonly value="{{ number_format($ticket->mileage_rate ?? 0, 2) }}"></div>
                        <div class="mb-3"><label class="form-label">Amount</label><input type="text" id="mileage_amount_display" class="form-control" readonly value="{{ number_format($ticket->mileage_amount ?? 0, 2) }}"></div>
                        <div class="mb-3"><label class="form-label">Remarks</label><input type="text" name="mileage_remarks" class="form-control" value="{{ $ticket->mileage_remarks }}"></div>
                        <div class="mb-3"><label class="form-label">Toll (RM)</label><input type="number" name="toll" id="toll" class="form-control" step="0.01" min="0" value="{{ $ticket->toll ?? 0 }}"></div>
                        <div class="mb-3"><label class="form-label">Standby/Meal (RM)</label><input type="number" name="standby_meal" id="standby_meal" class="form-control" step="0.01" min="0" value="{{ $ticket->standby_meal ?? 0 }}"></div>
                        <div class="mb-3"><label class="form-label fw-bold text-success">Total (RM)</label><input type="text" id="total_claim_display" class="form-control fw-bold text-success" readonly value="{{ number_format($ticket->total_claim_amount ?? 0, 2) }}"></div>
                    </div>
                </div>

                <button type="submit" id="btnSubmit" class="btn btn-primary w-100"><i class="bi bi-check-lg me-1"></i>Update Ticket</button>
            </div>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
$(function() {
    var mileageRate = parseFloat('{{ $ticket->mileage_rate ?? 0 }}');

    $('#vendor_id').on('change', function() {
        var vendorId = $(this).val();
        $('#vendor_branch_id').html('<option value="">Loading...</option>');
        if (!vendorId) return;
        $.get('{{ route("supervisor.tickets.ajax.vendor-branches") }}', { vendor_id: vendorId }, function(data) {
            var opts = '<option value="">Select Branch</option>';
            $.each(data, function(i, b) { opts += '<option value="' + b.id + '" data-state="' + (b.state_id||'') + '" data-city="' + (b.city_id||'') + '">' + b.branch_name + '</option>'; });
            $('#vendor_branch_id').html(opts);
        });
    });

    $('#vendor_branch_id').on('change', function() {
        var opt = $(this).find(':selected');
        if (opt.data('state')) $('#state_id').val(opt.data('state')).trigger('change', [opt.data('city')]);
    });

    $('#state_id').on('change', function(e, presetCityId) {
        var stateId = $(this).val();
        if (!stateId) { $('#city_id').html('<option value="">Select State First</option>'); return; }
        $.get('{{ route("supervisor.tickets.ajax.cities") }}', { state_id: stateId }, function(data) {
            var opts = '<option value="">Select District</option>';
            $.each(data, function(i, c) { opts += '<option value="' + c.id + '">' + c.name + '</option>'; });
            $('#city_id').html(opts);
            if (presetCityId) $('#city_id').val(presetCityId);
        });
    });

    $('#job_type_id').on('change', function() {
        var jtId = $(this).val(), cur = '{{ $ticket->charge_id }}';
        if (!jtId) return;
        $.get('{{ route("supervisor.tickets.ajax.charges") }}', { job_type_id: jtId }, function(data) {
            var opts = '<option value="">Select Charge</option>';
            $.each(data, function(i, c) { opts += '<option value="' + c.id + '"' + (c.id == cur ? ' selected' : '') + '>' + c.charge_name + '</option>'; });
            $('#charge_id').html(opts);
        });
    });

    $('#mileage, #toll, #standby_meal').on('input', calcClaim);
    function calcClaim() {
        var km = parseFloat($('#mileage').val()) || 0;
        var mAmt = km * mileageRate;
        $('#mileage_amount_display').val(mAmt.toFixed(2));
        $('#total_claim_display').val((mAmt + (parseFloat($('#toll').val())||0) + (parseFloat($('#standby_meal').val())||0)).toFixed(2));
    }

    $('#ticketForm').on('submit', function(e) {
        e.preventDefault();
        var btn = $('#btnSubmit');
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>Updating...');
        $.ajax({
            url: $(this).attr('action'), method: 'POST', data: $(this).serialize(),
            success: function(res) { if (res.success) { showToast(res.message, 'success'); setTimeout(function() { window.location.href = res.redirect; }, 1000); } else { showToast(res.message, 'error'); btn.prop('disabled', false).html('<i class="bi bi-check-lg me-1"></i>Update Ticket'); } },
            error: function(xhr) { showToast(xhr.responseJSON?.errors ? Object.values(xhr.responseJSON.errors).flat().join('<br>') : 'Error.', 'error'); btn.prop('disabled', false).html('<i class="bi bi-check-lg me-1"></i>Update Ticket'); }
        });
    });
});
</script>
@endpush
