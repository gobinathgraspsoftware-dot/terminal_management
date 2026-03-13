@extends('layouts.app')
@section('title', 'Edit Ticket ' . $ticket->ticket_no)

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="mb-0"><i class="bi bi-pencil-square me-2"></i>Edit Ticket: {{ $ticket->ticket_no }}</h4>
        <a href="{{ route('admin.tickets.show', $ticket->id) }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left me-1"></i> Back</a>
    </div>

    <form id="ticketForm" method="POST" action="{{ route('admin.tickets.update', $ticket->id) }}">
        @csrf
        @method('PUT')

        {{-- Section 1: Ticket Information --}}
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-primary text-white"><i class="bi bi-info-circle me-2"></i>Section 1: Ticket Information</div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Ticket ID</label>
                        <input type="text" class="form-control" value="{{ $ticket->ticket_no }}" readonly>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Vendor <span class="text-danger">*</span></label>
                        <select name="vendor_id" id="vendor_id" class="form-select" required>
                            <option value="">Select Vendor</option>
                            @foreach($vendors as $vendor)
                                <option value="{{ $vendor->id }}" {{ $ticket->vendor_id == $vendor->id ? 'selected' : '' }}>{{ $vendor->vendor_code }} - {{ $vendor->vendor_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Vendor Ticket Ref No <span class="text-danger">*</span></label>
                        <input type="text" name="vendor_ticket_ref_no" class="form-control" value="{{ $ticket->vendor_ticket_ref_no }}" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Branch <span class="text-danger">*</span></label>
                        <select name="vendor_branch_id" id="vendor_branch_id" class="form-select" required>
                            <option value="">Select Branch</option>
                            @foreach($branches as $b)
                                <option value="{{ $b->id }}" {{ $ticket->vendor_branch_id == $b->id ? 'selected' : '' }}>{{ $b->branch_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">State <span class="text-danger">*</span></label>
                        <select name="state_id" id="state_id" class="form-select" required>
                            <option value="">Select State</option>
                            @foreach($states as $state)
                                <option value="{{ $state->id }}" {{ $ticket->state_id == $state->id ? 'selected' : '' }}>{{ $state->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">District <span class="text-danger">*</span></label>
                        <select name="city_id" id="city_id" class="form-select" required>
                            <option value="">Select District</option>
                            @foreach($cities as $city)
                                <option value="{{ $city->id }}" {{ $ticket->city_id == $city->id ? 'selected' : '' }}>{{ $city->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Job Type <span class="text-danger">*</span></label>
                        <select name="job_type_id" class="form-select" required>
                            @foreach($jobTypes as $jt)
                                <option value="{{ $jt->id }}" {{ $ticket->job_type_id == $jt->id ? 'selected' : '' }}>{{ $jt->job_title }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Charge</label>
                        <select name="charge_id" class="form-select">
                            <option value="">Select Charge</option>
                            @foreach($charges as $c)
                                <option value="{{ $c->id }}" {{ $ticket->charge_id == $c->id ? 'selected' : '' }}>{{ $c->charge_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Supervisor <span class="text-danger">*</span></label>
                        <select name="supervisor_id" id="supervisor_id" class="form-select" required>
                            @foreach($supervisors as $sup)
                                <option value="{{ $sup->id }}" {{ $ticket->supervisor_id == $sup->id ? 'selected' : '' }} data-mileage-rate="{{ $sup->mileage_rate ?? 0 }}">{{ $sup->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Assignee</label>
                        <select name="technician_id" id="technician_id" class="form-select">
                            <option value="">Select Technician</option>
                            @foreach($technicians as $tech)
                                <option value="{{ $tech->id }}" {{ $ticket->technician_id == $tech->id ? 'selected' : '' }}>{{ $tech->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Priority <span class="text-danger">*</span></label>
                        <select name="priority" class="form-select" required>
                            @foreach(['low'=>'Low','normal'=>'Normal','high'=>'High','urgent'=>'Urgent'] as $k=>$v)
                                <option value="{{ $k }}" {{ $ticket->priority == $k ? 'selected' : '' }}>{{ $v }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">SLA (Hours)</label>
                        <input type="number" name="sla_hours" class="form-control" value="{{ $ticket->sla_hours }}" min="1" max="720">
                    </div>
                </div>
            </div>
        </div>

        {{-- Section 2: Merchant --}}
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-success text-white"><i class="bi bi-shop me-2"></i>Section 2: Merchant Information</div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">TID <span class="text-danger">*</span></label>
                        <input type="text" name="tid" class="form-control" value="{{ $ticket->tid }}" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Merchant Name <span class="text-danger">*</span></label>
                        <input type="text" name="merchant_name" class="form-control" value="{{ $ticket->merchant_name }}" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Contact Number <span class="text-danger">*</span></label>
                        <input type="text" name="contact_number" class="form-control" value="{{ $ticket->contact_number }}" required>
                    </div>
                    <div class="col-md-12">
                        <label class="form-label">Merchant Address <span class="text-danger">*</span></label>
                        <textarea name="merchant_address" class="form-control" rows="2" required>{{ $ticket->merchant_address }}</textarea>
                    </div>
                </div>
            </div>
        </div>

        {{-- Section 3: Description --}}
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-info text-white"><i class="bi bi-card-text me-2"></i>Section 3: Description</div>
            <div class="card-body">
                <textarea name="description" class="form-control" rows="4" required>{{ $ticket->description }}</textarea>
            </div>
        </div>

        {{-- Claim --}}
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-warning text-dark"><i class="bi bi-cash-coin me-2"></i>Section 5: Claim</div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Mileage (KM)</label>
                        <input type="number" name="mileage" id="mileage" class="form-control" step="0.01" value="{{ $ticket->mileage ?? 0 }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Mileage Rate</label>
                        <input type="text" id="mileage_rate_display" class="form-control" readonly value="{{ number_format($ticket->mileage_rate ?? 0, 2) }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Mileage Claim (RM)</label>
                        <input type="text" id="mileage_amount_display" class="form-control fw-bold" readonly value="{{ number_format($ticket->mileage_amount ?? 0, 2) }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Mileage Remarks</label>
                        <input type="text" name="mileage_remarks" class="form-control" value="{{ $ticket->mileage_remarks }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Toll (RM)</label>
                        <input type="number" name="toll" id="toll" class="form-control" step="0.01" value="{{ $ticket->toll ?? 0 }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Standby / Meal (RM)</label>
                        <input type="number" name="standby_meal" id="standby_meal" class="form-control" step="0.01" value="{{ $ticket->standby_meal ?? 0 }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold text-primary">Total Claim (RM)</label>
                        <input type="text" id="total_claim_display" class="form-control fw-bold text-primary" readonly value="{{ number_format($ticket->total_claim_amount ?? 0, 2) }}">
                    </div>
                </div>
            </div>
        </div>

        <div class="text-end mb-4">
            <a href="{{ route('admin.tickets.show', $ticket->id) }}" class="btn btn-secondary me-2">Cancel</a>
            <button type="submit" class="btn btn-primary" id="submitBtn"><i class="bi bi-check-circle me-1"></i> Update Ticket</button>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
$(function() {
    $('#vendor_id').on('change', function() {
        $.get('{{ route("admin.tickets.ajax.vendor-branches") }}', {vendor_id: $(this).val()}, function(data) {
            var html = '<option value="">Select Branch</option>';
            data.forEach(function(b) { html += '<option value="'+b.id+'">'+b.branch_name+'</option>'; });
            $('#vendor_branch_id').html(html);
        });
    });

    $('#state_id').on('change', function() {
        $.get('{{ route("admin.tickets.ajax.cities") }}', {state_id: $(this).val()}, function(data) {
            var html = '<option value="">Select District</option>';
            data.forEach(function(c) { html += '<option value="'+c.id+'">'+c.name+'</option>'; });
            $('#city_id').html(html);
        });
    });

    $('#supervisor_id').on('change', function() {
        var rate = $(this).find(':selected').data('mileage-rate') || 0;
        $('#mileage_rate_display').val(parseFloat(rate).toFixed(2));
        recalcClaim();
    });

    function recalcClaim() {
        var m = parseFloat($('#mileage').val()) || 0;
        var r = parseFloat($('#mileage_rate_display').val()) || 0;
        var t = parseFloat($('#toll').val()) || 0;
        var s = parseFloat($('#standby_meal').val()) || 0;
        $('#mileage_amount_display').val((m*r).toFixed(2));
        $('#total_claim_display').val((m*r+t+s).toFixed(2));
    }
    $('#mileage, #toll, #standby_meal').on('input change', recalcClaim);

    $('#ticketForm').on('submit', function(e) {
        e.preventDefault();
        var $btn = $('#submitBtn');
        $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Updating...');
        $.ajax({
            url: $(this).attr('action'),
            method: 'POST',
            data: $(this).serialize(),
            success: function(res) {
                if (res.success) { showToast(res.message, 'success'); setTimeout(function(){ window.location.href = res.redirect; }, 1000); }
            },
            error: function(xhr) {
                $btn.prop('disabled', false).html('<i class="bi bi-check-circle me-1"></i> Update Ticket');
                if (xhr.status === 422) {
                    var errors = xhr.responseJSON?.errors || {};
                    $('.is-invalid').removeClass('is-invalid');
                    Object.keys(errors).forEach(function(f) { $('[name="'+f+'"]').addClass('is-invalid').siblings('.invalid-feedback').text(errors[f][0]); });
                } else { showToast(xhr.responseJSON?.message || 'Failed.', 'error'); }
            }
        });
    });
});
</script>
@endpush
