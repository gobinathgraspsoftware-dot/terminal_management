@extends('layouts.app')

@section('title', 'Ticket: ' . $ticket->ticket_no)

@section('content')
{{-- ══════════════════════════════════════════════════════════════════
     Page Header
     ══════════════════════════════════════════════════════════════════ --}}
<div class="page-header d-flex justify-content-between align-items-center flex-wrap">
    <div>
        <h1><i class="bi bi-ticket-detailed me-2"></i>{{ $ticket->ticket_no }}</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.tickets.index') }}">Tickets</a></li>
                <li class="breadcrumb-item active">{{ $ticket->ticket_no }}</li>
            </ol>
        </nav>
    </div>
    <div class="d-flex gap-2 mt-2 mt-md-0">
        @if(!in_array($ticket->status, ['done_success','done_fail','closed']))
        <a href="{{ route('admin.tickets.edit', $ticket->id) }}" class="btn btn-warning btn-sm">
            <i class="bi bi-pencil me-1"></i> Edit
        </a>
        @endif
        <a href="{{ route('admin.tickets.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i> Back
        </a>
    </div>
</div>

<div class="row">
    {{-- ════════════════════════════════════════════════════════════
         LEFT COLUMN — Ticket Info
         ════════════════════════════════════════════════════════════ --}}
    <div class="col-lg-8">
        {{-- Status & Priority Row --}}
        <div class="card">
            <div class="card-body d-flex justify-content-between align-items-center">
                <div>
                    <span class="fw-semibold me-2">Status:</span>{!! \App\Models\Ticket::getStatusBadge($ticket->status) !!}
                </div>
                <div>
                    <span class="fw-semibold me-2">Priority:</span>{!! \App\Models\Ticket::getPriorityBadge($ticket->priority) !!}
                </div>
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
                        <small class="text-muted">Branch</small>
                        <p class="mb-1">{{ $ticket->vendorBranch?->branch_name ?? '-' }}</p>
                    </div>
                    <div class="col-md-6">
                        <small class="text-muted">Vendor Ref No</small>
                        <p class="mb-1">{{ $ticket->vendor_ticket_ref_no ?? '-' }}</p>
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
                        <small class="text-muted">Contact Number</small>
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
                    @php
                        $jobCat = $ticket->jobCategory;
                        $showTid = $jobCat && !$jobCat->hidesDeviceIds();
                        $showTerminalId = $jobCat && ($jobCat->requiresTerminalId() || $jobCat->showsBothDeviceIds());
                        $showRouterId = $jobCat && ($jobCat->requiresRouterId() || $jobCat->showsBothDeviceIds());
                    @endphp
                    @if($showTid || $showTerminalId || $showRouterId)
                    <div class="col-md-4">
                        <small class="text-muted">TID</small>
                        <p class="mb-1">{{ $ticket->tid ?? '-' }}</p>
                    </div>
                    @endif
                    @if($showTerminalId)
                    <div class="col-md-4">
                        <small class="text-muted">Terminal ID</small>
                        <p class="mb-1">{{ $ticket->terminal_id ?? '-' }}</p>
                    </div>
                    @endif
                    @if($showRouterId)
                    <div class="col-md-4">
                        <small class="text-muted">Router ID(s)</small>
                        <p class="mb-1">{{ $ticket->getRouterIdsDisplay() }}</p>
                    </div>
                    @endif
                    <div class="col-md-4">
                        <small class="text-muted">Serial Number</small>
                        <p class="mb-1">{{ $ticket->serial_number ?? '-' }}</p>
                    </div>
                </div>
                @if($ticket->description)
                <hr>
                <small class="text-muted">Description</small>
                <p class="mb-0">{{ $ticket->description }}</p>
                @endif
            </div>
        </div>

        {{-- Job & Pricing --}}
        <div class="card">
            <div class="card-header"><i class="bi bi-briefcase me-2"></i>Job & Pricing</div>
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
                        <p class="mb-1 fw-semibold text-primary">RM {{ number_format($ticket->price ?? 0, 2) }}</p>
                    </div>
                    @if($ticket->accessoryItem)
                    <div class="col-md-4">
                        <small class="text-muted">Accessory Type</small>
                        <p class="mb-1">{{ $ticket->getAccessoryTypeLabel() }}</p>
                    </div>
                    <div class="col-md-4">
                        <small class="text-muted">Accessory Item</small>
                        <p class="mb-1">{{ $ticket->accessoryItem->item_name ?? '-' }}</p>
                    </div>
                    <div class="col-md-4">
                        <small class="text-muted">Accessory Qty</small>
                        <p class="mb-1">{{ $ticket->accessory_qty ?? '-' }}</p>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- ══════════════════════════════════════════════════════════
             OLD ROUTER ID — CHANGE #3: Only editable at In Progress
             ══════════════════════════════════════════════════════════ --}}
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
                    <div class="col-md-8">
                        <label for="old_terminal_id_input" class="form-label">Update Old Router ID</label>
                        <input type="text" id="old_terminal_id_input" class="form-control"
                               value="{{ $ticket->old_terminal_id }}" placeholder="Enter Old Router / Terminal ID" maxlength="100">
                    </div>
                    <div class="col-md-4">
                        <button type="button" class="btn btn-outline-primary w-100" id="btnSaveOldRouterId">
                            <i class="bi bi-save me-1"></i> Save
                        </button>
                    </div>
                </div>
                @else
                <div class="mt-2">
                    <small class="text-muted fst-italic">
                        <i class="bi bi-info-circle me-1"></i>Old Router ID can only be updated when ticket is <strong>In Progress</strong> (verified on-site).
                    </small>
                </div>
                @endif
            </div>
        </div>

        {{-- Claim / Financial Summary --}}
        <div class="card">
            <div class="card-header"><i class="bi bi-wallet2 me-2"></i>Financial Summary</div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-3">
                        <small class="text-muted">Mileage</small>
                        <p class="mb-1">{{ number_format($ticket->mileage ?? 0, 2) }} km</p>
                    </div>
                    <div class="col-md-3">
                        <small class="text-muted">Mileage Rate</small>
                        <p class="mb-1">RM {{ number_format($ticket->mileage_rate ?? 0, 2) }}/km</p>
                    </div>
                    <div class="col-md-3">
                        <small class="text-muted">Mileage Amount</small>
                        <p class="mb-1">RM {{ number_format($ticket->mileage_amount ?? 0, 2) }}</p>
                    </div>
                    <div class="col-md-3">
                        <small class="text-muted">Toll</small>
                        <p class="mb-1">RM {{ number_format($ticket->toll ?? 0, 2) }}</p>
                    </div>
                    <div class="col-md-3">
                        <small class="text-muted">Standby / Meal</small>
                        <p class="mb-1">RM {{ number_format($ticket->standby_meal ?? 0, 2) }}</p>
                    </div>
                    <div class="col-md-3">
                        <small class="text-muted">Total Claim</small>
                        <p class="mb-1 fw-bold text-danger">RM {{ number_format($ticket->total_claim_amount ?? 0, 2) }}</p>
                    </div>
                    <div class="col-md-3">
                        <small class="text-muted">Job Price</small>
                        <p class="mb-1 fw-bold text-primary">RM {{ number_format($supervisorPrice ?? $ticket->price ?? 0, 2) }}</p>
                    </div>
                    <div class="col-md-3">
                        <small class="text-muted">Grand Total</small>
                        <p class="mb-1 fw-bold text-success fs-5">RM {{ number_format($ticket->grand_total, 2) }}</p>
                    </div>
                </div>
                @if($ticket->mileage_remarks)
                <hr>
                <small class="text-muted">Mileage Remarks:</small>
                <p class="mb-0">{{ $ticket->mileage_remarks }}</p>
                @endif
            </div>
        </div>

        {{-- Status History --}}
        <div class="card">
            <div class="card-header"><i class="bi bi-clock-history me-2"></i>Status History</div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>From</th>
                                <th>To</th>
                                <th>Changed By</th>
                                <th>Remarks</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($ticket->statusHistory as $history)
                            <tr>
                                <td>{!! $history->from_status ? \App\Models\Ticket::getStatusBadge($history->from_status) : '<span class="text-muted">—</span>' !!}</td>
                                <td>{!! \App\Models\Ticket::getStatusBadge($history->to_status) !!}</td>
                                <td>{{ $history->changedBy?->name ?? '-' }}</td>
                                <td>{{ $history->remarks ?? '-' }}</td>
                                <td>{{ $history->created_at->format('d/m/Y H:i') }}</td>
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

    {{-- ════════════════════════════════════════════════════════════
         RIGHT COLUMN — Actions & Assignment
         ════════════════════════════════════════════════════════════ --}}
    <div class="col-lg-4">

        {{-- Meta Info --}}
        <div class="card">
            <div class="card-body">
                <div class="mb-2"><small class="text-muted">Created By:</small> {{ $ticket->creator?->name ?? '-' }}</div>
                <div class="mb-2"><small class="text-muted">Created:</small> {{ $ticket->created_at?->format('d M Y H:i') }}</div>
                <div class="mb-2"><small class="text-muted">Updated:</small> {{ $ticket->updated_at?->format('d M Y H:i') }}</div>
                @if($ticket->assigned_at)
                <div class="mb-2"><small class="text-muted">Assigned:</small> {{ $ticket->assigned_at->format('d M Y H:i') }}</div>
                @endif
                @if($ticket->accepted_at)
                <div class="mb-2"><small class="text-muted">Accepted:</small> {{ $ticket->accepted_at->format('d M Y H:i') }}</div>
                @endif
                @if($ticket->completed_at)
                <div class="mb-2"><small class="text-muted">Completed:</small> {{ $ticket->completed_at->format('d M Y H:i') }}</div>
                @endif
                @if($ticket->closed_at)
                <div class="mb-0"><small class="text-muted">Closed:</small> {{ $ticket->closed_at->format('d M Y H:i') }}</div>
                @endif
            </div>
        </div>

        {{-- ══════════════════════════════════════════════════════
             ASSIGNMENT MANAGEMENT CARD
             CHANGE #2: Supervisor reassignment + conditional
             technician assignment based on supervisor type
             ══════════════════════════════════════════════════════ --}}
        <div class="card">
            <div class="card-header"><i class="bi bi-people me-2"></i>Assignment Management</div>
            <div class="card-body">

                {{-- Current Assignment Display --}}
                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <small class="text-muted">Current Supervisor</small>
                        @if($ticket->supervisor)
                            <span class="badge {{ $ticket->supervisor->isExternalSupervisor() ? 'bg-warning text-dark' : 'bg-info' }}">
                                {{ $ticket->supervisor->isExternalSupervisor() ? 'External' : 'Internal' }}
                            </span>
                        @endif
                    </div>
                    <p class="fw-semibold mb-1">{{ $ticket->supervisor?->name ?? 'Not Assigned' }}</p>
                </div>

                @php
                    $currentSupervisor = $ticket->supervisor;
                    $isExternal = $currentSupervisor && $currentSupervisor->isExternalSupervisor();
                    $isInternal = $currentSupervisor && $currentSupervisor->isInternalSupervisor();
                    $isCompleted = in_array($ticket->status, ['done_success', 'done_fail', 'closed']);
                @endphp

                <div class="mb-3">
                    <small class="text-muted">Current Technician</small>
                    @if($isExternal)
                        <p class="mb-0 fst-italic text-muted">External supervisor — works directly</p>
                    @else
                        <p class="fw-semibold mb-0">{{ $ticket->technician?->name ?? 'Not Assigned' }}</p>
                    @endif
                </div>

                <hr>

                @if($isCompleted)
                    {{-- Ticket is completed — no reassignment allowed --}}
                    <div class="alert alert-light border mb-0 py-2 px-3">
                        <i class="bi bi-lock me-1 text-muted"></i>
                        <small class="text-muted">Ticket is completed — assignment cannot be changed.</small>
                    </div>
                @else

                {{-- ────────────────────────────────────────────────
                     SUPERVISOR REASSIGNMENT SECTION
                     ──────────────────────────────────────────────── --}}
                @if($canReassignSupervisor)
                    <div class="mb-3" id="supervisorReassignSection">
                        <h6 class="fw-bold text-primary mb-2">
                            <i class="bi bi-arrow-repeat me-1"></i> Reassign Supervisor
                        </h6>
                        <div class="mb-2">
                            <select id="reassign_supervisor_id" class="form-select" style="width:100%">
                                <option value="">-- Select Supervisor --</option>
                                @foreach($supervisors as $sup)
                                    <option value="{{ $sup->id }}"
                                            data-type="{{ $sup->supervisor_type }}"
                                            data-mileage="{{ $sup->mileage_rate }}"
                                            {{ $ticket->supervisor_id == $sup->id ? 'selected' : '' }}>
                                        {{ $sup->name }} ({{ ucfirst($sup->supervisor_type) }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div id="supervisorTypePreview" class="mb-2" style="display:none;">
                            <small id="supervisorTypeText" class="fst-italic"></small>
                        </div>
                        <div class="mb-2">
                            <input type="text" id="reassign_supervisor_remarks" class="form-control form-control-sm"
                                   placeholder="Remarks (optional)" maxlength="1000">
                        </div>
                        <button type="button" class="btn btn-outline-primary btn-sm w-100" id="btnReassignSupervisor">
                            <i class="bi bi-arrow-repeat me-1"></i> Reassign Supervisor
                        </button>
                    </div>
                    <hr>
                @else
                    @if($ticket->supervisor_id)
                    <div class="mb-3">
                        <div class="alert alert-light border mb-0 py-2 px-3">
                            <i class="bi bi-lock me-1 text-muted"></i>
                            <small class="text-muted">Supervisor locked — ticket has been accepted.</small>
                        </div>
                    </div>
                    <hr>
                    @endif
                @endif

                {{-- ────────────────────────────────────────────────
                     TECHNICIAN ASSIGN / REASSIGN SECTION
                     Hidden when external supervisor (server-side + JS)
                     ──────────────────────────────────────────────── --}}
                <div id="technicianAssignSection" style="{{ $isExternal ? 'display:none;' : '' }}">
                    @if(!$ticket->supervisor_id)
                        {{-- No supervisor assigned yet --}}
                        <div class="alert alert-light border mb-0 py-2 px-3">
                            <i class="bi bi-info-circle me-1 text-muted"></i>
                            <small class="text-muted">Assign a supervisor first before assigning a technician.</small>
                        </div>

                    @else
                        {{-- Internal supervisor — SHOW technician assign/reassign --}}
                        <h6 class="fw-bold text-primary mb-2">
                            <i class="bi bi-person-gear me-1"></i>
                            {{ $ticket->technician_id ? 'Reassign Technician' : 'Assign Technician' }}
                        </h6>
                        @if($technicians->isNotEmpty())
                        <div class="mb-2">
                            <select id="technician_id" class="form-select" style="width:100%">
                                <option value="">-- Select Technician --</option>
                                @foreach($technicians as $tech)
                                    <option value="{{ $tech->id }}" {{ $ticket->technician_id == $tech->id ? 'selected' : '' }}>
                                        {{ $tech->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-2">
                            <input type="text" id="assign_tech_remarks" class="form-control form-control-sm"
                                   placeholder="Remarks (optional)" maxlength="1000">
                        </div>
                        <button type="button" class="btn btn-outline-success btn-sm w-100" id="btnAssignTechnician">
                            <i class="bi bi-person-check me-1"></i>
                            {{ $ticket->technician_id ? 'Reassign Technician' : 'Assign Technician' }}
                        </button>
                        @else
                        <div class="alert alert-warning border mb-0 py-2 px-3">
                            <i class="bi bi-exclamation-triangle me-1"></i>
                            <small>No active technicians found under this supervisor.</small>
                        </div>
                        @endif
                    @endif
                </div>

                {{-- Dynamic external supervisor message (shown via JS when external selected) --}}
                <div id="technicianExternalMsg" style="{{ $isExternal ? '' : 'display:none;' }}">
                    <div class="alert alert-light border mb-0 py-2 px-3">
                        <i class="bi bi-person-badge me-1 text-warning"></i>
                        <small class="text-muted">External supervisor — no technician assignment required. The supervisor will work on this ticket directly.</small>
                    </div>
                </div>

                @endif {{-- end @if($isCompleted) / @else --}}

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

                {{-- Scheduled fields --}}
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

                {{-- Old Router ID field — only when In Progress --}}
                <div id="oldRouterIdField" style="display:none;">
                    <div class="mb-2">
                        <label class="form-label small">Old Router / Terminal ID</label>
                        <input type="text" id="statusOldTerminalId" class="form-control form-control-sm"
                               value="{{ $ticket->old_terminal_id }}" placeholder="Enter Old Router ID" maxlength="100">
                        <small class="text-muted">Verified on-site during In Progress</small>
                    </div>
                </div>

                {{-- Proof files --}}
                <div id="proofFields" style="display:none;"></div>

                <button type="button" class="btn btn-primary btn-sm w-100" id="btnChangeStatus">
                    <i class="bi bi-check-circle me-1"></i> Update Status
                </button>
            </div>
        </div>
        @endif

        {{-- Claim Update (Admin) --}}
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-receipt me-2"></i>Claim Details</span>
                @if($canUpdateClaim)
                    <span class="badge bg-success">Editable</span>
                @else
                    <span class="badge bg-secondary">Locked</span>
                @endif
            </div>
            <div class="card-body">
                @if($canUpdateClaim)
                <div class="mb-2">
                    <label class="form-label small">Mileage (km)</label>
                    <input type="number" id="claimMileage" class="form-control form-control-sm" step="0.01" min="0" value="{{ $ticket->mileage ?? 0 }}">
                </div>
                <div class="mb-2">
                    <label class="form-label small">Mileage Remarks</label>
                    <input type="text" id="claimMileageRemarks" class="form-control form-control-sm" value="{{ $ticket->mileage_remarks }}" maxlength="500">
                </div>
                <div class="mb-2">
                    <label class="form-label small">Toll (RM)</label>
                    <input type="number" id="claimToll" class="form-control form-control-sm" step="0.01" min="0" value="{{ $ticket->toll ?? 0 }}">
                </div>
                <div class="mb-2">
                    <label class="form-label small">Standby / Meal (RM)</label>
                    <input type="number" id="claimStandbyMeal" class="form-control form-control-sm" step="0.01" min="0" value="{{ $ticket->standby_meal ?? 0 }}">
                </div>
                <button type="button" class="btn btn-outline-primary btn-sm w-100" id="btnUpdateClaim">
                    <i class="bi bi-save me-1"></i> Update Claim
                </button>
                @else
                <div class="row g-2">
                    <div class="col-6"><small class="text-muted">Mileage</small><p class="mb-1">{{ number_format($ticket->mileage ?? 0, 2) }} km</p></div>
                    <div class="col-6"><small class="text-muted">Toll</small><p class="mb-1">RM {{ number_format($ticket->toll ?? 0, 2) }}</p></div>
                    <div class="col-6"><small class="text-muted">Standby/Meal</small><p class="mb-1">RM {{ number_format($ticket->standby_meal ?? 0, 2) }}</p></div>
                    <div class="col-6"><small class="text-muted">Total Claim</small><p class="mb-1 fw-bold text-danger">RM {{ number_format($ticket->total_claim_amount ?? 0, 2) }}</p></div>
                </div>
                <div class="mt-2">
                    <small class="text-muted fst-italic"><i class="bi bi-lock me-1"></i>Claim has been verified — amounts are locked and cannot be modified.</small>
                </div>
                @endif
            </div>
        </div>

        {{-- Delete --}}
        @if($ticket->status === 'open')
        <div class="card border-danger">
            <div class="card-body text-center">
                <button type="button" class="btn btn-outline-danger btn-sm" id="btnDeleteTicket">
                    <i class="bi bi-trash me-1"></i> Delete Ticket
                </button>
            </div>
        </div>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    const ticketId = {{ $ticket->id }};
    const currentStatus = '{{ $ticket->status }}';

    // ── Select2 Initialization ──
    $('#reassign_supervisor_id').select2({ theme: 'bootstrap-5', placeholder: '-- Select Supervisor --', allowClear: true });
    $('#technician_id').select2({ theme: 'bootstrap-5', placeholder: '-- Select Technician --', allowClear: true });

    // ══════════════════════════════════════════════════════════
    // CHANGE #2: Supervisor Reassignment
    // ══════════════════════════════════════════════════════════

    // Preview supervisor type on selection change
    $('#reassign_supervisor_id').on('change.select2', function() {
        var selected = $(this).find(':selected');
        var supType = selected.data('type');
        var mileage = selected.data('mileage');
        var $preview = $('#supervisorTypePreview');
        var $text = $('#supervisorTypeText');

        if (!$(this).val()) {
            $preview.hide();
            // Reset to server-rendered initial state
            var currentIsExternal = {{ $isExternal ? 'true' : 'false' }};
            if (currentIsExternal) {
                $('#technicianAssignSection').hide();
                $('#technicianExternalMsg').show();
            } else {
                $('#technicianAssignSection').show();
                $('#technicianExternalMsg').hide();
                // Reload original supervisor's technicians
                var originalSupId = {{ $ticket->supervisor_id ?? 'null' }};
                if (originalSupId) {
                    var $techSelect = $('#technician_id');
                    if ($techSelect.hasClass('select2-hidden-accessible')) {
                        $techSelect.select2('destroy');
                    }
                    $techSelect.html('<option value="">Loading...</option>');
                    $.ajax({
                        url: '{{ route("admin.tickets.ajax.technicians") }}',
                        method: 'GET',
                        data: { supervisor_id: originalSupId },
                        success: function(technicians) {
                            $techSelect.html('<option value="">-- Select Technician --</option>');
                            $.each(technicians, function(i, tech) {
                                var sel = (tech.id == {{ $ticket->technician_id ?? 'null' }}) ? ' selected' : '';
                                $techSelect.append('<option value="' + tech.id + '"' + sel + '>' + tech.name + '</option>');
                            });
                            $techSelect.select2({ theme: 'bootstrap-5', placeholder: '-- Select Technician --', allowClear: true });
                        }
                    });
                }
            }
            return;
        }

        if (supType === 'external') {
            $text.html('<i class="bi bi-person-badge text-warning me-1"></i><span class="text-warning">External supervisor — will work on this ticket directly (no technician). Mileage rate: RM ' + parseFloat(mileage || 0).toFixed(2) + '/km</span>');
            // HIDE technician section when external supervisor is selected
            $('#technicianAssignSection').hide();
            $('#technicianExternalMsg').show();
        } else {
            $text.html('<i class="bi bi-people text-info me-1"></i><span class="text-info">Internal supervisor — technician can be assigned after reassignment. Mileage rate: RM ' + parseFloat(mileage || 0).toFixed(2) + '/km</span>');
            // SHOW technician section and LOAD that supervisor's team technicians
            $('#technicianAssignSection').show();
            $('#technicianExternalMsg').hide();

            // AJAX fetch technicians under the selected internal supervisor
            var selectedSupId = $(this).val();
            var $techSelect = $('#technician_id');

            // Destroy Select2, clear options, show loading
            if ($techSelect.hasClass('select2-hidden-accessible')) {
                $techSelect.select2('destroy');
            }
            $techSelect.html('<option value="">Loading...</option>');

            $.ajax({
                url: '{{ route("admin.tickets.ajax.technicians") }}',
                method: 'GET',
                data: { supervisor_id: selectedSupId },
                success: function(technicians) {
                    $techSelect.html('<option value="">-- Select Technician --</option>');
                    if (technicians.length > 0) {
                        $.each(technicians, function(i, tech) {
                            $techSelect.append('<option value="' + tech.id + '">' + tech.name + '</option>');
                        });
                        // Show the assign section with populated dropdown
                        $techSelect.closest('#technicianAssignSection').find('.alert-warning').hide();
                        $techSelect.closest('.mb-2').show();
                        $('#btnAssignTechnician').show();
                    } else {
                        // No technicians under this supervisor
                        $techSelect.html('<option value="">-- No technicians available --</option>');
                    }
                    // Re-init Select2
                    $techSelect.select2({ theme: 'bootstrap-5', placeholder: '-- Select Technician --', allowClear: true });
                },
                error: function() {
                    $techSelect.html('<option value="">-- Failed to load --</option>');
                    $techSelect.select2({ theme: 'bootstrap-5', placeholder: '-- Select Technician --', allowClear: true });
                }
            });
        }
        $preview.show();
    });

    // Reassign Supervisor Button
    $('#btnReassignSupervisor').on('click', function() {
        var supervisorId = $('#reassign_supervisor_id').val();
        var remarks = $('#reassign_supervisor_remarks').val();

        if (!supervisorId) {
            showToast('Please select a supervisor.', 'warning');
            return;
        }

        if (supervisorId == {{ $ticket->supervisor_id ?? 'null' }}) {
            showToast('Please select a different supervisor.', 'warning');
            return;
        }

        confirmAction(
            'Reassign Supervisor?',
            'This will change the supervisor, reset the technician, and recalculate pricing. Continue?',
            function() {
                showLoading();
                $.ajax({
                    url: '{{ route("admin.tickets.reassign-supervisor", $ticket->id) }}',
                    method: 'POST',
                    data: { supervisor_id: supervisorId, remarks: remarks },
                    success: function(res) {
                        hideLoading();
                        if (res.success) {
                            showToast(res.message);
                            setTimeout(function() { location.reload(); }, 1000);
                        } else {
                            showToast(res.message || 'Failed to reassign supervisor.', 'error');
                        }
                    },
                    error: function(xhr) {
                        hideLoading();
                        var msg = xhr.responseJSON?.message || 'Failed to reassign supervisor.';
                        showToast(msg, 'error');
                    }
                });
            }
        );
    });

    // ══════════════════════════════════════════════════════════
    // Technician Assignment / Reassignment
    // ══════════════════════════════════════════════════════════
    $('#btnAssignTechnician').on('click', function() {
        var techId = $('#technician_id').val();
        var remarks = $('#assign_tech_remarks').val();

        if (!techId) {
            showToast('Please select a technician.', 'warning');
            return;
        }

        var isReassign = {{ $ticket->technician_id ? 'true' : 'false' }};
        var url = isReassign
            ? '{{ route("admin.tickets.reassign", $ticket->id) }}'
            : '{{ route("admin.tickets.assign", $ticket->id) }}';
        var actionText = isReassign ? 'Reassign Technician?' : 'Assign Technician?';

        confirmAction(actionText, 'This action will update the technician assignment.', function() {
            showLoading();
            $.ajax({
                url: url,
                method: 'POST',
                data: { technician_id: techId, remarks: remarks },
                success: function(res) {
                    hideLoading();
                    if (res.success) {
                        showToast(res.message);
                        setTimeout(function() { location.reload(); }, 1000);
                    } else {
                        showToast(res.message || 'Failed.', 'error');
                    }
                },
                error: function(xhr) {
                    hideLoading();
                    showToast(xhr.responseJSON?.message || 'Failed.', 'error');
                }
            });
        });
    });

    // ══════════════════════════════════════════════════════════
    // Status Change
    // ══════════════════════════════════════════════════════════
    $('#newStatus').on('change', function() {
        var status = $(this).val();
        $('#scheduledFields').toggle(status === 'scheduled');

        // CHANGE #3: Old Router ID field only when transitioning TO in_progress
        // AND current ticket is in a state that allows it
        var showOldRouter = (status === 'in_progress') || (currentStatus === 'in_progress');
        $('#oldRouterIdField').toggle(showOldRouter);

        // Proof fields
        var proofStatuses = ['scheduled','done_success','done_fail'];
        if (proofStatuses.includes(status)) {
            var proofTypes = {
                'scheduled': [['whatsapp_screenshot','WhatsApp Screenshot'],['call_log_screenshot','Call Log Screenshot']],
                'done_success': [['test_slip','Test Slip Image']],
                'done_fail': [['service_form','Service Form Image']]
            };
            var html = '';
            (proofTypes[status] || []).forEach(function(pt) {
                html += '<div class="mb-2"><label class="form-label small">' + pt[1] + '</label>';
                html += '<input type="file" class="form-control form-control-sm proof-file" name="proof_files[' + pt[0] + ']" accept=".jpg,.jpeg,.png,.pdf"></div>';
            });
            $('#proofFields').html(html).show();
        } else {
            $('#proofFields').hide().html('');
        }
    });

    $('#btnChangeStatus').on('click', function() {
        var status = $('#newStatus').val();
        if (!status) { showToast('Please select a status.', 'warning'); return; }

        if (status === 'scheduled') {
            if (!$('#rescheduleReason').val()) { showToast('Reschedule reason is required.', 'warning'); return; }
            if (!$('#scheduledDate').val()) { showToast('Scheduled date is required.', 'warning'); return; }
        }

        var formData = new FormData();
        formData.append('status', status);
        formData.append('remarks', $('#statusRemarks').val());
        if (status === 'scheduled') {
            formData.append('reschedule_reason', $('#rescheduleReason').val());
            formData.append('scheduled_date', $('#scheduledDate').val());
        }
        // CHANGE #3: Old terminal ID only when ticket is in_progress
        if ($('#statusOldTerminalId').val() && (currentStatus === 'in_progress' || status === 'in_progress')) {
            formData.append('old_terminal_id', $('#statusOldTerminalId').val());
        }
        // Proof files
        $('.proof-file').each(function() {
            if (this.files[0]) {
                formData.append($(this).attr('name'), this.files[0]);
            }
        });

        showLoading();
        $.ajax({
            url: '{{ route("admin.tickets.change-status", $ticket->id) }}',
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(res) {
                hideLoading();
                if (res.success) {
                    showToast(res.message);
                    setTimeout(function() { location.reload(); }, 1000);
                } else {
                    showToast(res.message || 'Failed.', 'error');
                }
            },
            error: function(xhr) {
                hideLoading();
                showToast(xhr.responseJSON?.message || 'Status change failed.', 'error');
            }
        });
    });

    // ══════════════════════════════════════════════════════════
    // CHANGE #3: Old Router ID Save (standalone card)
    // ══════════════════════════════════════════════════════════
    $('#btnSaveOldRouterId').on('click', function() {
        var val = $('#old_terminal_id_input').val();
        showLoading();
        $.ajax({
            url: '{{ route("admin.tickets.update-old-router", $ticket->id) }}',
            method: 'POST',
            data: { old_terminal_id: val },
            success: function(res) {
                hideLoading();
                showToast(res.success ? res.message : (res.message || 'Failed.'), res.success ? 'success' : 'error');
                if (res.success) setTimeout(function() { location.reload(); }, 1000);
            },
            error: function(xhr) {
                hideLoading();
                showToast(xhr.responseJSON?.message || 'Failed to update.', 'error');
            }
        });
    });

    // ── Claim Update ──
    $('#btnUpdateClaim').on('click', function() {
        showLoading();
        $.ajax({
            url: '{{ route("admin.tickets.update-claim", $ticket->id) }}',
            method: 'POST',
            data: {
                mileage: $('#claimMileage').val(),
                mileage_remarks: $('#claimMileageRemarks').val(),
                toll: $('#claimToll').val(),
                standby_meal: $('#claimStandbyMeal').val()
            },
            success: function(res) {
                hideLoading();
                if (res.success) { showToast(res.message); setTimeout(function() { location.reload(); }, 1000); }
                else { showToast(res.message || 'Failed.', 'error'); }
            },
            error: function(xhr) { hideLoading(); showToast(xhr.responseJSON?.message || 'Failed.', 'error'); }
        });
    });

    // ── Comment ──
    $('#btnAddComment').on('click', function() {
        var comment = $('#commentInput').val().trim();
        if (!comment) { showToast('Please enter a comment.', 'warning'); return; }
        $.ajax({
            url: '{{ route("admin.tickets.comment", $ticket->id) }}',
            method: 'POST',
            data: { comment: comment },
            success: function(res) {
                if (res.success) {
                    $('#noCommentsText').hide();
                    var c = res.comment;
                    var html = '<div class="d-flex mb-3">';
                    html += '<div class="flex-shrink-0 me-3"><div class="rounded-circle bg-success text-white d-flex align-items-center justify-content-center" style="width:36px;height:36px;font-size:0.8rem;">' + c.user_name.substring(0,2).toUpperCase() + '</div></div>';
                    html += '<div class="flex-grow-1"><div class="fw-semibold">' + c.user_name + ' <span class="badge bg-light text-dark ms-1">' + c.user_role + '</span> <small class="text-muted ms-2">' + c.created_at + '</small></div>';
                    html += '<p class="mb-0 mt-1">' + c.comment + '</p></div></div>';
                    $('#commentsList').prepend(html);
                    $('#commentInput').val('');
                    showToast('Comment added.');
                }
            },
            error: function() { showToast('Failed to add comment.', 'error'); }
        });
    });

    $('#commentInput').on('keypress', function(e) { if (e.which === 13) $('#btnAddComment').click(); });

    // ── Delete Ticket ──
    $('#btnDeleteTicket').on('click', function() {
        confirmAction('Delete Ticket?', 'This action cannot be undone.', function() {
            showLoading();
            $.ajax({
                url: '{{ route("admin.tickets.destroy", $ticket->id) }}',
                method: 'DELETE',
                success: function(res) {
                    hideLoading();
                    if (res.success) {
                        showToast(res.message);
                        setTimeout(function() { window.location.href = '{{ route("admin.tickets.index") }}'; }, 1000);
                    }
                },
                error: function() { hideLoading(); showToast('Failed to delete.', 'error'); }
            });
        });
    });
});
</script>
@endpush
