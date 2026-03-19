@extends('layouts.app')
@section('title', 'Create Ticket')

@section('content')
<div class="container-fluid">
    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-0"><i class="bi bi-plus-circle me-2 text-primary"></i>Create New Ticket</h4>
            <small class="text-muted">Fill in all required fields to create a new ticket</small>
        </div>
        <a href="{{ route('admin.tickets.index') }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left me-1"></i>Back to List</a>
    </div>

    <form id="ticketForm" novalidate>
        @csrf
        <div class="row g-4">
            {{-- ══════ LEFT COLUMN (8-col) ══════ --}}
            <div class="col-lg-8">

                {{-- Vendor --}}
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white py-2">
                        <h6 class="mb-0"><i class="bi bi-building me-2 text-primary"></i>Vendor Information</h6>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">Vendor <span class="text-danger">*</span></label>
                                <select name="vendor_id" id="vendor_id" class="form-select select2" required>
                                    <option value="">Select Vendor</option>
                                    @foreach($vendors as $v)
                                    <option value="{{ $v->id }}">{{ $v->vendor_name }}</option>
                                    @endforeach
                                </select>
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Branch <span class="text-danger">*</span></label>
                                <select name="vendor_branch_id" id="vendor_branch_id" class="form-select select2" required>
                                    <option value="">Select Branch</option>
                                </select>
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Vendor Ticket Ref No</label>
                                <input type="text" name="vendor_ticket_ref_no" class="form-control" placeholder="Vendor reference">
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Location & Merchant --}}
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white py-2">
                        <h6 class="mb-0"><i class="bi bi-geo-alt me-2 text-success"></i>Location & Merchant Details</h6>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">State <span class="text-danger">*</span></label>
                                <select name="state_id" id="state_id" class="form-select select2" required>
                                    <option value="">Select State</option>
                                    @foreach($states as $s)
                                    <option value="{{ $s->id }}">{{ $s->name }}</option>
                                    @endforeach
                                </select>
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">District / City <span class="text-danger">*</span></label>
                                <select name="city_id" id="city_id" class="form-select select2" required>
                                    <option value="">Select District</option>
                                </select>
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Contact Number <span class="text-danger">*</span></label>
                                <input type="text" name="contact_number" class="form-control" required placeholder="e.g. 012-3456789">
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Merchant Name <span class="text-danger">*</span></label>
                                <input type="text" name="merchant_name" class="form-control" required>
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Merchant Address <span class="text-danger">*</span></label>
                                <textarea name="merchant_address" class="form-control" rows="1" required></textarea>
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Job Configuration --}}
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white py-2">
                        <h6 class="mb-0"><i class="bi bi-diagram-3 me-2 text-info"></i>Job Configuration</h6>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">Job Category <span class="text-danger">*</span></label>
                                <select name="job_category_id" id="job_category_id" class="form-select" required>
                                    <option value="">Select Category</option>
                                    @foreach($jobCategories as $cat)
                                    <option value="{{ $cat->id }}" data-slug="{{ $cat->slug }}">{{ $cat->category_name }}</option>
                                    @endforeach
                                </select>
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Job Type <span class="text-danger">*</span></label>
                                <select name="job_type_id" id="job_type_id" class="form-select" required>
                                    <option value="">Select Job Type</option>
                                    @foreach($jobTypes as $jt)
                                    <option value="{{ $jt->id }}" data-slug="{{ $jt->slug }}">{{ $jt->job_title }}</option>
                                    @endforeach
                                </select>
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Price (RM)</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light">RM</span>
                                    <input type="text" name="price" id="price_display" class="form-control bg-light fw-bold" readonly value="0.00">
                                </div>
                                <div id="priceHint"><small class="text-muted">Auto-filled from pricing</small></div>
                            </div>
                        </div>

                        {{-- Dynamic Device Fields --}}
                        <div class="row g-3 mt-1" id="deviceFieldsRow">
                            <div class="col-md-4 device-field" id="terminalIdGroup" style="display:none;">
                                <label class="form-label">Terminal ID <span class="text-danger terminal-required-star" style="display:none;">*</span></label>
                                <input type="text" name="terminal_id" id="terminal_id" class="form-control" placeholder="Enter Terminal ID">
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-md-4 device-field" id="routerIdGroup" style="display:none;">
                                <label class="form-label">Router ID <span class="text-danger router-required-star" style="display:none;">*</span></label>
                                <input type="text" name="router_id" id="router_id" class="form-control" placeholder="Enter Router ID">
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-md-4 device-field" id="oldTerminalIdGroup" style="display:none;">
                                <label class="form-label">Old Terminal ID <span class="text-danger">*</span></label>
                                <input type="text" name="old_terminal_id" id="old_terminal_id" class="form-control" placeholder="For replacement jobs">
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Description & Schedule --}}
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white py-2">
                        <h6 class="mb-0"><i class="bi bi-card-text me-2 text-warning"></i>Description & Schedule</h6>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label">Description <span class="text-danger">*</span></label>
                                <textarea name="description" class="form-control" rows="3" required placeholder="Describe the issue or task in detail..."></textarea>
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Expected Start Date</label>
                                <input type="date" name="expected_start_date" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Expected End Date</label>
                                <input type="date" name="expected_end_date" class="form-control">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ══════ RIGHT COLUMN (4-col) ══════ --}}
            <div class="col-lg-4">

                {{-- Assignment --}}
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white py-2">
                        <h6 class="mb-0"><i class="bi bi-people me-2 text-danger"></i>Assignment</h6>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Priority <span class="text-danger">*</span></label>
                            <select name="priority" class="form-select" required>
                                @foreach(\App\Models\Ticket::getPriorities() as $key => $label)
                                <option value="{{ $key }}" {{ $key === 'normal' ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Supervisor <span class="text-danger">*</span></label>
                            <select name="supervisor_id" id="supervisor_id" class="form-select select2" required>
                                <option value="">Select Supervisor</option>
                                @foreach($supervisors as $sv)
                                <option value="{{ $sv->id }}" data-type="{{ $sv->supervisor_type }}">{{ $sv->name }} ({{ ucfirst($sv->supervisor_type ?? 'N/A') }})</option>
                                @endforeach
                            </select>
                            <div class="invalid-feedback"></div>
                        </div>
                        <div id="supervisorTypeInfo" style="display:none;" class="mb-3">
                            <div id="supervisorTypeBadge"></div>
                        </div>
                        <div class="mb-0" id="technicianGroup" style="display:none;">
                            <label class="form-label">Assign Technician</label>
                            <select name="technician_id" id="technician_id" class="form-select select2">
                                <option value="">Assign Later</option>
                            </select>
                        </div>
                    </div>
                </div>

                {{-- Claims (external supervisor only) --}}
                <div class="card shadow-sm mb-4" id="claimSection" style="display:none;">
                    <div class="card-header bg-white py-2">
                        <h6 class="mb-0"><i class="bi bi-receipt me-2 text-success"></i>Claim Information</h6>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-6">
                                <label class="form-label">Mileage (KM)</label>
                                <input type="number" name="mileage" id="mileage" class="form-control form-control-sm" step="0.01" min="0" value="0">
                            </div>
                            <div class="col-6">
                                <label class="form-label">Rate (RM/KM)</label>
                                <input type="text" id="mileage_rate_display" class="form-control form-control-sm bg-light" readonly value="0.00">
                            </div>
                            <div class="col-6">
                                <label class="form-label">Amount (RM)</label>
                                <input type="text" id="mileage_amount_display" class="form-control form-control-sm bg-light" readonly value="0.00">
                            </div>
                            <div class="col-6">
                                <label class="form-label">Toll (RM)</label>
                                <input type="number" name="toll" class="form-control form-control-sm" step="0.01" min="0" value="0">
                            </div>
                            <div class="col-6">
                                <label class="form-label">Standby/Meal</label>
                                <input type="number" name="standby_meal" class="form-control form-control-sm" step="0.01" min="0" value="0">
                            </div>
                            <div class="col-6">
                                <label class="form-label">Remarks</label>
                                <input type="text" name="mileage_remarks" class="form-control form-control-sm">
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Submit --}}
                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary" id="btnSubmit">
                        <i class="bi bi-check-circle me-2"></i>Create Ticket
                    </button>
                    <a href="{{ route('admin.tickets.index') }}" class="btn btn-outline-secondary">Cancel</a>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
