@extends('layouts.app')

@section('title', 'Edit Partner - TMS')

@section('content')
<!-- Page Header -->
<div class="page-header">
    <div class="row align-items-center">
        <div class="col">
            <h1><i class="bi bi-pencil-square me-2"></i>Edit Partner</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.partners.index') }}">Partners</a></li>
                    <li class="breadcrumb-item active">Edit</li>
                </ol>
            </nav>
        </div>
        <div class="col-auto">
            <a href="{{ route('admin.partners.show', $partner->id) }}" class="btn btn-info me-2">
                <i class="bi bi-eye me-1"></i> View
            </a>
            <a href="{{ route('admin.partners.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Back to List
            </a>
        </div>
    </div>
</div>

<!-- Partner Info Banner -->
<div class="alert alert-info mb-4">
    <div class="d-flex align-items-center">
        <i class="bi bi-building fs-4 me-3"></i>
        <div>
            <strong>{{ $partner->partner_code }}</strong> - {{ $partner->partner_name }}
            <div class="small">Last updated: {{ $partner->updated_at->format('Y-m-d H:i') }}</div>
        </div>
        <div class="ms-auto">
            {!! $partner->status_badge !!}
        </div>
    </div>
</div>

<!-- Form Card -->
<div class="card">
    <div class="card-header">
        <i class="bi bi-building me-2"></i>Partner Information
    </div>
    <div class="card-body">
        <form id="partnerForm" method="POST" action="{{ route('admin.partners.update', $partner->id) }}">
            @csrf
            @method('PUT')
            @include('admin.partners._form', ['partner' => $partner])
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Form submission
    $('#partnerForm').on('submit', function(e) {
        e.preventDefault();
        
        // Clear previous errors
        $('.is-invalid').removeClass('is-invalid');
        $('.invalid-feedback').remove();
        
        let formData = new FormData(this);
        
        // Collect SLA rules
        let slaRules = [];
        $('#slaRulesContainer .sla-rule-row').each(function() {
            let rule = {
                sla_type: $(this).find('[name="sla_type"]').val(),
                priority: $(this).find('[name="sla_priority"]').val(),
                response_hours: $(this).find('[name="response_hours"]').val(),
                resolution_hours: $(this).find('[name="resolution_hours"]').val(),
                escalation_enabled: $(this).find('[name="escalation_enabled"]').is(':checked'),
                escalation_hours: $(this).find('[name="escalation_hours"]').val() || null
            };
            if (rule.sla_type) {
                slaRules.push(rule);
            }
        });
        formData.append('sla_rules', JSON.stringify(slaRules));
        
        showLoading();
        
        $.ajax({
            url: $(this).attr('action'),
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                hideLoading();
                if (response.success) {
                    showToast(response.message, 'success');
                    setTimeout(() => {
                        window.location.href = response.redirect;
                    }, 1000);
                }
            },
            error: function(xhr) {
                hideLoading();
                if (xhr.status === 422) {
                    let errors = xhr.responseJSON.errors;
                    $.each(errors, function(field, messages) {
                        let input = $('[name="' + field + '"]');
                        input.addClass('is-invalid');
                        input.after('<div class="invalid-feedback">' + messages[0] + '</div>');
                    });
                    showToast('Please correct the errors in the form', 'error');
                } else {
                    showToast(xhr.responseJSON?.message || 'Failed to update partner', 'error');
                }
            }
        });
    });
});
</script>
@endpush
