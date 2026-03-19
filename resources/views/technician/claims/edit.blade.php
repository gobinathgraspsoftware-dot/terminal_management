@extends('layouts.app')

@section('title', 'Edit Claim - ' . $claim->claim_no)

@section('content')
<div class="container-fluid">
    {{-- Page Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1">Edit Claim</h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('technician.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('technician.claims.index') }}">Claims</a></li>
                    <li class="breadcrumb-item active">Edit {{ $claim->claim_no }}</li>
                </ol>
            </nav>
        </div>
        <a href="{{ route('technician.claims.show', $claim->id) }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Back to Claim
        </a>
    </div>

    {{-- Status Badge --}}
    <div class="mb-3">
        <span class="me-2">Claim No: <strong>{{ $claim->claim_no }}</strong></span>
        {!! \App\Models\Claim::getStatusBadge($claim->status) !!}
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white">
            <h5 class="card-title mb-0"><i class="bi bi-pencil-square me-2"></i>Update Claim Information</h5>
        </div>
        <div class="card-body">
            <form id="editClaimForm" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                <div class="row g-3">
                    {{-- Claim Type --}}
                    <div class="col-md-6">
                        <label for="claim_type_label" class="form-label">Claim Type <span class="text-danger">*</span></label>
                        <select id="claim_type_label" name="claim_type_label" class="form-select" required>
                            <option value="">-- Select Claim Type --</option>
                            @foreach($claimTypes as $value => $label)
                                <option value="{{ $label }}" {{ $claim->claim_type_label === $label ? 'selected' : '' }}>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                        <div class="invalid-feedback"></div>
                    </div>

                    {{-- Linked Ticket (Optional) --}}
                    <div class="col-md-6">
                        <label for="ticket_id" class="form-label">Related Ticket (Optional)</label>
                        <select id="ticket_id" name="ticket_id" class="form-select select2-field">
                            <option value="">-- None --</option>
                            @foreach($tickets as $ticket)
                                <option value="{{ $ticket->id }}" {{ $claim->ticket_id == $ticket->id ? 'selected' : '' }}>
                                    {{ $ticket->ticket_no }} - {{ $ticket->merchant_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Description --}}
                    <div class="col-12">
                        <label for="description" class="form-label">Description <span class="text-danger">*</span></label>
                        <textarea id="description" name="description" class="form-control" rows="3" required>{{ $claim->description }}</textarea>
                        <div class="invalid-feedback"></div>
                    </div>

                    {{-- Claim Amount --}}
                    <div class="col-md-4">
                        <label for="claim_amount" class="form-label">Claim Amount (RM) <span class="text-danger">*</span></label>
                        <input type="number" id="claim_amount" name="claim_amount" class="form-control"
                               step="0.01" min="0.01" required value="{{ $claim->total_amount }}">
                        <div class="invalid-feedback"></div>
                    </div>

                    {{-- Remarks --}}
                    <div class="col-md-8">
                        <label for="remarks" class="form-label">Remarks</label>
                        <textarea id="remarks" name="remarks" class="form-control" rows="2">{{ $claim->remarks }}</textarea>
                    </div>

                    {{-- Existing Attachments --}}
                    @if($claim->attachments->isNotEmpty())
                    <div class="col-12">
                        <label class="form-label">Existing Attachments</label>
                        <div class="list-group">
                            @foreach($claim->attachments as $attachment)
                            <div class="list-group-item d-flex justify-content-between align-items-center" id="attachment-{{ $attachment->id }}">
                                <div>
                                    <i class="bi bi-file-earmark me-2"></i>
                                    <span>{{ $attachment->file_name }}</span>
                                    <small class="text-muted ms-2">({{ number_format(($attachment->file_size ?? 0) / 1024, 1) }} KB)</small>
                                </div>
                                <div class="btn-group btn-group-sm">
                                    <a href="{{ asset('storage/' . $attachment->file_path) }}" class="btn btn-outline-primary" target="_blank" title="View">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <button type="button" class="btn btn-outline-danger btn-delete-attachment"
                                            data-attachment-id="{{ $attachment->id }}" title="Delete">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                    @endif

                    {{-- New Attachments --}}
                    <div class="col-12">
                        <label for="attachments" class="form-label">Add More Attachments (Max 5, PDF/PNG/JPG, 5MB each)</label>
                        <input type="file" id="attachments" name="attachments[]" class="form-control"
                               multiple accept=".pdf,.png,.jpg,.jpeg">
                        <div class="form-text">New attachments will be added alongside existing ones.</div>
                    </div>
                </div>

                <hr>

                <div class="d-flex justify-content-end gap-2">
                    <a href="{{ route('technician.claims.show', $claim->id) }}" class="btn btn-outline-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary" id="updateBtn">
                        <i class="bi bi-check-circle me-1"></i> Update Claim
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(function() {
    // Initialize Select2
    $('#ticket_id').select2({
        theme: 'bootstrap-5',
        placeholder: '-- None --',
        allowClear: true
    });
    // Trigger change.select2 to preserve pre-selected value
    $('#ticket_id').trigger('change.select2');

    // Delete attachment
    $(document).on('click', '.btn-delete-attachment', function() {
        const btn = $(this);
        const attachmentId = btn.data('attachment-id');

        Swal.fire({
            title: 'Delete Attachment?',
            text: 'This action cannot be undone.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            confirmButtonText: 'Yes, delete it',
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '{{ route("technician.claims.delete-attachment", [$claim->id, ""]) }}/' + attachmentId,
                    method: 'DELETE',
                    headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                    success: function(res) {
                        if (res.success) {
                            $('#attachment-' + attachmentId).fadeOut(300, function() { $(this).remove(); });
                            showToast('success', res.message);
                        } else {
                            showToast('error', res.message);
                        }
                    },
                    error: function(xhr) {
                        showToast('error', xhr.responseJSON?.message || 'Failed to delete attachment.');
                    }
                });
            }
        });
    });

    // Form submission
    $('#editClaimForm').on('submit', function(e) {
        e.preventDefault();

        const btn = $('#updateBtn');
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Updating...');

        // Reset validation
        $(this).find('.is-invalid').removeClass('is-invalid');

        const formData = new FormData(this);
        // Override method for Laravel
        formData.append('_method', 'PUT');

        $.ajax({
            url: '{{ route("technician.claims.update", $claim->id) }}',
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            success: function(res) {
                if (res.success) {
                    showToast('success', res.message);
                    setTimeout(() => window.location.href = '{{ route("technician.claims.show", $claim->id) }}', 1500);
                } else {
                    showToast('error', res.message || 'An error occurred.');
                    btn.prop('disabled', false).html('<i class="bi bi-check-circle me-1"></i> Update Claim');
                }
            },
            error: function(xhr) {
                if (xhr.status === 422) {
                    const errors = xhr.responseJSON.errors;
                    $.each(errors, function(field, messages) {
                        const input = $('[name="' + field + '"]');
                        input.addClass('is-invalid');
                        input.siblings('.invalid-feedback').text(messages[0]);
                    });
                } else if (xhr.status === 403) {
                    showToast('error', xhr.responseJSON?.message || 'You do not have permission to edit this claim.');
                } else {
                    showToast('error', xhr.responseJSON?.message || 'Server error. Please try again.');
                }
                btn.prop('disabled', false).html('<i class="bi bi-check-circle me-1"></i> Update Claim');
            }
        });
    });
});
</script>
@endpush
