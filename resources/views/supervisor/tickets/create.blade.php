@extends('layouts.app')
@section('title', 'Create Ticket')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="mb-0"><i class="bi bi-plus-circle me-2"></i>Create Ticket</h4>
        <a href="{{ route('supervisor.tickets.index') }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left me-1"></i> Back</a>
    </div>

    <form id="ticketForm" method="POST" action="{{ route('supervisor.tickets.store') }}">
        @csrf
        {{-- Hidden: supervisor is current user --}}
        <input type="hidden" name="supervisor_id" value="{{ auth()->id() }}">

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
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Vendor Ticket Ref No <span class="text-danger">*</span></label>
                        <input type="text" name="vendor_ticket_ref_no" class="form-control" required>
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
                            @foreach($states as $state)
                                <option value="{{ $state->id }}">{{ $state->name }}</option>
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
                            @foreach($charges as $c)
                                <option value="{{ $c->id }}">{{ $c->charge_name }} (RM {{ number_format($c->default_price, 2) }})</option>
                            @endforeach
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
                        <label class="form-label">SLA (Hours)</label>
                        <input type="number" name="sla_hours" class="form-control" value="24" min="1" max="720">
                        <small class="text-muted">Default: 24 hours</small>
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
                    <div class="col-md-12">
                        <label class="form-label">Merchant Address <span class="text-danger">*</span></label>
                        <textarea name="merchant_address" class="form-control" rows="2" required></textarea>
                    </div>
                </div>
            </div>
        </div>

        {{-- Section 3: Description --}}
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-info text-white"><i class="bi bi-card-text me-2"></i>Section 3: Description</div>
            <div class="card-body">
                <textarea name="description" class="form-control" rows="4" required placeholder="Describe the issue..."></textarea>
            </div>
        </div>

        {{-- Section 5: Claim --}}
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
                        <input type="text" id="mileage_rate_display" class="form-control" readonly value="{{ number_format(auth()->user()->mileage_rate ?? 0, 2) }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Mileage Claim (RM)</label>
                        <input type="text" id="mileage_amount_display" class="form-control fw-bold" readonly value="0.00">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Mileage Remarks</label>
                        <input type="text" name="mileage_remarks" class="form-control" placeholder="Route details">
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
            <a href="{{ route('supervisor.tickets.index') }}" class="btn btn-secondary me-2">Cancel</a>
            <button type="submit" class="btn btn-primary" id="submitBtn"><i class="bi bi-check-circle me-1"></i> Create Ticket</button>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
$(function() {
    $('#vendor_id').on('change', function() {
        $.get('{{ route("supervisor.tickets.ajax.vendor-branches") }}', {vendor_id: $(this).val()}, function(data) {
            var h = '<option value="">Select Branch</option>';
            data.forEach(function(b) { h += '<option value="'+b.id+'">'+b.branch_name+'</option>'; });
            $('#vendor_branch_id').html(h);
        });
    });

    $('#state_id').on('change', function() {
        $.get('{{ route("supervisor.tickets.ajax.cities") }}', {state_id: $(this).val()}, function(data) {
            var h = '<option value="">Select District</option>';
            data.forEach(function(c) { h += '<option value="'+c.id+'">'+c.name+'</option>'; });
            $('#city_id').html(h);
        });
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
        $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Creating...');
        $.ajax({
            url: $(this).attr('action'), method: 'POST', data: $(this).serialize(),
            success: function(res) { if (res.success) { showToast(res.message, 'success'); setTimeout(function(){ window.location.href = res.redirect; }, 1000); } },
            error: function(xhr) {
                $btn.prop('disabled', false).html('<i class="bi bi-check-circle me-1"></i> Create Ticket');
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
