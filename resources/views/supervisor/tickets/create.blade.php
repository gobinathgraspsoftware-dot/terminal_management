@extends('layouts.app')
@section('title', 'Create Ticket')

@section('content')
@php
    $user = auth()->user();
    $isInternal = $user->isInternalSupervisor();
    $isExternal = $user->isExternalSupervisor();
@endphp

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="mb-0"><i class="bi bi-ticket-detailed me-2"></i>Create New Ticket</h4>
        <a href="{{ route('supervisor.tickets.index') }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left me-1"></i>Back</a>
    </div>

    <form id="ticketForm" novalidate>
        @csrf
        <div class="row g-4">
            <div class="col-lg-8">
                {{-- Vendor --}}
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white"><h6 class="mb-0"><i class="bi bi-building me-2"></i>Vendor Information</h6></div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6"><label class="form-label">Vendor <span class="text-danger">*</span></label>
                                <select name="vendor_id" id="vendor_id" class="form-select select2" required><option value="">Select Vendor</option>@foreach($vendors as $v)<option value="{{ $v->id }}">{{ $v->vendor_name }}</option>@endforeach</select></div>
                            <div class="col-md-6"><label class="form-label">Branch <span class="text-danger">*</span></label>
                                <select name="vendor_branch_id" id="vendor_branch_id" class="form-select select2" required><option value="">Select Branch</option></select></div>
                            <div class="col-md-6"><label class="form-label">Vendor Ref</label><input type="text" name="vendor_ticket_ref_no" class="form-control"></div>
                        </div>
                    </div>
                </div>

                {{-- Location --}}
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white"><h6 class="mb-0"><i class="bi bi-geo-alt me-2"></i>Location & Merchant</h6></div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-4"><label class="form-label">State <span class="text-danger">*</span></label>
                                <select name="state_id" id="state_id" class="form-select select2" required><option value="">Select</option>@foreach($states as $s)<option value="{{ $s->id }}">{{ $s->name }}</option>@endforeach</select></div>
                            <div class="col-md-4"><label class="form-label">District <span class="text-danger">*</span></label>
                                <select name="city_id" id="city_id" class="form-select select2" required><option value="">Select</option></select></div>
                            <div class="col-md-4"><label class="form-label">TID <span class="text-danger">*</span></label><input type="text" name="tid" class="form-control" required></div>
                            <div class="col-md-6"><label class="form-label">Merchant Name <span class="text-danger">*</span></label><input type="text" name="merchant_name" class="form-control" required></div>
                            <div class="col-md-6"><label class="form-label">Contact <span class="text-danger">*</span></label><input type="text" name="contact_number" class="form-control" required></div>
                            <div class="col-12"><label class="form-label">Address <span class="text-danger">*</span></label><textarea name="merchant_address" class="form-control" rows="2" required></textarea></div>
                        </div>
                    </div>
                </div>

                {{-- Job Category & Type — INDEPENDENT --}}
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white"><h6 class="mb-0"><i class="bi bi-diagram-3 me-2"></i>Job Category & Type</h6></div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-4"><label class="form-label">Job Category <span class="text-danger">*</span></label>
                                <select name="job_category_id" id="job_category_id" class="form-select" required>
                                    <option value="">Select Category</option>
                                    @foreach($jobCategories as $cat)<option value="{{ $cat->id }}" data-slug="{{ $cat->slug }}">{{ $cat->category_name }}</option>@endforeach
                                </select>
                                <small class="text-muted">Controls device ID fields</small>
                            </div>
                            <div class="col-md-4"><label class="form-label">Job Type <span class="text-danger">*</span></label>
                                <select name="job_type_id" id="job_type_id" class="form-select" required>
                                    <option value="">Select Job Type</option>
                                    @foreach($jobTypes as $jt)<option value="{{ $jt->id }}" data-slug="{{ $jt->slug }}">{{ $jt->job_title }}</option>@endforeach
                                </select>
                                <small class="text-muted">Shared across all categories</small>
                            </div>
                            <div class="col-md-4"><label class="form-label">Price (RM)</label>
                                <input type="text" name="price" id="price_display" class="form-control bg-light" readonly placeholder="0.00">
                                <div id="priceHint"><small class="text-muted">From pricing (Category + Type)</small></div>
                            </div>
                            <div class="col-md-6 device-field" id="terminalIdGroup" style="display:none;">
                                <label class="form-label">Terminal ID <span class="text-danger terminal-required-star" style="display:none;">*</span></label>
                                <input type="text" name="terminal_id" id="terminal_id" class="form-control"></div>
                            <div class="col-md-6 device-field" id="routerIdGroup" style="display:none;">
                                <label class="form-label">Router ID <span class="text-danger router-required-star" style="display:none;">*</span></label>
                                <input type="text" name="router_id" id="router_id" class="form-control"></div>
                            <div class="col-md-6 device-field" id="oldTerminalIdGroup" style="display:none;">
                                <label class="form-label">Old Terminal ID</label>
                                <input type="text" name="old_terminal_id" class="form-control"></div>
                        </div>
                    </div>
                </div>

                {{-- Description --}}
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white"><h6 class="mb-0"><i class="bi bi-card-text me-2"></i>Details & Schedule</h6></div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-12"><label class="form-label">Description <span class="text-danger">*</span></label><textarea name="description" class="form-control" rows="4" required></textarea></div>
                            <div class="col-md-4"><label class="form-label">Expected Start Date</label><input type="date" name="expected_start_date" class="form-control"></div>
                            <div class="col-md-4"><label class="form-label">Expected End Date</label><input type="date" name="expected_end_date" class="form-control"></div>
                            <div class="col-md-4"><label class="form-label">SLA Hours</label><input type="number" name="sla_hours" class="form-control" value="24" min="1" max="720"></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white"><h6 class="mb-0"><i class="bi bi-people me-2"></i>Assignment</h6></div>
                    <div class="card-body">
                        <div class="mb-3"><label class="form-label">Supervisor</label>
                            <input type="text" class="form-control bg-light" readonly value="{{ $user->name }} ({{ ucfirst($user->supervisor_type) }})">
                            <input type="hidden" name="supervisor_id" value="{{ $user->id }}">
                        </div>
                        <div class="alert alert-{{ $isInternal ? 'success' : 'warning' }} py-2">
                            <small><i class="bi bi-info-circle me-1"></i>{{ $isInternal ? 'Internal supervisor — you can assign technicians' : 'External supervisor — tickets assigned to you directly' }}</small>
                        </div>
                        <div class="mb-3"><label class="form-label">Priority <span class="text-danger">*</span></label>
                            <select name="priority" class="form-select" required>@foreach(\App\Models\Ticket::getPriorities() as $key => $label)<option value="{{ $key }}" {{ $key === 'normal' ? 'selected' : '' }}>{{ $label }}</option>@endforeach</select></div>
                        @if($isInternal)
                        <div class="mb-3"><label class="form-label">Assign Technician</label>
                            <select name="technician_id" class="form-select select2"><option value="">Assign Later</option>@foreach($technicians as $t)<option value="{{ $t->id }}">{{ $t->name }}</option>@endforeach</select></div>
                        @endif
                    </div>
                </div>

                @if($isExternal)
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white"><h6 class="mb-0"><i class="bi bi-receipt me-2"></i>Claims</h6></div>
                    <div class="card-body">
                        <div class="mb-2"><label class="form-label">Mileage (KM)</label><input type="number" name="mileage" id="mileage" class="form-control" step="0.01" min="0" value="0"></div>
                        <div class="mb-2"><label class="form-label">Mileage Rate</label><input type="text" id="mileage_rate_display" class="form-control bg-light" readonly value="{{ number_format($user->mileage_rate ?? 0, 2) }}"></div>
                        <div class="mb-2"><label class="form-label">Mileage Remarks</label><input type="text" name="mileage_remarks" class="form-control"></div>
                        <div class="mb-2"><label class="form-label">Toll (RM)</label><input type="number" name="toll" class="form-control" step="0.01" min="0" value="0"></div>
                        <div class="mb-2"><label class="form-label">Standby/Meal (RM)</label><input type="number" name="standby_meal" class="form-control" step="0.01" min="0" value="0"></div>
                    </div>
                </div>
                @endif

                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary" id="btnSubmit"><i class="bi bi-check-lg me-1"></i>Create Ticket</button>
                    <a href="{{ route('supervisor.tickets.index') }}" class="btn btn-outline-secondary">Cancel</a>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
