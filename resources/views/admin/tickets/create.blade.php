@extends('layouts.app')
@section('title', 'Create Ticket')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="mb-0"><i class="bi bi-plus-circle me-2"></i>Create Ticket</h4>
        <a href="{{ route('admin.tickets.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i> Back to Tickets
        </a>
    </div>

    <form id="ticketForm" method="POST" action="{{ route('admin.tickets.store') }}">
        @csrf

        {{-- Section 1: Ticket Information --}}
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-primary text-white"><i class="bi bi-info-circle me-2"></i>Section 1: Ticket Information</div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Vendor <span class="text-danger">*</span></label>
                        <select name="vendor_id" id="vendor_id" class="form-select" required>
                            <option value="">Select Vendor</option>
                            @foreach($vendors as $vendor)
                                <option value="{{ $vendor->id }}">{{ $vendor->vendor_code }} - {{ $vendor->vendor_name }}</option>
                            @endforeach
                        </select>
                        <div class="invalid-feedback"></div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Vendor Ticket Ref No <span class="text-danger">*</span></label>
                        <input type="text" name="vendor_ticket_ref_no" class="form-control" required placeholder="External ticket reference">
                        <div class="invalid-feedback"></div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Branch <span class="text-danger">*</span></label>
                        <select name="vendor_branch_id" id="vendor_branch_id" class="form-select" required>
                            <option value="">Select Branch</option>
                        </select>
                        <div class="invalid-feedback"></div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">State <span class="text-danger">*</span></label>
                        <select name="state_id" id="state_id" class="form-select" required>
                            <option value="">Select State</option>
                            @foreach($states as $state)
                                <option value="{{ $state->id }}">{{ $state->name }}</option>
                            @endforeach
                        </select>
                        <div class="invalid-feedback"></div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">District <span class="text-danger">*</span></label>
                        <select name="city_id" id="city_id" class="form-select" required>
                            <option value="">Select District</option>
                        </select>
                        <div class="invalid-feedback"></div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Job Type <span class="text-danger">*</span></label>
                        <select name="job_type_id" id="job_type_id" class="form-select" required>
                            <option value="">Select Job Type</option>
                            @foreach($jobTypes as $jt)
                                <option value="{{ $jt->id }}">{{ $jt->job_title }}</option>
                            @endforeach
                        </select>
                        <div class="invalid-feedback"></div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Charge</label>
                        <select name="charge_id" id="charge_id" class="form-select">
                            <option value="">Select Charge</option>
                            @foreach($charges as $c)
                                <option value="{{ $c->id }}">{{ $c->charge_name }} (RM {{ number_format($c->default_price, 2) }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Supervisor <span class="text-danger">*</span></label>
                        <select name="supervisor_id" id="supervisor_id" class="form-select" required>
                            <option value="">Select Supervisor</option>
                            @foreach($supervisors as $sup)
                                <option value="{{ $sup->id }}" data-mileage-rate="{{ $sup->mileage_rate ?? 0 }}">{{ $sup->name }}</option>
                            @endforeach
                        </select>
                        <div class="invalid-feedback"></div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Assignee (Technician)</label>
                        <select name="technician_id" id="technician_id" class="form-select">
                            <option value="">Select Technician (Optional)</option>
                            @foreach($technicians as $tech)
                                <option value="{{ $tech->id }}">{{ $tech->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Priority <span class="text-danger">*</span></label>
                        <select name="priority" class="form-select" required>
                            <option value="low">Low</option>
                            <option value="normal" selected>Normal</option>
                            <option value="high">High</option>
                            <option value="urgent">Urgent</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">SLA (Hours) <span class="text-danger">*</span></label>
                        <input type="number" name="sla_hours" class="form-control" value="24" min="1" max="720" required>
                        <small class="text-muted">Default: 24 hours</small>
                    </div>
                </div>
            </div>
        </div>

        {{-- Section 2: Merchant Information --}}
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-success text-white"><i class="bi bi-shop me-2"></i>Section 2: Merchant Information</div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">TID (Terminal ID) <span class="text-danger">*</span></label>
                        <input type="text" name="tid" class="form-control" required placeholder="Terminal ID">
                        <div class="invalid-feedback"></div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Merchant Name <span class="text-danger">*</span></label>
                        <input type="text" name="merchant_name" class="form-control" required placeholder="Merchant / Client name">
                        <div class="invalid-feedback"></div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Contact Number <span class="text-danger">*</span></label>
                        <input type="text" name="contact_number" class="form-control" required placeholder="Phone number">
                        <div class="invalid-feedback"></div>
                    </div>
                    <div class="col-md-12">
                        <label class="form-label">Merchant Address <span class="text-danger">*</span></label>
                        <textarea name="merchant_address" class="form-control" rows="2" required placeholder="Full address"></textarea>
                        <div class="invalid-feedback"></div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Section 3: Ticket Description --}}
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-info text-white"><i class="bi bi-card-text me-2"></i>Section 3: Description</div>
            <div class="card-body">
                <textarea name="description" class="form-control" rows="4" required placeholder="Describe the issue or ticket details..."></textarea>
                <div class="invalid-feedback"></div>
            </div>
        </div>

        {{-- Section 5: Claim Section --}}
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-warning text-dark"><i class="bi bi-cash-coin me-2"></i>Section 5: Claim</div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Mileage (KM)</label>
                        <input type="number" name="mileage" id="mileage" class="form-control" step="0.01" min="0" value="0">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Mileage Rate (RM/km)</label>
                        <input type="text" id="mileage_rate_display" class="form-control" readonly value="0.00">
                        <small class="text-muted">Auto-filled from supervisor</small>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Mileage Claim (RM)</label>
                        <input type="text" id="mileage_amount_display" class="form-control fw-bold" readonly value="0.00">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Mileage Remarks</label>
                        <input type="text" name="mileage_remarks" class="form-control" placeholder="e.g. Shah Alam to merchant">
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
                        <label class="form-label fw-bold text-primary">Total Claim (RM)</label>
                        <input type="text" id="total_claim_display" class="form-control fw-bold text-primary" readonly value="0.00">
                    </div>
                </div>
            </div>
        </div>

        <div class="text-end mb-4">
            <a href="{{ route('admin.tickets.index') }}" class="btn btn-secondary me-2">Cancel</a>
            <button type="submit" class="btn btn-primary" id="submitBtn">
                <i class="bi bi-check-circle me-1"></i> Create Ticket
            </button>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
$(function() {
    // Vendor → Branches
    $('#vendor_id').on('change', function() {
        var vendorId = $(this).val();
        $('#vendor_branch_id').html('<option value="">Loading...</option>');
        if (!vendorId) { $('#vendor_branch_id').html('<option value="">Select Branch</option>'); return; }
        $.get('{{ route("admin.tickets.ajax.vendor-branches") }}', {vendor_id: vendorId}, function(data) {
            var html = '<option value="">Select Branch</option>';
            data.forEach(function(b) { html += '<option value="'+b.id+'">'+b.branch_name+'</option>'; });
            $('#vendor_branch_id').html(html);
        });
    });

    // State → Cities/Districts
    $('#state_id').on('change', function() {
        var stateId = $(this).val();
        $('#city_id').html('<option value="">Loading...</option>');
        if (!stateId) { $('#city_id').html('<option value="">Select District</option>'); return; }
        $.get('{{ route("admin.tickets.ajax.cities") }}', {state_id: stateId}, function(data) {
            var html = '<option value="">Select District</option>';
            data.forEach(function(c) { html += '<option value="'+c.id+'">'+c.name+'</option>'; });
            $('#city_id').html(html);
        });
    });

    // Supervisor → Technicians + Mileage Rate
    $('#supervisor_id').on('change', function() {
        var supId = $(this).val();
        var rate = $(this).find(':selected').data('mileage-rate') || 0;
        $('#mileage_rate_display').val(parseFloat(rate).toFixed(2));
        recalcClaim();

        // Load technicians for this supervisor
        $.get('{{ route("admin.tickets.ajax.technicians") }}', {supervisor_id: supId}, function(data) {
            var html = '<option value="">Select Technician (Optional)</option>';
            data.forEach(function(t) { html += '<option value="'+t.id+'">'+t.name+'</option>'; });
            $('#technician_id').html(html);
        });
    });

    // Job Type → Charges
    $('#job_type_id').on('change', function() {
        var jtId = $(this).val();
        $.get('{{ url("admin/tickets/ajax/charges") }}', {job_type_id: jtId}, function(data) {
            var html = '<option value="">Select Charge</option>';
            data.forEach(function(c) { html += '<option value="'+c.id+'">'+c.charge_name+' (RM '+parseFloat(c.default_price).toFixed(2)+')</option>'; });
            $('#charge_id').html(html);
        });
    });

    // Claim auto-calc
    function recalcClaim() {
        var mileage = parseFloat($('#mileage').val()) || 0;
        var rate = parseFloat($('#mileage_rate_display').val()) || 0;
        var toll = parseFloat($('#toll').val()) || 0;
        var meal = parseFloat($('#standby_meal').val()) || 0;
        var mileageAmt = mileage * rate;
        var total = mileageAmt + toll + meal;
        $('#mileage_amount_display').val(mileageAmt.toFixed(2));
        $('#total_claim_display').val(total.toFixed(2));
    }

    $('#mileage, #toll, #standby_meal').on('input change', recalcClaim);

    // Form submit via AJAX
    $('#ticketForm').on('submit', function(e) {
        e.preventDefault();
        var $btn = $('#submitBtn');
        $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Creating...');

        $.ajax({
            url: $(this).attr('action'),
            method: 'POST',
            data: $(this).serialize(),
            success: function(res) {
                if (res.success) {
                    showToast(res.message, 'success');
                    setTimeout(function() { window.location.href = res.redirect; }, 1000);
                }
            },
            error: function(xhr) {
                $btn.prop('disabled', false).html('<i class="bi bi-check-circle me-1"></i> Create Ticket');
                if (xhr.status === 422) {
                    var errors = xhr.responseJSON?.errors || {};
                    $('.is-invalid').removeClass('is-invalid');
                    Object.keys(errors).forEach(function(field) {
                        var $el = $('[name="'+field+'"]');
                        $el.addClass('is-invalid');
                        $el.siblings('.invalid-feedback').text(errors[field][0]);
                    });
                } else {
                    showToast(xhr.responseJSON?.message || 'Failed to create ticket.', 'error');
                }
            }
        });
    });
});
</script>
@endpush
