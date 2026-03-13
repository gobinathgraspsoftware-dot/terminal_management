@extends('layouts.app')
@section('title', 'Ticket ' . $ticket->ticket_no)

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-0">
                <i class="bi bi-ticket-detailed me-2"></i>{{ $ticket->ticket_no }}
                {!! Ticket::getStatusBadge($ticket->status) !!}
                {!! Ticket::getPriorityBadge($ticket->priority) !!}
            </h4>
            @if($ticket->isSlaBreach())
                <span class="badge bg-danger mt-1"><i class="bi bi-exclamation-triangle"></i> SLA BREACHED</span>
            @endif
        </div>
        <div>
            @if(!in_array($ticket->status, ['done_success','done_fail','closed']))
                <a href="{{ route('admin.tickets.edit', $ticket->id) }}" class="btn btn-outline-primary btn-sm"><i class="bi bi-pencil me-1"></i>Edit</a>
            @endif
            <a href="{{ route('admin.tickets.index') }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left me-1"></i>Back</a>
        </div>
    </div>

    <div class="row g-4">
        {{-- Left Column --}}
        <div class="col-lg-8">
            {{-- Section 1: Ticket Info --}}
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-primary text-white"><i class="bi bi-info-circle me-2"></i>Ticket Information</div>
                <div class="card-body">
                    <div class="row g-2">
                        <div class="col-md-4"><strong>Vendor:</strong><br>{{ $ticket->vendor?->vendor_name ?? '-' }}</div>
                        <div class="col-md-4"><strong>Vendor Ref No:</strong><br>{{ $ticket->vendor_ticket_ref_no ?? '-' }}</div>
                        <div class="col-md-4"><strong>Branch:</strong><br>{{ $ticket->vendorBranch?->branch_name ?? '-' }}</div>
                        <div class="col-md-4"><strong>State:</strong><br>{{ $ticket->state?->name ?? '-' }}</div>
                        <div class="col-md-4"><strong>District:</strong><br>{{ $ticket->city?->name ?? '-' }}</div>
                        <div class="col-md-4"><strong>Job Type:</strong><br>{{ $ticket->jobType?->job_title ?? '-' }}</div>
                        <div class="col-md-4"><strong>Charge:</strong><br>{{ $ticket->charge?->charge_name ?? '-' }}</div>
                        <div class="col-md-4"><strong>Supervisor:</strong><br>{{ $ticket->supervisor?->name ?? '-' }}</div>
                        <div class="col-md-4"><strong>Assignee:</strong><br>{{ $ticket->technician?->name ?? 'Unassigned' }}</div>
                        <div class="col-md-4"><strong>Priority:</strong><br>{!! Ticket::getPriorityBadge($ticket->priority) !!}</div>
                        <div class="col-md-4"><strong>SLA:</strong><br>{{ $ticket->sla_hours }}h — {{ $ticket->sla_remaining }}</div>
                        <div class="col-md-4"><strong>Created:</strong><br>{{ $ticket->created_at->format('d M Y H:i') }}</div>
                    </div>
                </div>
            </div>

            {{-- Section 2: Merchant Info --}}
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-success text-white"><i class="bi bi-shop me-2"></i>Merchant Information</div>
                <div class="card-body">
                    <div class="row g-2">
                        <div class="col-md-4"><strong>TID:</strong><br>{{ $ticket->tid ?? '-' }}</div>
                        <div class="col-md-4"><strong>Merchant Name:</strong><br>{{ $ticket->merchant_name ?? '-' }}</div>
                        <div class="col-md-4"><strong>Contact:</strong><br>{{ $ticket->contact_number ?? '-' }}</div>
                        <div class="col-md-12"><strong>Address:</strong><br>{{ $ticket->merchant_address ?? '-' }}</div>
                    </div>
                </div>
            </div>

            {{-- Section 3: Description --}}
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-info text-white"><i class="bi bi-card-text me-2"></i>Description</div>
                <div class="card-body">{!! nl2br(e($ticket->description)) !!}</div>
            </div>

            {{-- Section 4: Status Update --}}
            @if(count($allowedTransitions) > 0)
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-dark text-white"><i class="bi bi-arrow-repeat me-2"></i>Update Status</div>
                <div class="card-body">
                    <form id="statusForm" enctype="multipart/form-data">
                        @csrf
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">New Status <span class="text-danger">*</span></label>
                                <select name="status" id="new_status" class="form-select" required>
                                    <option value="">Select Status</option>
                                    @foreach($allowedTransitions as $st)
                                        <option value="{{ $st }}">{{ $statuses[$st] ?? ucfirst($st) }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-8" id="reschedule_fields" style="display:none;">
                                <label class="form-label">Reschedule Reason <span class="text-danger">*</span></label>
                                <input type="text" name="reschedule_reason" class="form-control" placeholder="Reason for rescheduling">
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">Remarks</label>
                                <textarea name="remarks" class="form-control" rows="2" placeholder="Optional remarks"></textarea>
                            </div>
                            <div class="col-md-12" id="proof_section" style="display:none;">
                                <div class="alert alert-info mb-2"><i class="bi bi-camera me-1"></i> <strong>Proof upload required for this status.</strong></div>
                                <div id="proof_fields"></div>
                            </div>
                            <div class="col-md-12">
                                <button type="submit" class="btn btn-dark" id="statusBtn">
                                    <i class="bi bi-check-circle me-1"></i> Update Status
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            @endif

            {{-- Proof Gallery --}}
            @if($ticket->proofs->count() > 0)
            <div class="card shadow-sm mb-4">
                <div class="card-header"><i class="bi bi-images me-2"></i>Uploaded Proofs</div>
                <div class="card-body">
                    <div class="row g-2">
                        @foreach($ticket->proofs as $proof)
                        <div class="col-md-3">
                            <div class="border rounded p-2 text-center">
                                @if($proof->is_image)
                                    <img src="{{ $proof->url }}" class="img-fluid rounded mb-1" style="max-height:120px;object-fit:cover;" alt="{{ $proof->caption }}">
                                @else
                                    <i class="bi bi-file-earmark-pdf text-danger" style="font-size:3rem;"></i>
                                @endif
                                <br><small class="text-muted">{{ Ticket::getProofTypeLabels()[$proof->proof_type] ?? $proof->proof_type }}</small>
                                <br><a href="{{ $proof->url }}" target="_blank" class="btn btn-sm btn-outline-primary mt-1"><i class="bi bi-download"></i></a>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
            @endif

            {{-- Status History --}}
            <div class="card shadow-sm mb-4">
                <div class="card-header"><i class="bi bi-clock-history me-2"></i>Status History</div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm mb-0">
                            <thead class="table-light">
                                <tr><th>Date</th><th>From</th><th>To</th><th>By</th><th>Remarks</th><th>Proofs</th></tr>
                            </thead>
                            <tbody>
                                @foreach($ticket->statusHistory as $h)
                                <tr>
                                    <td>{{ $h->created_at->format('d M Y H:i') }}</td>
                                    <td>{!! $h->from_status ? Ticket::getStatusBadge($h->from_status) : '-' !!}</td>
                                    <td>{!! Ticket::getStatusBadge($h->to_status) !!}</td>
                                    <td>{{ $h->changedBy?->name ?? '-' }}</td>
                                    <td>{{ $h->remarks ?? '-' }}</td>
                                    <td>
                                        @foreach($h->proofs as $p)
                                            <a href="{{ $p->url }}" target="_blank" class="badge bg-light text-dark me-1">
                                                <i class="bi bi-paperclip"></i> {{ Ticket::getProofTypeLabels()[$p->proof_type] ?? $p->proof_type }}
                                            </a>
                                        @endforeach
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {{-- Comments --}}
            <div class="card shadow-sm mb-4">
                <div class="card-header"><i class="bi bi-chat-dots me-2"></i>Comments</div>
                <div class="card-body">
                    <div id="commentsList">
                        @foreach($ticket->comments as $c)
                        <div class="d-flex mb-3">
                            <div class="bg-light rounded-circle d-flex align-items-center justify-content-center me-2" style="width:36px;height:36px;min-width:36px;">
                                {{ strtoupper(substr($c->user->name ?? '?', 0, 1)) }}
                            </div>
                            <div>
                                <strong>{{ $c->user->name ?? 'User' }}</strong>
                                <small class="text-muted ms-1">{{ $c->created_at->format('d M Y H:i') }}</small>
                                <p class="mb-0">{{ $c->comment }}</p>
                            </div>
                        </div>
                        @endforeach
                    </div>
                    <form id="commentForm" class="mt-3">
                        @csrf
                        <div class="input-group">
                            <input type="text" name="comment" class="form-control" placeholder="Add a comment..." required>
                            <button class="btn btn-primary" type="submit"><i class="bi bi-send"></i></button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- Right Column: Claim & Assignment --}}
        <div class="col-lg-4">
            {{-- Assign Technician --}}
            @can('assign', $ticket)
            @if(!in_array($ticket->status, ['done_success','done_fail','closed']))
            <div class="card shadow-sm mb-4">
                <div class="card-header"><i class="bi bi-person-plus me-2"></i>Assign Technician</div>
                <div class="card-body">
                    <form id="assignForm">
                        @csrf
                        <select name="technician_id" class="form-select mb-2" required>
                            <option value="">Select Technician</option>
                            @foreach($technicians as $tech)
                                <option value="{{ $tech->id }}" {{ $ticket->technician_id == $tech->id ? 'selected' : '' }}>{{ $tech->name }}</option>
                            @endforeach
                        </select>
                        <input type="text" name="remarks" class="form-control mb-2" placeholder="Remarks (optional)">
                        <button type="submit" class="btn btn-sm btn-primary w-100">Assign</button>
                    </form>
                </div>
            </div>
            @endif
            @endcan

            {{-- Section 5: Claim --}}
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-warning text-dark"><i class="bi bi-cash-coin me-2"></i>Claim Section</div>
                <div class="card-body">
                    <form id="claimForm">
                        @csrf
                        <div class="mb-2">
                            <label class="form-label small">Mileage (KM)</label>
                            <input type="number" name="mileage" id="claim_mileage" class="form-control form-control-sm" step="0.01" value="{{ $ticket->mileage ?? 0 }}">
                        </div>
                        <div class="mb-2">
                            <label class="form-label small">Mileage Rate (RM/km)</label>
                            <input type="text" class="form-control form-control-sm" readonly value="{{ number_format($ticket->mileage_rate ?? 0, 2) }}" id="claim_rate">
                        </div>
                        <div class="mb-2">
                            <label class="form-label small">Mileage Claim (RM)</label>
                            <input type="text" class="form-control form-control-sm fw-bold" readonly id="claim_mileage_amt" value="{{ number_format($ticket->mileage_amount ?? 0, 2) }}">
                        </div>
                        <div class="mb-2">
                            <label class="form-label small">Mileage Remarks</label>
                            <input type="text" name="mileage_remarks" class="form-control form-control-sm" value="{{ $ticket->mileage_remarks }}" placeholder="Route details">
                        </div>
                        <div class="mb-2">
                            <label class="form-label small">Toll (RM)</label>
                            <input type="number" name="toll" id="claim_toll" class="form-control form-control-sm" step="0.01" value="{{ $ticket->toll ?? 0 }}">
                        </div>
                        <div class="mb-2">
                            <label class="form-label small">Standby / Meal (RM)</label>
                            <input type="number" name="standby_meal" id="claim_meal" class="form-control form-control-sm" step="0.01" value="{{ $ticket->standby_meal ?? 0 }}">
                        </div>
                        <hr>
                        <div class="mb-2">
                            <label class="form-label small fw-bold text-primary">Total Claim (RM)</label>
                            <input type="text" class="form-control form-control-sm fw-bold text-primary" readonly id="claim_total" value="{{ number_format($ticket->total_claim_amount ?? 0, 2) }}">
                        </div>
                        <button type="submit" class="btn btn-warning btn-sm w-100 mt-2">
                            <i class="bi bi-save me-1"></i> Update Claim
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@php use App\Models\Ticket; @endphp

