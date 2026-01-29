@extends('layouts.app')

@section('title', 'Create Partner - TMS')

@section('content')
<!-- Page Header -->
<div class="page-header">
    <div class="row align-items-center">
        <div class="col">
            <h1><i class="bi bi-plus-circle me-2"></i>Create Partner</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.partners.index') }}">Partners</a></li>
                    <li class="breadcrumb-item active">Create</li>
                </ol>
            </nav>
        </div>
        <div class="col-auto">
            <a href="{{ route('admin.partners.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Back to List
            </a>
        </div>
    </div>
</div>

<!-- Form Card -->
<div class="card">
    <div class="card-header">
        <i class="bi bi-building me-2"></i>Partner Information
    </div>
    <div class="card-body">
        <form id="partnerForm" method="POST" action="{{ route('admin.partners.store') }}">
            @csrf
            @include('admin.partners._form', ['partner' => null])
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
        
        // Collect SLA rules as array
        $('#slaRulesContainer .sla-rule-row').each(function(index) {
            let slaType = $(this).find('[name="sla_type"]').val();
            if (slaType) {
                formData.append('sla_rules[' + index + '][sla_type]', slaType);
                formData.append('sla_rules[' + index + '][priority]', $(this).find('[name="sla_priority"]').val());
                formData.append('sla_rules[' + index + '][response_hours]', $(this).find('[name="response_hours"]').val());
                formData.append('sla_rules[' + index + '][resolution_hours]', $(this).find('[name="resolution_hours"]').val());
                formData.append('sla_rules[' + index + '][escalation_enabled]', $(this).find('[name="escalation_enabled"]').is(':checked') ? '1' : '0');
                formData.append('sla_rules[' + index + '][escalation_hours]', $(this).find('[name="escalation_hours"]').val() || '');
            }
        });
        
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
                    showToast(xhr.responseJSON?.message || 'Failed to create partner', 'error');
                }
            }
        });
    });
});
</script>
@endpush
