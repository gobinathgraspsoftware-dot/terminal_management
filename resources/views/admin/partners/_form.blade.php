{{-- Reusable Partner Form Partial --}}
@php
    $isEdit = isset($partner) && $partner;
@endphp

<div class="row">
    <!-- Basic Information Section -->
    <div class="col-12">
        <h6 class="text-muted mb-3"><i class="bi bi-info-circle me-2"></i>Basic Information</h6>
    </div>
    
    <div class="col-md-6 mb-3">
        <label for="partner_code" class="form-label">Partner Code</label>
        <input type="text" class="form-control bg-light" id="partner_code" name="partner_code" 
            value="{{ $isEdit ? $partner->partner_code : ($nextCode ?? '') }}" 
            {{ $isEdit ? 'readonly' : '' }} placeholder="Auto-generated if empty">
        <div class="form-text">Leave empty to auto-generate</div>
    </div>

    <div class="col-md-6 mb-3">
        <label for="partner_name" class="form-label">Partner Name <span class="text-danger">*</span></label>
        <input type="text" class="form-control" id="partner_name" name="partner_name" 
            value="{{ $isEdit ? $partner->partner_name : old('partner_name') }}" required>
    </div>

    <div class="col-md-6 mb-3">
        <label for="job_intake_method" class="form-label">Job Intake Method <span class="text-danger">*</span></label>
        <select class="form-select" id="job_intake_method" name="job_intake_method" required>
            @foreach($jobIntakeMethods as $value => $label)
            <option value="{{ $value }}" 
                {{ ($isEdit ? $partner->job_intake_method : old('job_intake_method', 'manual')) == $value ? 'selected' : '' }}>
                {{ $label }}
            </option>
            @endforeach
        </select>
    </div>

    <div class="col-md-6 mb-3">
        <label for="status" class="form-label">Status <span class="text-danger">*</span></label>
        <select class="form-select" id="status" name="status" required>
            <option value="active" {{ ($isEdit ? $partner->status : old('status', 'active')) == 'active' ? 'selected' : '' }}>Active</option>
            <option value="inactive" {{ ($isEdit ? $partner->status : old('status')) == 'inactive' ? 'selected' : '' }}>Inactive</option>
        </select>
    </div>

    <!-- API Key Field (shown when job intake is API) -->
    <div class="col-md-12 mb-3" id="apiKeySection" style="display: none;">
        <label for="api_key" class="form-label">API Key</label>
        <div class="input-group">
            <input type="text" class="form-control" id="api_key" name="api_key" 
                value="{{ $isEdit ? $partner->api_key : '' }}" placeholder="Auto-generated if empty">
            <button type="button" class="btn btn-outline-secondary" id="generateApiKey">
                <i class="bi bi-arrow-repeat"></i> Generate
            </button>
        </div>
        <div class="form-text">Leave empty to auto-generate when using API integration</div>
    </div>
</div>

<hr class="my-4">

<!-- Person in Charge Section -->
<div class="row">
    <div class="col-12">
        <h6 class="text-muted mb-3"><i class="bi bi-person me-2"></i>Person in Charge (PIC)</h6>
    </div>

    <div class="col-md-4 mb-3">
        <label for="pic_name" class="form-label">PIC Name</label>
        <input type="text" class="form-control" id="pic_name" name="pic_name" 
            value="{{ $isEdit ? $partner->pic_name : old('pic_name') }}" placeholder="Contact person name">
    </div>

    <div class="col-md-4 mb-3">
        <label for="pic_email" class="form-label">PIC Email</label>
        <input type="email" class="form-control" id="pic_email" name="pic_email" 
            value="{{ $isEdit ? $partner->pic_email : old('pic_email') }}" placeholder="email@example.com">
    </div>

    <div class="col-md-4 mb-3">
        <label for="pic_phone" class="form-label">PIC Phone</label>
        <input type="text" class="form-control" id="pic_phone" name="pic_phone" 
            value="{{ $isEdit ? $partner->pic_phone : old('pic_phone') }}" placeholder="+60 12-345 6789">
    </div>
</div>

<hr class="my-4">

