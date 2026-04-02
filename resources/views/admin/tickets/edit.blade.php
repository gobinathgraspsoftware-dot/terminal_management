@extends('layouts.app')
@section('title', 'Edit Ticket - ' . $ticket->ticket_no)

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-0"><i class="bi bi-pencil-square me-2 text-warning"></i>Edit Ticket: <span class="text-primary">{{ $ticket->ticket_no }}</span></h4>
            <small class="text-muted">Update ticket details below</small>
        </div>
        <a href="{{ route('admin.tickets.show', $ticket->id) }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left me-1"></i>Back to Ticket</a>
    </div>

    <form id="ticketForm" novalidate>
        @csrf @method('PUT')
        <div class="row g-4">
            <div class="col-lg-12">
                <div class="card shadow-sm">
                    <div class="card-body p-4">

                        {{-- Section 1: Vendor --}}
                        <div class="d-flex align-items-center mb-3">
                            <span class="badge bg-primary rounded-circle me-2" style="width:28px;height:28px;display:inline-flex;align-items:center;justify-content:center;">1</span>
                            <h6 class="mb-0 fw-bold">Vendor Information</h6>
                        </div>
                        <div class="row g-3 mb-4">
                            <div class="col-md-4">
                                <label class="form-label">Vendor <span class="text-danger">*</span></label>
                                <select name="vendor_id" id="vendor_id" class="form-select select2" required><option value="">Select Vendor</option>@foreach($vendors as $v)<option value="{{ $v->id }}" {{ $ticket->vendor_id == $v->id ? 'selected' : '' }}>{{ $v->vendor_name }}</option>@endforeach</select>
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Branch <span class="text-danger">*</span></label>
                                <select name="vendor_branch_id" id="vendor_branch_id" class="form-select select2" required><option value="">Select Branch</option>@foreach($branches as $b)<option value="{{ $b->id }}" {{ $ticket->vendor_branch_id == $b->id ? 'selected' : '' }}>{{ $b->branch_name }}</option>@endforeach</select>
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Vendor Ticket Ref No</label>
                                <input type="text" name="vendor_ticket_ref_no" class="form-control" value="{{ $ticket->vendor_ticket_ref_no }}">
                            </div>
                        </div>

                        <hr class="my-3">

                        {{-- Section 2: Location & Merchant --}}
                        <div class="d-flex align-items-center mb-3">
                            <span class="badge bg-success rounded-circle me-2" style="width:28px;height:28px;display:inline-flex;align-items:center;justify-content:center;">2</span>
                            <h6 class="mb-0 fw-bold">Location & Merchant Details</h6>
                        </div>
                        <div class="row g-3 mb-4">
                            <div class="col-md-3">
                                <label class="form-label">State <span class="text-danger">*</span></label>
                                <select name="state_id" id="state_id" class="form-select select2" required><option value="">Select State</option>@foreach($states as $s)<option value="{{ $s->id }}" {{ $ticket->state_id == $s->id ? 'selected' : '' }}>{{ $s->name }}</option>@endforeach</select>
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">District / City <span class="text-danger">*</span></label>
                                <select name="city_id" id="city_id" class="form-select select2" required><option value="">Select District</option>@foreach($cities as $c)<option value="{{ $c->id }}" data-postcode="{{ $c->postcode ?? '' }}" {{ $ticket->city_id == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>@endforeach</select>
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Postcode</label>
                                <input type="text" id="postcode" class="form-control bg-light" readonly value="{{ $ticket->city?->postcode ?? '-' }}">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Country</label>
                                <input type="text" class="form-control bg-light" readonly value="Malaysia">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Merchant Name <span class="text-danger">*</span></label>
                                <input type="text" name="merchant_name" class="form-control" required value="{{ $ticket->merchant_name }}"><div class="invalid-feedback"></div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Contact Number <span class="text-danger">*</span></label>
                                <input type="text" name="contact_number" class="form-control" required value="{{ $ticket->contact_number }}"><div class="invalid-feedback"></div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Merchant Address <span class="text-danger">*</span></label>
                                <textarea name="merchant_address" class="form-control" rows="1" required>{{ $ticket->merchant_address }}</textarea><div class="invalid-feedback"></div>
                            </div>
                        </div>

                        <hr class="my-3">

                        {{-- Section 3: Job Config & Assignment --}}
                        <div class="d-flex align-items-center mb-3">
                            <span class="badge bg-info rounded-circle me-2" style="width:28px;height:28px;display:inline-flex;align-items:center;justify-content:center;">3</span>
                            <h6 class="mb-0 fw-bold">Job Configuration & Assignment</h6>
                        </div>
                        <div class="row g-3 mb-3">
                            <div class="col-md-4">
                                <label class="form-label">Job Category <span class="text-danger">*</span></label>
                                <select name="job_category_id" id="job_category_id" class="form-select" required><option value="">Select</option>@foreach($jobCategories as $cat)<option value="{{ $cat->id }}" data-slug="{{ $cat->slug }}" {{ $ticket->job_category_id == $cat->id ? 'selected' : '' }}>{{ $cat->category_name }}</option>@endforeach</select>
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Job Type <span class="text-danger">*</span></label>
                                <select name="job_type_id" id="job_type_id" class="form-select" required><option value="">Select</option>@foreach($jobTypes as $jt)<option value="{{ $jt->id }}" data-slug="{{ $jt->slug }}" {{ $ticket->job_type_id == $jt->id ? 'selected' : '' }}>{{ $jt->job_title }}</option>@endforeach</select>
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Priority <span class="text-danger">*</span></label>
                                <select name="priority" class="form-select" required>@foreach(\App\Models\Ticket::getPriorities() as $key => $label)<option value="{{ $key }}" {{ $ticket->priority === $key ? 'selected' : '' }}>{{ $label }}</option>@endforeach</select>
                            </div>
                        </div>

                        {{-- Dynamic Device Fields --}}
                        <div class="row g-3 mb-3">
                            {{-- Terminal ID (text) — for terminal & project --}}
                            <div class="col-md-4 device-field" id="terminalIdGroup" style="display:none;">
                                <label class="form-label">Terminal ID <span class="text-danger terminal-required-star" style="display:none;">*</span></label>
                                <input type="text" name="terminal_id" id="terminal_id" class="form-control" value="{{ $ticket->terminal_id }}"><div class="invalid-feedback"></div>
                            </div>

                            {{-- Router ID SELECT from inventory — ONLY for router category --}}
                            <div class="col-md-4 device-field" id="routerIdSelectGroup" style="display:none;">
                                <label class="form-label">Router ID <span class="text-danger router-required-star" style="display:none;">*</span></label>
                                <select name="router_id" id="router_id_select" class="form-select select2" style="width:100%;" disabled>
                                    <option value="">Select Router</option>
                                    @if($ticket->router_id && $ticket->jobCategory?->slug === 'router')
                                    <option value="{{ $ticket->router_id }}" selected>{{ $ticket->router_id }}</option>
                                    @endif
                                </select>
                                <div class="invalid-feedback"></div>
                            </div>

                            {{-- Router ID TEXT input — ONLY for project category (NOT inventory-linked) --}}
                            <div class="col-md-4 device-field" id="routerIdTextGroup" style="display:none;">
                                <label class="form-label">Router ID</label>
                                <input type="text" name="router_id" id="router_id_text" class="form-control" value="{{ $ticket->jobCategory?->slug === 'project' ? $ticket->router_id : '' }}" disabled><div class="invalid-feedback"></div>
                            </div>

                            {{-- Old Router/Terminal ID — for router or terminal category + replacement job type --}}
                            <div class="col-md-4 device-field" id="oldTerminalIdGroup" style="display:none;">
                                <label class="form-label" id="oldIdLabel">Old Router ID</label>
                                <input type="text" name="old_terminal_id" class="form-control" value="{{ $ticket->old_terminal_id }}"><div class="invalid-feedback"></div>
                            </div>
                        </div>

                        {{-- Serial Number — Default optional field (always visible) --}}
                        <div class="row g-3 mb-3">
                            <div class="col-md-4">
                                <label class="form-label">Serial Number</label>
                                <input type="text" name="serial_number" id="serial_number" class="form-control" value="{{ old('serial_number', $ticket->serial_number) }}" placeholder="Enter serial number (optional)" maxlength="100">
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>

                        {{-- Accessories Fields --}}
                        <div class="row g-3 mb-3" id="accessoriesSection" style="display:none;">
                            <div class="col-md-4">
                                <label class="form-label">Accessory Type <span class="text-danger">*</span></label>
                                <select name="accessory_type_selected" id="accessory_type_selected" class="form-select">
                                    <option value="">Select Accessory Type</option>
                                    <option value="sim_card" {{ $ticket->accessory_type_selected === 'sim_card' ? 'selected' : '' }}>SIM Card</option>
                                    <option value="antenna" {{ $ticket->accessory_type_selected === 'antenna' ? 'selected' : '' }}>Antenna</option>
                                </select>
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Accessory Item <span class="text-danger">*</span></label>
                                <select name="accessory_item_id" id="accessory_item_id" class="form-select select2" style="width:100%;">
                                    <option value="">Select Accessory</option>
                                    @if($ticket->accessory_item_id && $ticket->accessoryItem)
                                    <option value="{{ $ticket->accessory_item_id }}" selected>{{ $ticket->accessoryItem->item_code }} - {{ $ticket->accessoryItem->item_name }}</option>
                                    @endif
                                </select>
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Quantity <span class="text-danger">*</span></label>
                                <input type="number" name="accessory_qty" id="accessory_qty" class="form-control" min="1" value="{{ $ticket->accessory_qty ?? 1 }}">
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>

                        <div class="row g-3 mb-4">
                            <div class="col-md-4">
                                <label class="form-label">Supervisor <span class="text-danger">*</span></label>
                                <select name="supervisor_id" id="supervisor_id" class="form-select select2" required><option value="">Select</option>@foreach($supervisors as $sv)<option value="{{ $sv->id }}" data-type="{{ $sv->supervisor_type }}" {{ $ticket->supervisor_id == $sv->id ? 'selected' : '' }}>{{ $sv->name }} ({{ ucfirst($sv->supervisor_type ?? 'N/A') }})</option>@endforeach</select>
                                <div class="invalid-feedback"></div>
                                <div id="supervisorTypeInfo" class="mt-1"><span id="supervisorTypeBadge"></span></div>
                            </div>
                            <div class="col-md-4" id="technicianGroup">
                                <label class="form-label">Assign Technician</label>
                                <select name="technician_id" id="technician_id" class="form-select select2"><option value="">Assign Later</option>@foreach($technicians as $t)<option value="{{ $t->id }}" {{ $ticket->technician_id == $t->id ? 'selected' : '' }}>{{ $t->name }}</option>@endforeach</select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Price (RM)</label>
                                <div class="input-group"><span class="input-group-text bg-light">RM</span><input type="text" name="price" id="price_display" class="form-control bg-light fw-bold" readonly value="{{ number_format($ticket->price ?? 0, 2) }}"></div>
                                <div id="priceHint"></div>
                            </div>
                        </div>

                        <hr class="my-3">

                        {{-- Section 4: Description & Schedule --}}
                        <div class="d-flex align-items-center mb-3">
                            <span class="badge bg-warning text-dark rounded-circle me-2" style="width:28px;height:28px;display:inline-flex;align-items:center;justify-content:center;">4</span>
                            <h6 class="mb-0 fw-bold">Description & Schedule</h6>
                        </div>
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label">Description <span class="text-danger">*</span></label>
                                <textarea name="description" class="form-control" rows="3" required>{{ $ticket->description }}</textarea><div class="invalid-feedback"></div>
                            </div>
                            <div class="col-md-6"><label class="form-label">Expected Start Date</label><input type="date" name="expected_start_date" class="form-control" value="{{ $ticket->expected_start_date?->format('Y-m-d') }}"></div>
                            <div class="col-md-6"><label class="form-label">Expected End Date</label><input type="date" name="expected_end_date" class="form-control" value="{{ $ticket->expected_end_date?->format('Y-m-d') }}"></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-12">
                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-warning" id="btnSubmit"><i class="bi bi-pencil-square me-2"></i>Update Ticket</button>
                    <a href="{{ route('admin.tickets.show', $ticket->id) }}" class="btn btn-outline-secondary">Cancel</a>
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
    let currentSupervisorType = '{{ $ticket->supervisor?->supervisor_type }}';
    let citiesCache = {};

    $('.select2').select2({ theme: 'bootstrap-5', width: '100%' });

    // Init on load
    initSupervisorType();
    @if($ticket->jobCategory)
        toggleDeviceFields('{{ $ticket->jobCategory->slug }}', true);
    @endif
    checkOldRouterId(); // Check on load

    // Cache initial cities
    $('#city_id option').each(function() {
        let id = $(this).val();
        if (id) citiesCache[id] = { id: id, name: $(this).text(), postcode: $(this).data('postcode') || '' };
    });

    function initSupervisorType() {
        if (currentSupervisorType === 'internal') {
            $('#supervisorTypeBadge').html('<span class="badge bg-success"><i class="bi bi-building me-1"></i>Internal</span>');
            $('#technicianGroup').show();
        } else if (currentSupervisorType === 'external') {
            $('#supervisorTypeBadge').html('<span class="badge bg-warning text-dark"><i class="bi bi-person-badge me-1"></i>External</span>');
            $('#technicianGroup').hide();
        }
    }

    // ── Vendor → Branch ──
    $('#vendor_id').on('change', function() {
        $.get(baseUrl + '/ajax/vendor-branches', { vendor_id: $(this).val() }, function(data) {
            let o = '<option value="">Select Branch</option>';
            data.forEach(b => o += `<option value="${b.id}" data-state="${b.state_id}" data-city="${b.city_id}">${b.branch_name}</option>`);
            $('#vendor_branch_id').html(o).trigger('change.select2');
        });
    });

    $('#vendor_branch_id').on('change', function() {
        let stateId = $(this).find(':selected').data('state');
        let cityId = $(this).find(':selected').data('city');
        if (!stateId) return;
        $('#state_id').val(stateId).trigger('change.select2');
        loadCities(stateId, function() {
            if (cityId) { $('#city_id').val(cityId).trigger('change.select2'); if (citiesCache[cityId]) { $('#postcode').val(citiesCache[cityId].postcode || '-'); } }
        });
    });

    $('#state_id').on('change', function() {
        let sid = $(this).val();
        if (!sid) { $('#city_id').html('<option value="">Select District</option>'); $('#postcode').val(''); return; }
        loadCities(sid);
        $.get(baseUrl + '/ajax/supervisors', { state_id: sid }, function(data) {
            let o = '<option value="">Select Supervisor</option>';
            data.forEach(s => { let t = s.supervisor_type ? ` (${s.supervisor_type.charAt(0).toUpperCase()+s.supervisor_type.slice(1)})` : ''; o += `<option value="${s.id}" data-type="${s.supervisor_type}">${s.name}${t}</option>`; });
            $('#supervisor_id').html(o).trigger('change.select2');
        });
    });

    function loadCities(stateId, callback) {
        $('#city_id').html('<option value="">Loading...</option>');
        $.get(baseUrl + '/ajax/cities', { state_id: stateId }, function(data) {
            citiesCache = {};
            let o = '<option value="">Select District</option>';
            data.forEach(c => { citiesCache[c.id] = c; o += `<option value="${c.id}" data-postcode="${c.postcode || ''}">${c.name}</option>`; });
            $('#city_id').html(o).trigger('change.select2');
            if (typeof callback === 'function') callback();
        });
    }

    $('#city_id').on('change', function() {
        let cid = $(this).val();
        $('#postcode').val(cid && citiesCache[cid] ? (citiesCache[cid].postcode || '-') : '');
    });

    $('#supervisor_id').on('change', function() {
        currentSupervisorType = $(this).find(':selected').data('type');
        initSupervisorType();
        let svId = $(this).val(); if (!svId) return;
        if (currentSupervisorType === 'internal') {
            $.get(baseUrl + '/ajax/technicians', { supervisor_id: svId }, function(data) {
                let o = '<option value="">Assign Later</option>'; data.forEach(t => o += `<option value="${t.id}">${t.name}</option>`);
                $('#technician_id').html(o).trigger('change.select2');
            });
        }
        refreshPrice();
    });

    // ══════════════════════════════════════════════════════════
    // Job Category → toggle device fields
    // ══════════════════════════════════════════════════════════
    $('#job_category_id').on('change', function() {
        toggleDeviceFields($(this).find(':selected').data('slug'), false);
        checkOldRouterId();
        refreshPrice();
    });

    $('#job_type_id').on('change', function() {
        checkOldRouterId();
        refreshPrice();
    });

    function toggleDeviceFields(slug, isInit) {
        $('.device-field').hide();
        $('.terminal-required-star, .router-required-star').hide();
        $('#terminal_id').removeAttr('required');
        $('#router_id_select').val(null).trigger('change.select2').removeAttr('required').prop('disabled', true);
        $('#router_id_text').removeAttr('required').prop('disabled', true);
        $('#accessoriesSection').hide();

        if (slug === 'terminal') {
            $('#terminalIdGroup').show();
            $('.terminal-required-star').show();
            $('#terminal_id').attr('required', true);

        } else if (slug === 'router') {
            $('#routerIdSelectGroup').show();
            $('.router-required-star').show();
            $('#router_id_select').prop('disabled', false).attr('required', true);
            loadAvailableRouters(isInit ? '{{ $ticket->router_id }}' : null);

        } else if (slug === 'project') {
            $('#terminalIdGroup').show();
            $('#routerIdTextGroup').show();
            $('#router_id_text').prop('disabled', false);

        } else if (slug === 'accessories') {
            $('#accessoriesSection').show();
            if (isInit && '{{ $ticket->accessory_type_selected }}') {
                loadAvailableAccessories('{{ $ticket->accessory_type_selected }}', '{{ $ticket->accessory_item_id }}');
            }
        }
    }

    // Old ID: visible when (category=router OR category=terminal) AND type=replacement
    function checkOldRouterId() {
        let catSlug = $('#job_category_id').find(':selected').data('slug') || '';
        let typeSlug = ($('#job_type_id').find(':selected').data('slug') || '').toLowerCase();

        if ((catSlug === 'router' || catSlug === 'terminal') && typeSlug.includes('replacement')) {
            let label = catSlug === 'terminal' ? 'Old Terminal ID' : 'Old Router ID';
            $('#oldIdLabel').text(label);
            $('#oldTerminalIdGroup').show();
        } else {
            $('#oldTerminalIdGroup').hide();
        }
    }

    // ── Load available routers from inventory (grouped by Job Category) ──
    function loadAvailableRouters(selectedValue) {
        $.get(baseUrl + '/ajax/available-routers', function(groups) {
            let o = '<option value="">Select Router</option>';
            let found = false;
            groups.forEach(function(group) {
                o += '<optgroup label="' + group.category + '">';
                group.items.forEach(function(r) {
                    let sel = (selectedValue && r.id == selectedValue) ? ' selected' : '';
                    if (sel) found = true;
                    o += '<option value="' + r.id + '"' + sel + '>' + r.text + '</option>';
                });
                o += '</optgroup>';
            });
            // Keep existing value if not in available list (already assigned router)
            if (selectedValue && !found) {
                o += '<optgroup label="Currently Assigned">';
                o += '<option value="' + selectedValue + '" selected>' + selectedValue + ' (Currently assigned)</option>';
                o += '</optgroup>';
            }
            $('#router_id_select').html(o).trigger('change.select2');
        });
    }

    $('#accessory_type_selected').on('change', function() {
        let accType = $(this).val();
        if (!accType) { $('#accessory_item_id').html('<option value="">Select Accessory</option>').trigger('change.select2'); return; }
        loadAvailableAccessories(accType, null);
    });

    function loadAvailableAccessories(accType, selectedId) {
        $.get(baseUrl + '/ajax/available-accessories', { accessory_type: accType }, function(data) {
            let o = '<option value="">Select Accessory</option>';
            data.forEach(a => {
                let sel = (selectedId && a.id == selectedId) ? ' selected' : '';
                o += `<option value="${a.id}"${sel}>${a.text}</option>`;
            });
            $('#accessory_item_id').html(o).trigger('change.select2');
        });
    }

    function refreshPrice() {
        let svId = $('#supervisor_id').val(), catId = $('#job_category_id').val(), typeId = $('#job_type_id').val();
        if (!svId || !catId || !typeId) { $('#priceHint').html('<small class="text-muted">Select all 3 fields</small>'); return; }
        $('#priceHint').html('<small class="text-info"><i class="bi bi-hourglass-split me-1"></i>Fetching...</small>');
        $.ajax({ url: baseUrl + '/ajax/price', data: { supervisor_id: svId, job_category_id: catId, job_type_id: typeId },
            success: function(data) { let p = parseFloat(data.price||0); $('#price_display').val(p.toFixed(2)); if(p>0){$('#price_display').addClass('text-success');$('#priceHint').html('<small class="text-success"><i class="bi bi-check-circle me-1"></i>Price loaded</small>');}else{$('#price_display').removeClass('text-success');$('#priceHint').html('<small class="text-warning"><i class="bi bi-exclamation-triangle me-1"></i>No pricing configured</small>');} },
            error: function() { $('#priceHint').html('<small class="text-danger"><i class="bi bi-x-circle me-1"></i>Failed</small>'); }
        });
    }

    // ── Submit ──
    $('#ticketForm').on('submit', function(e) {
        e.preventDefault(); let btn = $('#btnSubmit');
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>Updating...');
        let fd = new FormData(this); fd.set('price', $('#price_display').val());
        $.ajax({ url: '{{ route("admin.tickets.update", $ticket->id) }}', method: 'POST', data: fd, processData: false, contentType: false, headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
            success: function(res) { if(res.success){showToast(res.message,'success');window.location.href=res.redirect;}else showToast(res.message,'error'); },
            error: function(xhr) { if(xhr.status===422){let errors=xhr.responseJSON?.errors||{};$('.is-invalid').removeClass('is-invalid');let f=null;Object.keys(errors).forEach(k=>{let el=$('[name="'+k+'"]');el.addClass('is-invalid').siblings('.invalid-feedback').text(errors[k][0]);if(!f)f=el;});if(f)$('html,body').animate({scrollTop:f.offset().top-100},300);showToast('Please fix validation errors.','error');}else showToast(xhr.responseJSON?.message||'Error','error'); },
            complete: () => btn.prop('disabled', false).html('<i class="bi bi-pencil-square me-2"></i>Update Ticket')
        });
    });
});
</script>
@endpush
