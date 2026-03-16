@extends('layouts.app')

@section('title', 'Submit Other Claim')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1">Submit Other Claim</h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('supervisor.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('supervisor.claims.index') }}">Claims</a></li>
                    <li class="breadcrumb-item active">Submit Other Claim</li>
                </ol>
            </nav>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">
                    <h6 class="mb-0"><i class="bi bi-file-earmark-plus me-1"></i> New Other Claim</h6>
                </div>
                <div class="card-body">
                    <form id="claim-form" enctype="multipart/form-data">
                        @csrf
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Claim Type <span class="text-danger">*</span></label>
                                <select name="claim_type_label" class="form-select" required>
                                    <option value="">-- Select Claim Type --</option>
                                    @foreach($claimTypes as $key => $label)
                                        <option value="{{ $label }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Claim Amount (RM) <span class="text-danger">*</span></label>
                                <input type="number" name="claim_amount" class="form-control"
                                       step="0.01" min="0.01" required placeholder="0.00">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Description <span class="text-danger">*</span></label>
                                <textarea name="description" class="form-control" rows="3" required
                                          placeholder="Describe the claim..."></textarea>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Linked Ticket (Optional)</label>
                                <select name="ticket_id" class="form-select select2-single">
                                    <option value="">-- No Ticket --</option>
                                    @foreach($tickets as $ticket)
                                        <option value="{{ $ticket->id }}">{{ $ticket->ticket_no }} - {{ $ticket->merchant_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Remarks</label>
                                <input type="text" name="remarks" class="form-control" placeholder="Optional remarks...">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Attachments (PDF, PNG, JPG - max 5MB each)</label>
                                <input type="file" name="attachments[]" class="form-control" multiple
                                       accept=".pdf,.png,.jpg,.jpeg">
                                <small class="text-muted">You can upload up to 5 files.</small>
                            </div>
                        </div>

                        <hr>
                        <div class="d-flex justify-content-end gap-2">
                            <a href="{{ route('supervisor.claims.index') }}" class="btn btn-secondary">Cancel</a>
                            <button type="submit" class="btn btn-primary" id="btn-submit">
                                <i class="bi bi-send me-1"></i> Submit Claim
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">
                    <h6 class="mb-0"><i class="bi bi-question-circle me-1"></i> Claim Types</h6>
                </div>
                <div class="card-body small">
                    <ul class="list-unstyled mb-0">
                        <li class="mb-2"><strong>Courier Faulty Device:</strong> Cost to courier faulty devices back to office/vendor.</li>
                        <li class="mb-2"><strong>Miscellaneous:</strong> Any other work-related expenses.</li>
                        <li class="mb-2"><strong>Out-of-Pocket:</strong> Personal funds spent on work requirements.</li>
                        <li class="mb-2"><strong>Transport / Parking / Toll:</strong> Travel-related costs not covered by ticket claims.</li>
                        <li class="mb-0"><strong>Meal / Accommodation:</strong> Meal or overnight stay expenses.</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(function() {
    $('.select2-single').select2({ theme: 'bootstrap-5', width: '100%', placeholder: '-- No Ticket --', allowClear: true });

    $('#claim-form').on('submit', function(e) {
        e.preventDefault();
        var formData = new FormData(this);
        var $btn = $('#btn-submit');
        $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Submitting...');

        $.ajax({
            url: '{{ route("supervisor.claims.store") }}',
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(res) {
                if (res.success) {
                    showToast(res.message, 'success');
                    setTimeout(function() { window.location.href = '{{ route("supervisor.claims.index") }}'; }, 1500);
                } else {
                    showToast(res.message || 'Error occurred.', 'error');
                    $btn.prop('disabled', false).html('<i class="bi bi-send me-1"></i> Submit Claim');
                }
            },
            error: function(xhr) {
                var errors = xhr.responseJSON?.errors;
                if (errors) {
                    var msg = Object.values(errors).flat().join('<br>');
                    showToast(msg, 'error');
                } else {
                    showToast(xhr.responseJSON?.message || 'Error occurred.', 'error');
                }
                $btn.prop('disabled', false).html('<i class="bi bi-send me-1"></i> Submit Claim');
            }
        });
    });
});
</script>
@endpush