<!-- Address Section -->
<div class="row">
    <div class="col-12">
        <h6 class="text-muted mb-3"><i class="bi bi-geo-alt me-2"></i>Address</h6>
    </div>

    <div class="col-md-12 mb-3">
        <label for="address" class="form-label">Street Address</label>
        <textarea class="form-control" id="address" name="address" rows="2" 
            placeholder="Building, street, etc.">{{ $isEdit ? $partner->address : old('address') }}</textarea>
    </div>

    <div class="col-md-4 mb-3">
        <label for="city" class="form-label">City</label>
        <input type="text" class="form-control" id="city" name="city" 
            value="{{ $isEdit ? $partner->city : old('city') }}" placeholder="City">
    </div>

    <div class="col-md-4 mb-3">
        <label for="state" class="form-label">State</label>
        <select class="form-select" id="state" name="state">
            <option value="">Select State</option>
            @foreach($states as $state)
            <option value="{{ $state }}" 
                {{ ($isEdit ? $partner->state : old('state')) == $state ? 'selected' : '' }}>
                {{ $state }}
            </option>
            @endforeach
        </select>
    </div>

    <div class="col-md-2 mb-3">
        <label for="postcode" class="form-label">Postcode</label>
        <input type="text" class="form-control" id="postcode" name="postcode" 
            value="{{ $isEdit ? $partner->postcode : old('postcode') }}" placeholder="12345">
    </div>

    <div class="col-md-2 mb-3">
        <label for="country" class="form-label">Country</label>
        <input type="text" class="form-control" id="country" name="country" 
            value="{{ $isEdit ? $partner->country : old('country', 'Malaysia') }}" placeholder="Country">
    </div>
</div>

<hr class="my-4">

<!-- SLA Rules Section -->
<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h6 class="text-muted mb-0"><i class="bi bi-clock-history me-2"></i>SLA Rules</h6>
            <button type="button" class="btn btn-sm btn-outline-primary" id="addSlaRule">
                <i class="bi bi-plus-lg me-1"></i> Add SLA Rule
            </button>
        </div>
    </div>

    <div class="col-12">
        <div id="slaRulesContainer">
            @if($isEdit && $partner->sla_rules)
                @foreach($partner->sla_rules as $index => $rule)
                <div class="sla-rule-row card mb-3">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3 mb-2">
                                <label class="form-label small">SLA Type</label>
                                <select class="form-select form-select-sm" name="sla_type">
                                    <option value="">Select Type</option>
                                    <option value="installation" {{ ($rule['sla_type'] ?? '') == 'installation' ? 'selected' : '' }}>Installation</option>
                                    <option value="repair" {{ ($rule['sla_type'] ?? '') == 'repair' ? 'selected' : '' }}>Repair</option>
                                    <option value="maintenance" {{ ($rule['sla_type'] ?? '') == 'maintenance' ? 'selected' : '' }}>Maintenance</option>
                                    <option value="collection" {{ ($rule['sla_type'] ?? '') == 'collection' ? 'selected' : '' }}>Collection</option>
                                    <option value="other" {{ ($rule['sla_type'] ?? '') == 'other' ? 'selected' : '' }}>Other</option>
                                </select>
                            </div>
                            <div class="col-md-2 mb-2">
                                <label class="form-label small">Priority</label>
                                <select class="form-select form-select-sm" name="sla_priority">
                                    <option value="low" {{ ($rule['priority'] ?? '') == 'low' ? 'selected' : '' }}>Low</option>
                                    <option value="medium" {{ ($rule['priority'] ?? 'medium') == 'medium' ? 'selected' : '' }}>Medium</option>
                                    <option value="high" {{ ($rule['priority'] ?? '') == 'high' ? 'selected' : '' }}>High</option>
                                    <option value="critical" {{ ($rule['priority'] ?? '') == 'critical' ? 'selected' : '' }}>Critical</option>
                                </select>
                            </div>
                            <div class="col-md-2 mb-2">
                                <label class="form-label small">Response (hrs)</label>
                                <input type="number" class="form-control form-control-sm" name="response_hours" 
                                    value="{{ $rule['response_hours'] ?? 24 }}" min="1" max="720">
                            </div>
                            <div class="col-md-2 mb-2">
                                <label class="form-label small">Resolution (hrs)</label>
                                <input type="number" class="form-control form-control-sm" name="resolution_hours" 
                                    value="{{ $rule['resolution_hours'] ?? 48 }}" min="1" max="720">
                            </div>
                            <div class="col-md-2 mb-2">
                                <label class="form-label small">Escalation (hrs)</label>
                                <div class="input-group input-group-sm">
                                    <div class="input-group-text">
                                        <input type="checkbox" class="form-check-input mt-0" name="escalation_enabled"
                                            {{ !empty($rule['escalation_enabled']) ? 'checked' : '' }}>
                                    </div>
                                    <input type="number" class="form-control" name="escalation_hours" 
                                        value="{{ $rule['escalation_hours'] ?? '' }}" min="1" max="720"
                                        {{ empty($rule['escalation_enabled']) ? 'disabled' : '' }}>
                                </div>
                            </div>
                            <div class="col-md-1 mb-2 d-flex align-items-end">
                                <button type="button" class="btn btn-sm btn-outline-danger remove-sla-rule">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                @endforeach
            @endif
        </div>
        <div id="noSlaRules" class="text-center text-muted py-4 {{ ($isEdit && $partner->sla_rules && count($partner->sla_rules) > 0) ? 'd-none' : '' }}">
            <i class="bi bi-clock-history fs-3"></i>
            <p class="mb-0">No SLA rules defined. Click "Add SLA Rule" to add one.</p>
        </div>
    </div>
