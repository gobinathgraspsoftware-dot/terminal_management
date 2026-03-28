@extends('layouts.app')
@section('title', 'Submit Claim')
@section('content')
<div class="page-header">
    <div class="d-flex align-items-center justify-content-between">
        <div>
            <h1><i class="bi bi-plus-circle me-2 text-primary"></i>Submit Claim</h1>
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('supervisor.dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('supervisor.claims.index') }}">Claims</a></li>
                <li class="breadcrumb-item active">Submit Claim</li>
            </ol>
        </div>
        <a href="{{ route('supervisor.claims.other-claims') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i>Back
        </a>
    </div>
</div>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header"><i class="bi bi-file-earmark-plus me-2"></i>Claim Details</div>
            <div class="card-body">
                <form id="createClaimForm" enctype="multipart/form-data">
                    @csrf
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Claim Type <span class="text-danger">*</span></label>
                            <select name="claim_type_label" class="form-select select2" required>
                                <option value="">-- Select Type --</option>
                                @foreach($claimTypes as $key => $label)
                                    <option value="{{ $key }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Amount (RM) <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text">RM</span>
                                <input type="number" name="claim_amount" class="form-control" min="0.01" step="0.01" placeholder="0.00" required>
                            </div>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Related Ticket <small class="text-muted">(optional)</small></label>
                            <select name="ticket_id" class="form-select select2">
                                <option value="">-- No Ticket --</option>
                                @foreach($tickets as $t)
                                    <option value="{{ $t->id }}">{{ $t->ticket_no }} – {{ $t->merchant_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Description <span class="text-danger">*</span></label>
                            <textarea name="description" class="form-control" rows="3" placeholder="Describe the claim..." required></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Remarks <small class="text-muted">(optional)</small></label>
                            <textarea name="remarks" class="form-control" rows="2" placeholder="Additional notes..."></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Attachments <small class="text-muted">(PDF/JPG/PNG, max 5MB each)</small></label>
                            <input type="file" name="attachments[]" class="form-control" multiple accept=".pdf,.jpg,.jpeg,.png">
                        </div>
                    </div>
                    <div class="d-flex gap-2 mt-4">
                        <button type="submit" class="btn btn-primary px-4">
                            <i class="bi bi-send me-1"></i>Submit Claim
                        </button>
                        <a href="{{ route('supervisor.claims.other-claims') }}" class="btn btn-outline-secondary">Cancel</a>
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

    $('#createClaimForm').on('submit', function (e) {
        e.preventDefault();
        var btn = $(this).find('[type=submit]');
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>Submitting...');
        $.ajax({
            url: '{{ route("supervisor.claims.store") }}',
            method: 'POST',
            data: new FormData(this),
            processData: false,
            contentType: false,
            success: function (res) {
                if (res.success) {
                    showToast(res.message, 'success');
                    setTimeout(function(){ window.location = '{{ route("supervisor.claims.other-claims") }}'; }, 1500);
                } else {
                    showToast(res.message, 'error');
                    btn.prop('disabled', false).html('<i class="bi bi-send me-1"></i>Submit Claim');
                }
            },
            error: function() {
                showToast('An error occurred. Please try again.', 'error');
                btn.prop('disabled', false).html('<i class="bi bi-send me-1"></i>Submit Claim');
            }
        });
    });
});
</script>
@endpush