$(function() {
    const baseUrl = '/admin/tickets';
    let currentMileageRate = 0;

    $('.select2').select2({ theme: 'bootstrap-5', width: '100%' });

    // ── Vendor → Branch ──
    $('#vendor_id').on('change', function() {
        let vid = $(this).val();
        $('#vendor_branch_id').html('<option value="">Loading...</option>');
        if (!vid) { $('#vendor_branch_id').html('<option value="">Select Branch</option>'); return; }
        $.get(baseUrl + '/ajax/vendor-branches', { vendor_id: vid }, function(data) {
            let o = '<option value="">Select Branch</option>';
            data.forEach(b => o += `<option value="${b.id}" data-state="${b.state_id}" data-city="${b.city_id}">${b.branch_name}</option>`);
            $('#vendor_branch_id').html(o).trigger('change.select2');
        });
    });

    // Branch → auto-fill state/city
    $('#vendor_branch_id').on('change', function() {
        let stateId = $(this).find(':selected').data('state'), cityId = $(this).find(':selected').data('city');
        if (stateId) {
            $('#state_id').val(stateId).trigger('change.select2');
            setTimeout(() => { if (cityId) $('#city_id').val(cityId).trigger('change.select2'); }, 500);
        }
    });

    // ── State → City + Supervisor filter (state only) ──
    $('#state_id').on('change', function() {
        let sid = $(this).val();
        if (!sid) { $('#city_id').html('<option value="">Select District</option>'); return; }
        $.get(baseUrl + '/ajax/cities', { state_id: sid }, function(data) {
            let o = '<option value="">Select District</option>';
            data.forEach(c => o += `<option value="${c.id}">${c.name}</option>`);
            $('#city_id').html(o).trigger('change.select2');
        });
        // Load supervisors by state only
        loadSupervisors(sid);
    });

    function loadSupervisors(stateId) {
        if (!stateId) return;
        $.get(baseUrl + '/ajax/supervisors', { state_id: stateId }, function(data) {
            let o = '<option value="">Select Supervisor</option>';
            data.forEach(s => {
                let t = s.supervisor_type ? ` (${s.supervisor_type.charAt(0).toUpperCase() + s.supervisor_type.slice(1)})` : '';
                o += `<option value="${s.id}" data-type="${s.supervisor_type}">${s.name}${t}</option>`;
            });
            $('#supervisor_id').html(o).trigger('change.select2');
        });
    }

    // ── Supervisor → type + technician + mileage + price ──
    $('#supervisor_id').on('change', function() {
        let svId = $(this).val(), svType = $(this).find(':selected').data('type');
        if (!svId) { $('#technicianGroup, #claimSection, #supervisorTypeInfo').hide(); return; }

        if (svType === 'internal') {
            $('#supervisorTypeBadge').html('<span class="badge bg-success"><i class="bi bi-building me-1"></i>Internal — assign technicians, no claims</span>');
            $('#technicianGroup').show(); $('#claimSection').hide();
        } else {
            $('#supervisorTypeBadge').html('<span class="badge bg-warning text-dark"><i class="bi bi-person-badge me-1"></i>External — direct assignment, claims applicable</span>');
            $('#technicianGroup').hide(); $('#technician_id').val('').trigger('change.select2');
            $('#claimSection').show();
        }
        $('#supervisorTypeInfo').show();

        if (svType === 'internal') {
            $.get(baseUrl + '/ajax/technicians', { supervisor_id: svId }, function(data) {
                let o = '<option value="">Assign Later</option>';
                data.forEach(t => o += `<option value="${t.id}">${t.name}</option>`);
                $('#technician_id').html(o).trigger('change.select2');
            });
        }

        $.get(baseUrl + '/ajax/supervisor-mileage-rate', { supervisor_id: svId }, function(data) {
            currentMileageRate = parseFloat(data.mileage_rate) || 0;
            $('#mileage_rate_display').val(currentMileageRate.toFixed(2));
            calcMileageAmount();
        });
        refreshPrice();
    });

    // ── Job Category → device fields + price ──
    $('#job_category_id').on('change', function() {
        toggleDeviceFields($(this).find(':selected').data('slug'));
        refreshPrice();
    });

    // ── Job Type → replacement check + price ──
    $('#job_type_id').on('change', function() {
        let slug = $(this).find(':selected').data('slug') || '';
        if (slug.includes('replacement')) { $('#oldTerminalIdGroup').show(); } else { $('#oldTerminalIdGroup').hide(); $('#old_terminal_id').val(''); }
        refreshPrice();
    });

    function toggleDeviceFields(slug) {
        $('.device-field').hide(); $('.terminal-required-star, .router-required-star').hide();
        $('#terminal_id, #router_id, #old_terminal_id').val('').removeAttr('required');
        switch(slug) {
            case 'terminal':
                $('#terminalIdGroup').show(); $('.terminal-required-star').show(); $('#terminal_id').attr('required', true); break;
            case 'router':
                $('#routerIdGroup').show(); $('.router-required-star').show(); $('#router_id').attr('required', true); break;
            case 'project':
                $('#terminalIdGroup, #routerIdGroup').show(); break;
        }
    }

    // ── Price fetch ──
    function refreshPrice() {
        let svId = $('#supervisor_id').val(), catId = $('#job_category_id').val(), typeId = $('#job_type_id').val();
        if (!svId || !catId || !typeId) {
            $('#price_display').val('0.00').removeClass('text-success');
            $('#priceHint').html('<small class="text-muted">Select supervisor, category & type</small>');
            return;
        }
        $('#priceHint').html('<small class="text-info"><i class="bi bi-hourglass-split me-1"></i>Fetching...</small>');
        $.ajax({
            url: baseUrl + '/ajax/price',
            data: { supervisor_id: svId, job_category_id: catId, job_type_id: typeId },
            success: function(data) {
                let p = parseFloat(data.price || 0);
                $('#price_display').val(p.toFixed(2));
                if (p > 0) {
                    $('#price_display').addClass('text-success');
                    $('#priceHint').html('<small class="text-success"><i class="bi bi-check-circle me-1"></i>Price loaded</small>');
                } else {
                    $('#price_display').removeClass('text-success');
                    $('#priceHint').html('<small class="text-warning"><i class="bi bi-exclamation-triangle me-1"></i>No pricing configured</small>');
                }
            },
            error: function() {
                $('#priceHint').html('<small class="text-danger"><i class="bi bi-x-circle me-1"></i>Failed to fetch price</small>');
            }
        });
    }

    // ── Mileage calc ──
    $('#mileage').on('input', calcMileageAmount);
    function calcMileageAmount() {
        let km = parseFloat($('#mileage').val()) || 0;
        $('#mileage_amount_display').val((km * currentMileageRate).toFixed(2));
    }

    // ── Submit ──
    $('#ticketForm').on('submit', function(e) {
        e.preventDefault();
        let btn = $('#btnSubmit');
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>Creating...');

        let fd = new FormData(this);
        fd.set('price', $('#price_display').val());

        $.ajax({
            url: '{{ route("admin.tickets.store") }}',
            method: 'POST', data: fd, processData: false, contentType: false,
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
            success: function(res) {
                if (res.success) { showToast(res.message, 'success'); if (res.redirect) window.location.href = res.redirect; }
                else showToast(res.message || 'Error', 'error');
            },
            error: function(xhr) {
                if (xhr.status === 422) {
                    let errors = xhr.responseJSON?.errors || {};
                    $('.is-invalid').removeClass('is-invalid');
                    let firstErrorEl = null;
                    Object.keys(errors).forEach(field => {
                        let el = $('[name="' + field + '"]');
                        el.addClass('is-invalid').siblings('.invalid-feedback').text(errors[field][0]);
                        if (!firstErrorEl) firstErrorEl = el;
                    });
                    if (firstErrorEl) $('html, body').animate({ scrollTop: firstErrorEl.offset().top - 100 }, 300);
                    showToast('Please fix the validation errors.', 'error');
                } else showToast(xhr.responseJSON?.message || 'Server error.', 'error');
            },
            complete: () => btn.prop('disabled', false).html('<i class="bi bi-check-circle me-2"></i>Create Ticket')
        });
    });
});
</script>
@endpush
