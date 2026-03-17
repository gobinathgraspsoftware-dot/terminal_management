@php use App\Models\Ticket; @endphp
@extends('layouts.app')

@section('title', 'Edit Ticket - ' . $ticket->ticket_no)

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="mb-0"><i class="bi bi-pencil-square me-2"></i>Edit Ticket: {{ $ticket->ticket_no }}</h4>
        <a href="{{ route('admin.tickets.show', $ticket->id) }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i>Back
        </a>
    </div>

    <form id="ticketForm" method="POST" action="{{ route('admin.tickets.update', $ticket->id) }}">
        @csrf
        @method('PUT')
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
                                        <option value="{{ $v->id }}" {{ $ticket->vendor_id == $v->id ? 'selected' : '' }}>{{ $v->vendor_name }} ({{ $v->vendor_code }})</option>
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
                                    <option value="">Select Branch</option>
                                    @foreach($branches as $b)
                                        <option value="{{ $b->id }}" {{ $ticket->vendor_branch_id == $b->id ? 'selected' : '' }}
                                            data-state="{{ $b->state_id }}" data-city="{{ $b->city_id }}">{{ $b->branch_name }}</option>
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
                                    <option value="">Select District</option>
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
                            <div class="col-md-6">
                                <label class="form-label">Terminal ID (TID) <span class="text-danger">*</span></label>
                                <input type="text" name="tid" class="form-control" value="{{ $ticket->tid }}" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Merchant Name <span class="text-danger">*</span></label>
                                <input type="text" name="merchant_name" class="form-control" value="{{ $ticket->merchant_name }}" required>
                            </div>
                            <div class="col-md-8">
                                <label class="form-label">Merchant Address <span class="text-danger">*</span></label>
                                <textarea name="merchant_address" class="form-control" rows="2" required>{{ $ticket->merchant_address }}</textarea>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Contact Number <span class="text-danger">*</span></label>
                                <input type="text" name="contact_number" class="form-control" value="{{ $ticket->contact_number }}" required>
                            </div>
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
                                    <option value="">Select Job Type</option>
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
                            <div class="col-md-4">
                                <label class="form-label">SLA Hours</label>
                                <input type="number" name="sla_hours" class="form-control" value="{{ $ticket->sla_hours }}" min="1" max="720">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Description <span class="text-danger">*</span></label>
                                <textarea name="description" class="form-control" rows="3" required>{{ $ticket->description }}</textarea>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white"><h6 class="mb-0"><i class="bi bi-people me-2"></i>Assignment</h6></div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Supervisor <span class="text-danger">*</span></label>
                            <select name="supervisor_id" id="supervisor_id" class="form-select" required>
                                <option value="">Select Supervisor</option>
                                @foreach($supervisors as $s)
                                    <option value="{{ $s->id }}" {{ $ticket->supervisor_id == $s->id ? 'selected' : '' }}
                                        data-mileage-rate="{{ $s->mileage_rate ?? 0 }}">{{ $s->name }}</option>
                                @endforeach
                            </select>
                            <small class="text-muted">Filtered by State & District</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Technician (Assignee)</label>
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
                    <div class="card-header bg-white"><h6 class="mb-0"><i class="bi bi-cash-coin me-2"></i>Claim Details</h6></div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Mileage (KM)</label>
                            <input type="number" name="mileage" id="mileage" class="form-control" step="0.01" min="0" value="{{ $ticket->mileage ?? 0 }}">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Mileage Rate (RM/KM)</label>
                            <input type="text" id="mileage_rate_display" class="form-control" readonly value="{{ number_format($ticket->mileage_rate ?? 0, 2) }}">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Mileage Amount (RM)</label>
                            <input type="text" id="mileage_amount_display" class="form-control" readonly value="{{ number_format($ticket->mileage_amount ?? 0, 2) }}">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Mileage Remarks</label>
                            <input type="text" name="mileage_remarks" class="form-control" value="{{ $ticket->mileage_remarks }}">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Toll (RM)</label>
                            <input type="number" name="toll" id="toll" class="form-control" step="0.01" min="0" value="{{ $ticket->toll ?? 0 }}">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Standby / Meal (RM)</label>
                            <input type="number" name="standby_meal" id="standby_meal" class="form-control" step="0.01" min="0" value="{{ $ticket->standby_meal ?? 0 }}">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold text-success">Total Claim (RM)</label>
                            <input type="text" id="total_claim_display" class="form-control fw-bold text-success" readonly value="{{ number_format($ticket->total_claim_amount ?? 0, 2) }}">
                        </div>
                    </div>
                </div>

                <button type="submit" id="btnSubmit" class="btn btn-primary w-100 mb-2">
                    <i class="bi bi-check-lg me-1"></i>Update Ticket
                </button>
            </div>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