@push('scripts')
<script>
$(function() {
    // Status change → show/hide proof and reschedule fields
    var proofConfig = {
        'scheduled': {label: 'WhatsApp / Call Log Screenshot', types: {'whatsapp_screenshot': 'WhatsApp Screenshot', 'call_log_screenshot': 'Call Log Screenshot'}},
        'done_success': {label: 'Test Slip Image', types: {'test_slip': 'Test Slip Image'}},
        'done_fail': {label: 'Service Form Image', types: {'service_form': 'Service Form Image'}}
    };

    $('#new_status').on('change', function() {
        var st = $(this).val();
        $('#reschedule_fields').toggle(st === 'scheduled');
        if (proofConfig[st]) {
            var html = '';
            $.each(proofConfig[st].types, function(key, label) {
                html += '<div class="mb-2"><label class="form-label small">' + label + ' <span class="text-danger">*</span></label>' +
                    '<input type="file" name="proof_files[' + key + ']" class="form-control form-control-sm" accept=".jpg,.jpeg,.png,.pdf" required></div>';
            });
            $('#proof_fields').html(html);
            $('#proof_section').show();
        } else {
            $('#proof_section').hide();
            $('#proof_fields').html('');
        }
    });

    // Status form submit
    $('#statusForm').on('submit', function(e) {
        e.preventDefault();
        var fd = new FormData(this);
        var $btn = $('#statusBtn');
        $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Updating...');

        $.ajax({
            url: '{{ route("admin.tickets.change-status", $ticket->id) }}',
            method: 'POST',
            data: fd,
            processData: false,
            contentType: false,
            headers: {'X-CSRF-TOKEN': '{{ csrf_token() }}'},
            success: function(res) {
                if (res.success) { showToast(res.message, 'success'); setTimeout(function(){ location.reload(); }, 1000); }
            },
            error: function(xhr) {
                $btn.prop('disabled', false).html('<i class="bi bi-check-circle me-1"></i> Update Status');
                showToast(xhr.responseJSON?.message || 'Failed.', 'error');
            }
        });
    });

    // Assign form
    $('#assignForm').on('submit', function(e) {
        e.preventDefault();
        $.post('{{ route("admin.tickets.assign", $ticket->id) }}', $(this).serialize(), function(res) {
            if (res.success) { showToast(res.message, 'success'); setTimeout(function(){ location.reload(); }, 1000); }
        }).fail(function(xhr) { showToast(xhr.responseJSON?.message || 'Failed.', 'error'); });
    });

    // Claim calc
    function recalcClaim() {
        var m = parseFloat($('#claim_mileage').val()) || 0;
        var r = parseFloat($('#claim_rate').val()) || 0;
        var t = parseFloat($('#claim_toll').val()) || 0;
        var s = parseFloat($('#claim_meal').val()) || 0;
        var mAmt = m * r;
        $('#claim_mileage_amt').val(mAmt.toFixed(2));
        $('#claim_total').val((mAmt + t + s).toFixed(2));
    }
    $('#claim_mileage, #claim_toll, #claim_meal').on('input change', recalcClaim);

    // Claim form
    $('#claimForm').on('submit', function(e) {
        e.preventDefault();
        $.post('{{ route("admin.tickets.update-claim", $ticket->id) }}', $(this).serialize(), function(res) {
            if (res.success) { showToast(res.message, 'success'); setTimeout(function(){ location.reload(); }, 1000); }
        }).fail(function(xhr) { showToast(xhr.responseJSON?.message || 'Failed.', 'error'); });
    });

    // Comment form
    $('#commentForm').on('submit', function(e) {
        e.preventDefault();
        $.post('{{ route("admin.tickets.comment", $ticket->id) }}', $(this).serialize(), function(res) {
            if (res.success) {
                var c = res.comment;
                var html = '<div class="d-flex mb-3"><div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-2" style="width:36px;height:36px;min-width:36px;">' +
                    c.user_name.charAt(0).toUpperCase() + '</div><div><strong>' + c.user_name + '</strong><small class="text-muted ms-1">' + c.created_at + '</small><p class="mb-0">' + c.comment + '</p></div></div>';
                $('#commentsList').prepend(html);
                $('#commentForm')[0].reset();
            }
        }).fail(function() { showToast('Failed to add comment.', 'error'); });
    });
});
</script>
@endpush