</div>

<hr class="my-4">

<!-- Notes Section -->
<div class="row">
    <div class="col-12">
        <h6 class="text-muted mb-3"><i class="bi bi-sticky me-2"></i>Additional Notes</h6>
    </div>

    <div class="col-md-12 mb-3">
        <textarea class="form-control" id="notes" name="notes" rows="3" 
            placeholder="Any additional notes or comments...">{{ $isEdit ? $partner->notes : old('notes') }}</textarea>
    </div>
</div>

<!-- Form Actions -->
<div class="row">
    <div class="col-12">
        <hr class="my-4">
        <div class="d-flex justify-content-end gap-2">
            <a href="{{ route('admin.partners.index') }}" class="btn btn-secondary">
                <i class="bi bi-x-lg me-1"></i> Cancel
            </a>
            <button type="submit" class="btn btn-primary">
                <i class="bi bi-check-lg me-1"></i> {{ $isEdit ? 'Update Partner' : 'Create Partner' }}
            </button>
        </div>
    </div>
</div>

<!-- SLA Rule Template (hidden) -->
<template id="slaRuleTemplate">
    <div class="sla-rule-row card mb-3">
        <div class="card-body">
            <div class="row">
                <div class="col-md-3 mb-2">
                    <label class="form-label small">SLA Type</label>
                    <select class="form-select form-select-sm" name="sla_type">
                        <option value="">Select Type</option>
                        <option value="installation">Installation</option>
                        <option value="repair">Repair</option>
                        <option value="maintenance">Maintenance</option>
                        <option value="collection">Collection</option>
                        <option value="other">Other</option>
                    </select>
                </div>
                <div class="col-md-2 mb-2">
                    <label class="form-label small">Priority</label>
                    <select class="form-select form-select-sm" name="sla_priority">
                        <option value="low">Low</option>
                        <option value="medium" selected>Medium</option>
                        <option value="high">High</option>
                        <option value="critical">Critical</option>
                    </select>
                </div>
                <div class="col-md-2 mb-2">
                    <label class="form-label small">Response (hrs)</label>
                    <input type="number" class="form-control form-control-sm" name="response_hours" value="24" min="1" max="720">
                </div>
                <div class="col-md-2 mb-2">
                    <label class="form-label small">Resolution (hrs)</label>
                    <input type="number" class="form-control form-control-sm" name="resolution_hours" value="48" min="1" max="720">
                </div>
                <div class="col-md-2 mb-2">
                    <label class="form-label small">Escalation (hrs)</label>
                    <div class="input-group input-group-sm">
                        <div class="input-group-text">
                            <input type="checkbox" class="form-check-input mt-0" name="escalation_enabled">
                        </div>
                        <input type="number" class="form-control" name="escalation_hours" min="1" max="720" disabled>
                    </div>
                </div>
                <div class="col-md-1 mb-2 d-flex align-items-end">
                    <button type="button" class="btn btn-sm btn-outline-danger remove-sla-rule">
                        <i class="bi bi-trash"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

@push('scripts')
<script>
$(document).ready(function() {
    // Show/hide API key field based on job intake method
    function toggleApiKeySection() {
        if ($('#job_intake_method').val() === 'api') {
            $('#apiKeySection').slideDown();
        } else {
            $('#apiKeySection').slideUp();
        }
    }
    toggleApiKeySection();
    $('#job_intake_method').on('change', toggleApiKeySection);

    // Generate random API key
    $('#generateApiKey').on('click', function() {
        let key = 'ptn_' + Array.from(crypto.getRandomValues(new Uint8Array(16)))
            .map(b => b.toString(16).padStart(2, '0'))
            .join('');
        $('#api_key').val(key);
    });

    // Add SLA rule
    $('#addSlaRule').on('click', function() {
        let template = $('#slaRuleTemplate').html();
        $('#slaRulesContainer').append(template);
        $('#noSlaRules').addClass('d-none');
    });

    // Remove SLA rule
    $(document).on('click', '.remove-sla-rule', function() {
        $(this).closest('.sla-rule-row').remove();
        if ($('.sla-rule-row').length === 0) {
            $('#noSlaRules').removeClass('d-none');
        }
    });

    // Toggle escalation input
    $(document).on('change', '[name="escalation_enabled"]', function() {
        let input = $(this).closest('.input-group').find('[name="escalation_hours"]');
        if ($(this).is(':checked')) {
            input.prop('disabled', false).focus();
        } else {
            input.prop('disabled', true).val('');
        }
    });

    // Initialize Select2 for state dropdown
    $('#state').select2({
        theme: 'bootstrap-5',
        placeholder: 'Select State',
        allowClear: true
    });
});
</script>
@endpush