$(function() {
    var mileageRate = parseFloat('{{ $ticket->mileage_rate ?? 0 }}');

    // ── Vendor → Branches ──
    $('#vendor_id').on('change', function() {
        var vendorId = $(this).val();
        $('#vendor_branch_id').html('<option value="">Loading...</option>');
        if (!vendorId) { $('#vendor_branch_id').html('<option value="">Select Vendor First</option>'); return; }
        $.get('{{ route("admin.tickets.ajax.vendor-branches") }}', { vendor_id: vendorId }, function(data) {
            var opts = '<option value="">Select Branch</option>';
            $.each(data, function(i, b) {
                opts += '<option value="' + b.id + '" data-state="' + (b.state_id||'') + '" data-city="' + (b.city_id||'') + '">' + b.branch_name + '</option>';
            });
            $('#vendor_branch_id').html(opts);
        });
    });

    $('#vendor_branch_id').on('change', function() {
        var opt = $(this).find(':selected');
        var stateId = opt.data('state');
        var cityId = opt.data('city');
        if (stateId) $('#state_id').val(stateId).trigger('change', [cityId]);
    });

    // ── State → Cities + Supervisors ──
    $('#state_id').on('change', function(e, presetCityId) {
        var stateId = $(this).val();
        $('#city_id').html('<option value="">Loading...</option>');
        if (!stateId) {
            $('#city_id').html('<option value="">Select State First</option>');
            $('#supervisor_id').html('<option value="">Select State First</option>');
            return;
        }
        $.get('{{ route("admin.tickets.ajax.cities") }}', { state_id: stateId }, function(data) {
            var opts = '<option value="">Select District</option>';
            $.each(data, function(i, c) { opts += '<option value="' + c.id + '">' + c.name + '</option>'; });
            $('#city_id').html(opts);
            if (presetCityId) $('#city_id').val(presetCityId);
        });
        loadSupervisors(stateId, null);
    });

    $('#city_id').on('change', function() {
        var stateId = $('#state_id').val();
        var cityId = $(this).val();
        if (stateId) loadSupervisors(stateId, cityId);
    });

    function loadSupervisors(stateId, cityId) {
        var currentSup = '{{ $ticket->supervisor_id }}';
        $('#supervisor_id').html('<option value="">Loading...</option>');
        var params = { state_id: stateId };
        if (cityId) params.city_id = cityId;
        $.get('{{ route("admin.tickets.ajax.supervisors") }}', params, function(data) {
            var opts = '<option value="">Select Supervisor</option>';
            $.each(data, function(i, s) {
                var sel = (s.id == currentSup) ? ' selected' : '';
                opts += '<option value="' + s.id + '" data-mileage-rate="' + (s.mileage_rate||0) + '"' + sel + '>' + s.name + '</option>';
            });
            $('#supervisor_id').html(opts);
        });
    }

    // ── Supervisor → Technicians + Mileage Rate ──
    $('#supervisor_id').on('change', function() {
        var supId = $(this).val();
        var opt = $(this).find(':selected');
        mileageRate = parseFloat(opt.data('mileage-rate')) || 0;
        $('#mileage_rate_display').val(mileageRate.toFixed(2));
        calcClaim();

        var currentTech = '{{ $ticket->technician_id }}';
        $('#technician_id').html('<option value="">Loading...</option>');
        if (!supId) { $('#technician_id').html('<option value="">Select Supervisor First</option>'); return; }
        $.get('{{ route("admin.tickets.ajax.technicians") }}', { supervisor_id: supId }, function(data) {
            var opts = '<option value="">Unassigned</option>';
            $.each(data, function(i, t) {
                var sel = (t.id == currentTech) ? ' selected' : '';
                opts += '<option value="' + t.id + '"' + sel + '>' + t.name + '</option>';
            });
            $('#technician_id').html(opts);
        });
    });

    // ── Job Type → Charges ──
    $('#job_type_id').on('change', function() {
        var jtId = $(this).val();
        var currentCharge = '{{ $ticket->charge_id }}';
        $('#charge_id').html('<option value="">Loading...</option>');
        if (!jtId) { $('#charge_id').html('<option value="">Select Job Type First</option>'); return; }
        $.get('{{ route("admin.tickets.ajax.charges") }}', { job_type_id: jtId }, function(data) {
            var opts = '<option value="">Select Charge</option>';
            $.each(data, function(i, c) {
                var sel = (c.id == currentCharge) ? ' selected' : '';
                opts += '<option value="' + c.id + '"' + sel + '>' + c.charge_name + ' (RM ' + parseFloat(c.default_price).toFixed(2) + ')</option>';
            });
            $('#charge_id').html(opts);
        });
    });

    // ── Claim Calculation ──
    $('#mileage, #toll, #standby_meal').on('input', calcClaim);
    function calcClaim() {
        var km = parseFloat($('#mileage').val()) || 0;
        var mileageAmt = km * mileageRate;
        var toll = parseFloat($('#toll').val()) || 0;
        var meal = parseFloat($('#standby_meal').val()) || 0;
        $('#mileage_amount_display').val(mileageAmt.toFixed(2));
        $('#total_claim_display').val((mileageAmt + toll + meal).toFixed(2));
    }

    // ── Form Submit ──
    $('#ticketForm').on('submit', function(e) {
        e.preventDefault();
        var btn = $('#btnSubmit');
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>Updating...');
        $.ajax({
            url: $(this).attr('action'),
            method: 'POST',
            data: $(this).serialize(),
            success: function(res) {
                if (res.success) {
                    showToast(res.message, 'success');
                    setTimeout(function() { window.location.href = res.redirect; }, 1000);
                } else {
                    showToast(res.message || 'Error.', 'error');
                    btn.prop('disabled', false).html('<i class="bi bi-check-lg me-1"></i>Update Ticket');
                }
            },
            error: function(xhr) {
                var msg = 'Error updating ticket.';
                if (xhr.responseJSON && xhr.responseJSON.errors) msg = Object.values(xhr.responseJSON.errors).flat().join('<br>');
                else if (xhr.responseJSON && xhr.responseJSON.message) msg = xhr.responseJSON.message;
                showToast(msg, 'error');
                btn.prop('disabled', false).html('<i class="bi bi-check-lg me-1"></i>Update Ticket');
            }
        });
    });
});
</script>
@endpush
