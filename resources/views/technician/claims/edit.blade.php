@extends('layouts.app')
@section('title', 'Edit Claim – ' . $claim->claim_no)
@section('content')
<div class="page-header">
    <div class="d-flex align-items-center justify-content-between">
        <div>
            <h1><i class="bi bi-pencil me-2 text-warning"></i>Edit Claim</h1>
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('technician.dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('technician.claims.index') }}">Claims</a></li>
                <li class="breadcrumb-item active">Edit {{ $claim->claim_no }}</li>
            </ol>
        </div>
        <a href="{{ route('technician.claims.show', $claim->id) }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i>Back
        </a>
    </div>
</div>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <span><i class="bi bi-pencil-square me-2"></i>Edit Claim – {{ $claim->claim_no }}</span>
                {!! \App\Models\Claim::getStatusBadge($claim->status) !!}
            </div>
            <div class="card-body">
                <form id="editClaimForm" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Claim Type <span class="text-danger">*</span></label>
                            <select name="claim_type_label" class="form-select select2" required>
                                <option value="">-- Select Type --</option>
                                @foreach($claimTypes as $key => $label)
                                    <option value="{{ $key }}" {{ $claim->claim_type_label === $key ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Amount (RM) <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text">RM</span>
                                <input type="number" name="claim_amount" class="form-control" min="0.01" step="0.01" value="{{ $claim->total_amount }}" required>
                            </div>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Related Ticket</label>
                            <select name="ticket_id" class="form-select select2">
                                <option value="">-- No Ticket --</option>
                                @foreach($tickets as $t)
                                    <option value="{{ $t->id }}" {{ $claim->ticket_id == $t->id ? 'selected' : '' }}>
                                        {{ $t->ticket_no }} – {{ $t->merchant_name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Description <span class="text-danger">*</span></label>
                            <textarea name="description" class="form-control" rows="3" required>{{ $claim->description }}</textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Remarks</label>
                            <textarea name="remarks" class="form-control" rows="2">{{ $claim->remarks }}</textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Add New Attachments <small class="text-muted">(leave blank to keep existing)</small></label>
                            <input type="file" name="attachments[]" class="form-control" multiple accept=".pdf,.jpg,.jpeg,.png">
                        </div>

                        {{-- Existing Attachments --}}
                        @if($claim->attachments->isNotEmpty())
                        <div class="col-12">
                            <label class="form-label fw-semibold">Existing Attachments</label>
                            <div class="row g-2">
                                @foreach($claim->attachments as $att)
                                <div class="col-sm-6" id="att-row-{{ $att->id }}">
                                    <div class="d-flex align-items-center border rounded p-2">
                                        <i class="bi bi-file-earmark text-primary me-2"></i>
                                        <div class="flex-grow-1 overflow-hidden">
                                            <div class="text-truncate small fw-semibold">{{ $att->file_name }}</div>
                                        </div>
                                        <a href="{{ asset('storage/'.$att->file_path) }}" target="_blank" class="btn btn-sm btn-outline-primary me-1"><i class="bi bi-eye"></i></a>
                                        <button type="button" class="btn btn-sm btn-outline-danger btn-delete-att"
                                            data-att-id="{{ $att->id }}"
                                            data-url="{{ route('technician.claims.delete-attachment', [$claim->id, $att->id]) }}">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                        </div>
                        @endif
                    </div>

                    <div class="d-flex gap-2 mt-4">
                        <button type="submit" class="btn btn-warning px-4">
                            <i class="bi bi-save me-1"></i>Save Changes
                        </button>
                        <a href="{{ route('technician.claims.show', $claim->id) }}" class="btn btn-outline-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
@push('scripts')
<script>
$(function () {
    $('.select2').select2({ theme: 'bootstrap-5', width: '100%' });

    $('#editClaimForm').on('submit', function(e) {
        e.preventDefault();
        var btn = $(this).find('[type=submit]');
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>Saving...');
        var fd = new FormData(this);
        $.ajax({
            url: '{{ route("technician.claims.update", $claim->id) }}',
            method: 'POST', data: fd, processData: false, contentType: false,
            success: function(res) {
                if (res.success) {
                    showToast(res.message, 'success');
                    setTimeout(function(){ window.location = '{{ route("technician.claims.show", $claim->id) }}'; }, 1500);
                } else {
                    showToast(res.message, 'error');
                    btn.prop('disabled', false).html('<i class="bi bi-save me-1"></i>Save Changes');
                }
            },
            error: function(){ showToast('An error occurred.', 'error'); btn.prop('disabled', false).html('<i class="bi bi-save me-1"></i>Save Changes'); }
        });
    });

    $(document).on('click', '.btn-delete-att', function() {
        var row  = $(this).closest('[id^=att-row-]');
        var url  = $(this).data('url');
        confirmAction('Delete Attachment?', 'This cannot be undone.', function() {
            $.ajax({ url: url, method: 'DELETE',
                success: function(r){ if(r.success){ row.fadeOut(); showToast(r.message,'success'); } else showToast(r.message,'error'); }
            });
        });
    });
});
</script>
@endpush
