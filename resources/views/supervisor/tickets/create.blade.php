@php use App\Models\Ticket; @endphp
@extends('layouts.app')

@section('title', 'Create Ticket')

@section('content')
<div class="container-fluid">
    <div class="page-header d-flex justify-content-between align-items-center">
        <h1><i class="bi bi-plus-circle me-2"></i>Create Ticket</h1>
        <a href="{{ route('supervisor.tickets.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i>Back to Tickets
        </a>
    </div>

    <form id="ticketForm" method="POST" action="{{ route('supervisor.tickets.store') }}">
        @csrf
        <input type="hidden" name="supervisor_id" value="{{ auth()->id() }}">

        <div class="card">
            <div class="card-body">

                {{-- Section 1: Ticket Information --}}
                <div class="alert fw-bold mb-4" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: none; border-radius: 6px;">
                    <i class="bi bi-info-circle me-2"></i>Section 1: Ticket Information
                </div>
                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <label class="form-label">Vendor <span class="text-danger">*</span></label>
                        <select name="vendor_id" id="vendor_id" class="form-select" required>
                            <option value="">Select Vendor</option>
                            @foreach($vendors as $v)
                                <option value="{{ $v->id }}">{{ $v->vendor_name }} ({{ $v->vendor_code }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Vendor Ticket Ref No</label>
                        <input type="text" name="vendor_ticket_ref_no" class="form-control" placeholder="External ticket reference">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Branch <span class="text-danger">*</span></label>
                        <select name="vendor_branch_id" id="vendor_branch_id" class="form-select" required>
                            <option value="">Select Branch</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">State <span class="text-danger">*</span></label>
                        <select name="state_id" id="state_id" class="form-select" required>
                            <option value="">Select State</option>
                            @foreach($states as $st)
                                <option value="{{ $st->id }}">{{ $st->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">District <span class="text-danger">*</span></label>
                        <select name="city_id" id="city_id" class="form-select" required>
                            <option value="">Select District</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Job Type <span class="text-danger">*</span></label>
                        <select name="job_type_id" id="job_type_id" class="form-select" required>
                            <option value="">Select Job Type</option>
                            @foreach($jobTypes as $jt)
                                <option value="{{ $jt->id }}">{{ $jt->job_title }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Charge</label>
                        <select name="charge_id" id="charge_id" class="form-select">
                            <option value="">Select Charge</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Supervisor</label>
                        <input type="text" class="form-control" value="{{ auth()->user()->name }}" readonly>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Assignee (Technician)</label>
                        <select name="technician_id" id="technician_id" class="form-select">
                            <option value="">Select Technician (Optional)</option>
                            {{-- Supervisor (self) as first assignable option --}}
                            <option value="{{ auth()->id() }}">{{ auth()->user()->name }} (Supervisor)</option>
                            @foreach($technicians as $t)
                                <option value="{{ $t->id }}">{{ $t->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Priority <span class="text-danger">*</span></label>
                        <select name="priority" class="form-select" required>
                            @foreach(Ticket::getPriorities() as $val => $label)
                                <option value="{{ $val }}" {{ $val === 'normal' ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">SLA (Hours) <span class="text-danger">*</span></label>
                        <input type="number" name="sla_hours" class="form-control" value="24" min="1" max="720">
                        <small class="text-muted">Default: 24 hours</small>
                    </div>
                </div>

                {{-- Section 2: Merchant Details --}}
                <div class="alert fw-bold mb-4" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: none; border-radius: 6px;">
                    <i class="bi bi-shop me-2"></i>Section 2: Merchant Details
                </div>
                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <label class="form-label">Terminal ID (TID) <span class="text-danger">*</span></label>
                        <input type="text" name="tid" class="form-control" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Merchant Name <span class="text-danger">*</span></label>
                        <input type="text" name="merchant_name" class="form-control" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Contact Number <span class="text-danger">*</span></label>
                        <input type="text" name="contact_number" class="form-control" required>
                    </div>
                    <div class="col-md-8">
                        <label class="form-label">Merchant Address <span class="text-danger">*</span></label>
                        <textarea name="merchant_address" class="form-control" rows="2" required></textarea>
                    </div>
                </div>

                {{-- Section 3: Description & Claim --}}
                <div class="alert fw-bold mb-4" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: none; border-radius: 6px;">
                    <i class="bi bi-card-text me-2"></i>Section 3: Description & Claim
                </div>
                <div class="row g-3 mb-4">
                    <div class="col-12">
                        <label class="form-label">Description <span class="text-danger">*</span></label>
                        <textarea name="description" class="form-control" rows="3" required></textarea>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Mileage (KM)</label>
                        <input type="number" name="mileage" id="mileage" class="form-control" step="0.01" min="0" value="0">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Mileage Rate (RM/KM)</label>
                        <input type="text" id="mileage_rate_display" class="form-control" readonly value="{{ number_format(auth()->user()->mileage_rate ?? 0, 2) }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Mileage Amount (RM)</label>
                        <input type="text" id="mileage_amount_display" class="form-control" readonly value="0.00">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Mileage Remarks</label>
                        <input type="text" name="mileage_remarks" class="form-control" placeholder="Optional">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Toll (RM)</label>
                        <input type="number" name="toll" id="toll" class="form-control" step="0.01" min="0" value="0">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Standby / Meal (RM)</label>
                        <input type="number" name="standby_meal" id="standby_meal" class="form-control" step="0.01" min="0" value="0">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold text-success">Total Claim (RM)</label>
                        <input type="text" id="total_claim_display" class="form-control fw-bold text-success" readonly value="0.00">
                    </div>
                </div>

                <hr>
                <div class="text-end">
                    <a href="{{ route('supervisor.tickets.index') }}" class="btn btn-secondary me-2">Cancel</a>
                    <button type="submit" id="btnSubmit" class="btn btn-primary">
                        <i class="bi bi-check-lg me-1"></i>Create Ticket
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
$(function() {
    var mileageRate = parseFloat('{{ auth()->user()->mileage_rate ?? 0 }}');

    $('#vendor_id').on('change', function() {
        var vendorId = $(this).val();
        $('#vendor_branch_id').html('<option value="">Loading...</option>');
        if (!vendorId) { $('#vendor_branch_id').html('<option value="">Select Branch</option>'); return; }
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
        if (!stateId) { $('#city_id').html('<option value="">Select District</option>'); return; }
        $.get('{{ route("supervisor.tickets.ajax.cities") }}', { state_id: stateId }, function(data) {
            var opts = '<option value="">Select District</option>';
            $.each(data, function(i, c) { opts += '<option value="' + c.id + '">' + c.name + '</option>'; });
            $('#city_id').html(opts);
            if (presetCityId) $('#city_id').val(presetCityId);
        });
    });

    $('#job_type_id').on('change', function() {
        var jtId = $(this).val();
        if (!jtId) { $('#charge_id').html('<option value="">Select Charge</option>'); return; }
        $.get('{{ route("supervisor.tickets.ajax.charges") }}', { job_type_id: jtId }, function(data) {
            var opts = '<option value="">Select Charge</option>';
            $.each(data, function(i, c) { opts += '<option value="' + c.id + '">' + c.charge_name + ' (RM ' + parseFloat(c.default_price).toFixed(2) + ')</option>'; });
            $('#charge_id').html(opts);
        });
    });

    $('#mileage, #toll, #standby_meal').on('input', calcClaim);
    function calcClaim() {
        var km = parseFloat($('#mileage').val()) || 0, mAmt = km * mileageRate;
        $('#mileage_amount_display').val(mAmt.toFixed(2));
        $('#total_claim_display').val((mAmt + (parseFloat($('#toll').val())||0) + (parseFloat($('#standby_meal').val())||0)).toFixed(2));
    }

    $('#ticketForm').on('submit', function(e) {
        e.preventDefault();
        var btn = $('#btnSubmit');
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>Creating...');
        $.ajax({
            url: $(this).attr('action'), method: 'POST', data: $(this).serialize(),
            success: function(res) {
                if (res.success) { showToast(res.message, 'success'); setTimeout(function() { window.location.href = res.redirect; }, 1000); }
                else { showToast(res.message || 'Error.', 'error'); btn.prop('disabled', false).html('<i class="bi bi-check-lg me-1"></i>Create Ticket'); }
            },
            error: function(xhr) {
                var msg = xhr.responseJSON?.errors ? Object.values(xhr.responseJSON.errors).flat().join('<br>') : (xhr.responseJSON?.message || 'Error.');
                showToast(msg, 'error'); btn.prop('disabled', false).html('<i class="bi bi-check-lg me-1"></i>Create Ticket');
            }
        });
    });
});
</script>
@endpush