$(function() {
    const baseUrl = '/supervisor/tickets';
    $('.select2').select2({ theme: 'bootstrap-5', width: '100%' });

    // Vendor → Branch
    $('#vendor_id').on('change', function() {
        $.get(baseUrl + '/ajax/vendor-branches', { vendor_id: $(this).val() }, function(data) {
            let opts = '<option value="">Select Branch</option>';
            data.forEach(b => opts += `<option value="${b.id}" data-state="${b.state_id}" data-city="${b.city_id}">${b.branch_name}</option>`);
            $('#vendor_branch_id').html(opts).trigger('change.select2');
        });
    });
    $('#vendor_branch_id').on('change', function() {
        let stateId = $(this).find(':selected').data('state');
        if (stateId) $('#state_id').val(stateId).trigger('change.select2');
    });

    // State → City
    $('#state_id').on('change', function() {
        $.get(baseUrl + '/ajax/cities', { state_id: $(this).val() }, function(data) {
            let opts = '<option value="">Select</option>';
            data.forEach(c => opts += `<option value="${c.id}">${c.name}</option>`);
            $('#city_id').html(opts).trigger('change.select2');
        });
    });

    // Job Category → ONLY device fields + price
    $('#job_category_id').on('change', function() {
        toggleDeviceFields($(this).find(':selected').data('slug'));
        refreshPrice();
    });

    // Job Type → ONLY replacement check + price
    $('#job_type_id').on('change', function() {
        let slug = $(this).find(':selected').data('slug') || '';
        $('#oldTerminalIdGroup').toggle(slug.includes('replacement'));
        refreshPrice();
    });

    function toggleDeviceFields(slug) {
        $('.device-field').hide();
        $('.terminal-required-star, .router-required-star').hide();
        $('#terminal_id, #router_id').removeAttr('required');
        if (slug === 'terminal') { $('#terminalIdGroup').show(); $('.terminal-required-star').show(); $('#terminal_id').attr('required', true); }
        else if (slug === 'router') { $('#routerIdGroup').show(); $('.router-required-star').show(); $('#router_id').attr('required', true); }
        else if (slug === 'project') { $('#terminalIdGroup, #routerIdGroup').show(); }
    }

    function refreshPrice() {
        let catId = $('#job_category_id').val(), typeId = $('#job_type_id').val();
        if (!catId || !typeId) {
            $('#price_display').val('0.00').removeClass('text-success fw-bold');
            let missing = [];
            if (!catId) missing.push('Category');
            if (!typeId) missing.push('Type');
            $('#priceHint').html('<small class="text-muted">Select ' + missing.join(' & ') + ' to get price</small>');
            return;
        }
        $('#priceHint').html('<small class="text-info"><i class="bi bi-hourglass-split me-1"></i>Fetching...</small>');
        $.ajax({
            url: baseUrl + '/ajax/price',
            data: { job_category_id: catId, job_type_id: typeId },
            success: function(data) {
                let price = parseFloat(data.price || 0);
                $('#price_display').val(price.toFixed(2));
                if (price > 0) {
                    $('#price_display').addClass('text-success fw-bold').removeClass('text-danger');
                    $('#priceHint').html('<small class="text-success"><i class="bi bi-check-circle me-1"></i>Price loaded</small>');
                } else {
                    $('#price_display').removeClass('text-success fw-bold');
                    $('#priceHint').html('<small class="text-warning"><i class="bi bi-exclamation-triangle me-1"></i>No pricing configured</small>');
                }
            },
            error: function(xhr) {
                console.error('Price fetch failed:', xhr.status);
                $('#priceHint').html('<small class="text-danger"><i class="bi bi-x-circle me-1"></i>Price fetch failed</small>');
            }
        });
    }

    // Form submit
    $('#ticketForm').on('submit', function(e) {
        e.preventDefault();
        let btn = $('#btnSubmit');
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>Saving...');
        let fd = new FormData(this);
        fd.set('price', $('#price_display').val());
        $.ajax({
            url: '{{ route("supervisor.tickets.store") }}',
            method: 'POST', data: fd, processData: false, contentType: false,
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
            success: function(res) { if (res.success) { showToast(res.message, 'success'); window.location.href = res.redirect; } else showToast(res.message, 'error'); },
            error: function(xhr) {
                if (xhr.status === 422) {
                    let errors = xhr.responseJSON?.errors || {};
                    $('.is-invalid').removeClass('is-invalid');
                    Object.keys(errors).forEach(f => $('[name="'+f+'"]').addClass('is-invalid').siblings('.invalid-feedback').text(errors[f][0]));
                } else showToast(xhr.responseJSON?.message || 'Server error.', 'error');
            },
            complete: () => btn.prop('disabled', false).html('<i class="bi bi-check-lg me-1"></i>Create Ticket')
        });
    });
});
</script>
@endpush
