@extends('layouts.app')

@section('title', 'Ticket: ' . $ticket->ticket_no)

@section('content')
{{-- Page Header --}}
<div class="page-header d-flex justify-content-between align-items-center flex-wrap">
    <div>
        <h1><i class="bi bi-ticket-detailed me-2"></i>{{ $ticket->ticket_no }}</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('technician.dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('technician.tickets.index') }}">My Tickets</a></li>
                <li class="breadcrumb-item active">{{ $ticket->ticket_no }}</li>
            </ol>
        </nav>
    </div>
    <a href="{{ route('technician.tickets.index') }}" class="btn btn-outline-secondary btn-sm mt-2 mt-md-0">
        <i class="bi bi-arrow-left me-1"></i> Back
    </a>
</div>

<div class="row">
    {{-- LEFT COLUMN --}}
    <div class="col-lg-8">
        {{-- Status & Priority --}}
        <div class="card">
            <div class="card-body d-flex justify-content-between align-items-center flex-wrap">
                <div><span class="fw-semibold me-2">Status:</span>{!! \App\Models\Ticket::getStatusBadge($ticket->status) !!}</div>
                <div><span class="fw-semibold me-2">Priority:</span>{!! \App\Models\Ticket::getPriorityBadge($ticket->priority) !!}</div>
                <div>
                    <span class="fw-semibold me-2">SLA:</span>
                    @if($ticket->sla_remaining)
                        <span class="badge {{ $ticket->isSlaBreach() ? 'bg-danger' : 'bg-success' }}">{{ $ticket->sla_remaining }}</span>
                    @else
                        <span class="text-muted">N/A</span>
                    @endif
                </div>
            </div>
        </div>

        {{-- Ticket Details --}}
        <div class="card">
            <div class="card-header"><i class="bi bi-info-circle me-2"></i>Ticket Details</div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <small class="text-muted">Vendor</small>
                        <p class="mb-1 fw-semibold">{{ $ticket->vendor?->vendor_name ?? '-' }}</p>
                    </div>
                    <div class="col-md-6">
                        <small class="text-muted">Merchant Name</small>
                        <p class="mb-1">{{ $ticket->merchant_name ?? '-' }}</p>
                    </div>
                    <div class="col-md-12">
                        <small class="text-muted">Merchant Address</small>
                        <p class="mb-1">{{ $ticket->merchant_address ?? '-' }}</p>
                    </div>
                    <div class="col-md-4">
                        <small class="text-muted">Contact</small>
                        <p class="mb-1">{{ $ticket->contact_number ?? '-' }}</p>
                    </div>
                    <div class="col-md-4">
                        <small class="text-muted">State</small>
                        <p class="mb-1">{{ $ticket->state?->name ?? '-' }}</p>
                    </div>
                    <div class="col-md-4">
                        <small class="text-muted">City</small>
                        <p class="mb-1">{{ $ticket->city?->name ?? '-' }}</p>
                    </div>
                    <div class="col-md-4">
                        <small class="text-muted">TID</small>
                        <p class="mb-1">{{ $ticket->tid ?? '-' }}</p>
                    </div>
                    <div class="col-md-4">
                        <small class="text-muted">Terminal ID</small>
                        <p class="mb-1">{{ $ticket->terminal_id ?? '-' }}</p>
                    </div>
                    <div class="col-md-4">
                        <small class="text-muted">Router ID(s)</small>
                        <p class="mb-1">{{ $ticket->getRouterIdsDisplay() }}</p>
                    </div>
                </div>
                @if($ticket->description)
                <hr>
                <small class="text-muted">Description</small>
                <p class="mb-0">{{ $ticket->description }}</p>
                @endif
            </div>
        </div>

        {{-- Job Info --}}
        <div class="card">
            <div class="card-header"><i class="bi bi-briefcase me-2"></i>Job Info</div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <small class="text-muted">Job Category</small>
                        <p class="mb-1 fw-semibold">{{ $ticket->jobCategory?->category_name ?? '-' }}</p>
                    </div>
                    <div class="col-md-4">
                        <small class="text-muted">Job Type</small>
                        <p class="mb-1">{{ $ticket->jobType?->job_title ?? '-' }}</p>
                    </div>
                    <div class="col-md-4">
                        <small class="text-muted">Job Price</small>
                        <p class="mb-1 fw-semibold text-primary">RM {{ number_format($supervisorPrice ?? $ticket->price ?? 0, 2) }}</p>
                    </div>
                    @if($ticket->accessoryItem)
                    <div class="col-md-6">
                        <small class="text-muted">Accessory</small>
                        <p class="mb-1">{{ $ticket->accessoryItem->item_name ?? '-' }} ({{ $ticket->getAccessoryTypeLabel() }}) x{{ $ticket->accessory_qty ?? 0 }}</p>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Old Router ID — CHANGE #3: Only editable at In Progress --}}
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-router me-2"></i>Old Router / Terminal ID</span>
                @if($canUpdateOldRouterId)
                    <span class="badge bg-success">Editable</span>
                @else
                    <span class="badge bg-secondary">Read Only</span>
                @endif
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <small class="text-muted">Old Terminal ID</small>
                        <p class="mb-1">{{ $ticket->old_terminal_id ?? '-' }}</p>
                    </div>
                    <div class="col-md-6">
                        <small class="text-muted">Old Router ID(s)</small>
                        <p class="mb-1">{{ $ticket->getOldRouterIdsDisplay() }}</p>
                    </div>
                </div>
                @if($canUpdateOldRouterId)
                <hr>
                <div class="row align-items-end">
                    <div class="col-8">
                        <label for="old_terminal_id_input" class="form-label">Update Old Router ID</label>
                        <input type="text" id="old_terminal_id_input" class="form-control"
                               value="{{ $ticket->old_terminal_id }}" placeholder="Verify on-site and enter here" maxlength="100">
                    </div>
                    <div class="col-4">
                        <button type="button" class="btn btn-outline-primary w-100" id="btnSaveOldRouterId">
                            <i class="bi bi-save me-1"></i> Save
                        </button>
                    </div>
                </div>
                @else
                <div class="mt-2">
                    <small class="text-muted fst-italic">
                        <i class="bi bi-info-circle me-1"></i>Old Router ID can only be updated when ticket is <strong>In Progress</strong> (on-site verification).
                    </small>
                </div>
                @endif
            </div>
        </div>

        {{-- Financial Summary --}}
        <div class="card">
            <div class="card-header"><i class="bi bi-wallet2 me-2"></i>Financial Summary</div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-6 col-md-3"><small class="text-muted">Mileage</small><p class="mb-1">{{ number_format($ticket->mileage ?? 0, 2) }} km</p></div>
                    <div class="col-6 col-md-3"><small class="text-muted">Rate</small><p class="mb-1">RM {{ number_format($ticket->mileage_rate ?? 0, 2) }}/km</p></div>
                    <div class="col-6 col-md-3"><small class="text-muted">Toll</small><p class="mb-1">RM {{ number_format($ticket->toll ?? 0, 2) }}</p></div>
                    <div class="col-6 col-md-3"><small class="text-muted">Standby/Meal</small><p class="mb-1">RM {{ number_format($ticket->standby_meal ?? 0, 2) }}</p></div>
                    <div class="col-6"><small class="text-muted">Total Claim</small><p class="mb-1 fw-bold text-danger">RM {{ number_format($ticket->total_claim_amount ?? 0, 2) }}</p></div>
                    <div class="col-6"><small class="text-muted">Grand Total</small><p class="mb-1 fw-bold text-success fs-5">RM {{ number_format($ticket->grand_total, 2) }}</p></div>
                </div>
            </div>
        </div>

        {{-- Status History --}}
        <div class="card">
            <div class="card-header"><i class="bi bi-clock-history me-2"></i>Status History</div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead class="table-light"><tr><th>From</th><th>To</th><th>Changed By</th><th>Remarks</th><th>Date</th></tr></thead>
                        <tbody>
                            @forelse($ticket->statusHistory as $h)
                            <tr>
                                <td>{!! $h->from_status ? \App\Models\Ticket::getStatusBadge($h->from_status) : '<span class="text-muted">—</span>' !!}</td>
                                <td>{!! \App\Models\Ticket::getStatusBadge($h->to_status) !!}</td>
                                <td>{{ $h->changedBy?->name ?? '-' }}</td>
                                <td>{{ $h->remarks ?? '-' }}</td>
                                <td>{{ $h->created_at->format('d/m/Y H:i') }}</td>
                            </tr>
                            @empty
                            <tr><td colspan="5" class="text-center text-muted py-3">No history</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Comments --}}
        <div class="card">
            <div class="card-header"><i class="bi bi-chat-dots me-2"></i>Comments</div>
            <div class="card-body">
                <div id="commentsList">
                    @forelse($ticket->comments as $comment)
                    <div class="d-flex mb-3">
                        <div class="flex-shrink-0 me-3">
                            <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center" style="width:36px;height:36px;font-size:0.8rem;">
                                {{ strtoupper(substr($comment->user->name ?? '?', 0, 2)) }}
                            </div>
                        </div>
                        <div class="flex-grow-1">
                            <div class="fw-semibold">{{ $comment->user->name ?? 'Unknown' }}
                                <span class="badge bg-light text-dark ms-1">{{ ucfirst($comment->user->roles->first()?->name ?? 'user') }}</span>
                                <small class="text-muted ms-2">{{ $comment->created_at->format('d M Y H:i') }}</small>
                            </div>
                            <p class="mb-0 mt-1">{{ $comment->comment }}</p>
                        </div>
                    </div>
                    @empty
                    <p class="text-muted text-center mb-0" id="noCommentsText">No comments yet.</p>
                    @endforelse
                </div>
                <hr>
                <div class="input-group">
                    <input type="text" id="commentInput" class="form-control" placeholder="Write a comment..." maxlength="5000">
                    <button class="btn btn-primary" id="btnAddComment"><i class="bi bi-send"></i></button>
                </div>
            </div>
        </div>
    </div>

    {{-- RIGHT COLUMN --}}
    <div class="col-lg-4">
        {{-- Assignment Info --}}
        <div class="card">
            <div class="card-body">
                <div class="mb-2"><small class="text-muted">Supervisor:</small> {{ $ticket->supervisor?->name ?? '-' }}</div>
                <div class="mb-2"><small class="text-muted">Assigned:</small> {{ $ticket->assigned_at?->format('d M Y H:i') ?? '-' }}</div>
                @if($ticket->accepted_at)<div class="mb-2"><small class="text-muted">Accepted:</small> {{ $ticket->accepted_at->format('d M Y H:i') }}</div>@endif
                @if($ticket->sla_deadline)<div class="mb-0"><small class="text-muted">SLA Deadline:</small> {{ $ticket->sla_deadline->format('d M Y H:i') }}</div>@endif
            </div>
        </div>

        {{-- Status Change --}}
        @if(count($allowedTransitions) > 0)
        <div class="card">
            <div class="card-header"><i class="bi bi-arrow-right-circle me-2"></i>Change Status</div>
            <div class="card-body">
                <div class="mb-2">
                    <select id="newStatus" class="form-select">
                        <option value="">-- Select Status --</option>
                        @foreach($allowedTransitions as $status)
                            <option value="{{ $status }}">{{ $statuses[$status] ?? ucfirst(str_replace('_',' ',$status)) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-2">
                    <textarea id="statusRemarks" class="form-control form-control-sm" rows="2" placeholder="Remarks (optional)" maxlength="1000"></textarea>
                </div>
                <div id="scheduledFields" style="display:none;">
                    <div class="mb-2">
                        <label class="form-label small">Reschedule Reason <span class="text-danger">*</span></label>
                        <input type="text" id="rescheduleReason" class="form-control form-control-sm" maxlength="1000">
                    </div>
                    <div class="mb-2">
                        <label class="form-label small">Scheduled Date <span class="text-danger">*</span></label>
                        <input type="datetime-local" id="scheduledDate" class="form-control form-control-sm">
                    </div>
                </div>
                <div id="oldRouterIdField" style="display:none;">
                    <div class="mb-2">
                        <label class="form-label small">Old Router / Terminal ID</label>
                        <input type="text" id="statusOldTerminalId" class="form-control form-control-sm"
                               value="{{ $ticket->old_terminal_id }}" placeholder="Verify on-site" maxlength="100">
                        <small class="text-muted">Enter after verifying at merchant site</small>
                    </div>
                </div>
                <div id="proofFields" style="display:none;"></div>
                <button type="button" class="btn btn-primary btn-sm w-100" id="btnChangeStatus">
                    <i class="bi bi-check-circle me-1"></i> Update Status
                </button>
            </div>
        </div>
        @endif

        {{-- Claim Update --}}
        <div class="card">
            <div class="card-header"><i class="bi bi-receipt me-2"></i>Update Claim</div>
            <div class="card-body">
                <div class="mb-2"><label class="form-label small">Mileage (km)</label><input type="number" id="claimMileage" class="form-control form-control-sm" step="0.01" min="0" value="{{ $ticket->mileage ?? 0 }}"></div>
                <div class="mb-2"><label class="form-label small">Mileage Remarks</label><input type="text" id="claimMileageRemarks" class="form-control form-control-sm" value="{{ $ticket->mileage_remarks }}" maxlength="500"></div>
                <div class="mb-2"><label class="form-label small">Toll (RM)</label><input type="number" id="claimToll" class="form-control form-control-sm" step="0.01" min="0" value="{{ $ticket->toll ?? 0 }}"></div>
                <div class="mb-2"><label class="form-label small">Standby/Meal (RM)</label><input type="number" id="claimStandbyMeal" class="form-control form-control-sm" step="0.01" min="0" value="{{ $ticket->standby_meal ?? 0 }}"></div>
                <button type="button" class="btn btn-outline-primary btn-sm w-100" id="btnUpdateClaim"><i class="bi bi-save me-1"></i> Update Claim</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    var currentStatus = '{{ $ticket->status }}';

    // Status change toggle
    $('#newStatus').on('change', function() {
        var s = $(this).val();
        $('#scheduledFields').toggle(s === 'scheduled');
        // CHANGE #3: Old Router ID field only when In Progress
        $('#oldRouterIdField').toggle(s === 'in_progress' || currentStatus === 'in_progress');
        var proofTypes = { 'scheduled':[['whatsapp_screenshot','WhatsApp Screenshot'],['call_log_screenshot','Call Log Screenshot']], 'done_success':[['test_slip','Test Slip Image']], 'done_fail':[['service_form','Service Form Image']] };
        if (proofTypes[s]) {
            var h = '';
            proofTypes[s].forEach(function(p) { h += '<div class="mb-2"><label class="form-label small">'+p[1]+'</label><input type="file" class="form-control form-control-sm proof-file" name="proof_files['+p[0]+']" accept=".jpg,.jpeg,.png,.pdf"></div>'; });
            $('#proofFields').html(h).show();
        } else { $('#proofFields').hide().html(''); }
    });

    $('#btnChangeStatus').on('click', function() {
        var status = $('#newStatus').val();
        if (!status) { showToast('Select a status.','warning'); return; }
        if (status === 'scheduled' && (!$('#rescheduleReason').val() || !$('#scheduledDate').val())) { showToast('Reschedule reason and date required.','warning'); return; }
        var fd = new FormData();
        fd.append('status', status);
        fd.append('remarks', $('#statusRemarks').val());
        if (status === 'scheduled') { fd.append('reschedule_reason', $('#rescheduleReason').val()); fd.append('scheduled_date', $('#scheduledDate').val()); }
        if ($('#statusOldTerminalId').val() && (currentStatus === 'in_progress' || status === 'in_progress')) fd.append('old_terminal_id', $('#statusOldTerminalId').val());
        $('.proof-file').each(function() { if (this.files[0]) fd.append($(this).attr('name'), this.files[0]); });
        showLoading();
        $.ajax({ url: '{{ route("technician.tickets.change-status", $ticket->id) }}', method: 'POST', data: fd, processData: false, contentType: false,
            success: function(r) {
                hideLoading();
                if (r.success) {
                    showToast(r.message);
                    // Server returns redirect URL when technician loses access (e.g. rejection)
                    var redirectUrl = r.redirect || null;
                    setTimeout(function(){
                        if (redirectUrl) {
                            window.location.href = redirectUrl;
                        } else {
                            location.reload();
                        }
                    }, 1000);
                } else {
                    showToast(r.message||'Failed.','error');
                }
            },
            error: function(x) { hideLoading(); showToast(x.responseJSON?.message||'Failed.','error'); }
        });
    });

    // Old Router ID standalone save
    $('#btnSaveOldRouterId').on('click', function() {
        showLoading();
        $.ajax({ url: '{{ route("technician.tickets.change-status", $ticket->id) }}', method: 'POST',
            data: { status: currentStatus, old_terminal_id: $('#old_terminal_id_input').val(), remarks: 'Old Router ID updated' },
            success: function(r) { hideLoading(); showToast(r.success ? r.message : 'Failed.', r.success ? 'success':'error'); if (r.success) setTimeout(function(){ location.reload(); }, 1000); },
            error: function(x) { hideLoading(); showToast(x.responseJSON?.message||'Failed.','error'); }
        });
    });

    // Claim
    $('#btnUpdateClaim').on('click', function() {
        showLoading();
        $.ajax({ url: '{{ route("technician.tickets.update-claim", $ticket->id) }}', method: 'POST',
            data: { mileage: $('#claimMileage').val(), mileage_remarks: $('#claimMileageRemarks').val(), toll: $('#claimToll').val(), standby_meal: $('#claimStandbyMeal').val() },
            success: function(r) { hideLoading(); if (r.success) { showToast(r.message); setTimeout(function(){ location.reload(); }, 1000); } else showToast(r.message||'Failed.','error'); },
            error: function(x) { hideLoading(); showToast(x.responseJSON?.message||'Failed.','error'); }
        });
    });

    // Comment
    $('#btnAddComment').on('click', function() {
        var c = $('#commentInput').val().trim();
        if (!c) { showToast('Enter a comment.','warning'); return; }
        $.ajax({ url: '{{ route("technician.tickets.comment", $ticket->id) }}', method: 'POST', data: { comment: c },
            success: function(r) { if (r.success) { $('#noCommentsText').hide(); var d = r.comment;
                var h = '<div class="d-flex mb-3"><div class="flex-shrink-0 me-3"><div class="rounded-circle bg-success text-white d-flex align-items-center justify-content-center" style="width:36px;height:36px;font-size:0.8rem;">'+d.user_name.substring(0,2).toUpperCase()+'</div></div>';
                h += '<div class="flex-grow-1"><div class="fw-semibold">'+d.user_name+' <span class="badge bg-light text-dark ms-1">'+d.user_role+'</span> <small class="text-muted ms-2">'+d.created_at+'</small></div><p class="mb-0 mt-1">'+d.comment+'</p></div></div>';
                $('#commentsList').prepend(h); $('#commentInput').val(''); showToast('Comment added.'); } },
            error: function() { showToast('Failed.','error'); }
        });
    });
    $('#commentInput').on('keypress', function(e) { if (e.which === 13) $('#btnAddComment').click(); });
});
</script>
@endpush
