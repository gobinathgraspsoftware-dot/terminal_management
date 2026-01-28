{{-- Reassign Modal --}}
<div class="modal fade" id="reassignModal" tabindex="-1">
    <div class="modal-dialog"><div class="modal-content">
        <div class="modal-header"><h5 class="modal-title"><i class="fas fa-exchange-alt me-2"></i>Reassign Technician</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <form id="reassignForm">
            <div class="modal-body">
                <p>Reassigning: <strong id="reassignTechnicianName"></strong></p>
                <input type="hidden" id="reassignTechnicianId" name="technician_id">
                <div class="mb-3"><label class="form-label">Select Supervisor</label>
                    <select name="supervisor_id" id="reassignSupervisorSelect" class="form-select">
                        <option value="">-- Independent --</option>
                        @foreach($supervisors as $supervisor)<option value="{{ $supervisor->id }}">{{ $supervisor->name }} ({{ $supervisor->technicians_count }})</option>@endforeach
                    </select>
                </div>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> Save</button></div>
        </form>
    </div></div>
</div>

{{-- Bulk Assign Modal --}}
<div class="modal fade" id="bulkAssignModal" tabindex="-1">
    <div class="modal-dialog"><div class="modal-content">
        <div class="modal-header"><h5 class="modal-title"><i class="fas fa-users-cog me-2"></i>Bulk Assign</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <form id="bulkAssignForm">
            <div class="modal-body">
                <div class="alert alert-info"><i class="fas fa-info-circle me-2"></i>Select technicians from table view first.</div>
                <div class="mb-3"><label class="form-label">Assign to Supervisor</label>
                    <select name="supervisor_id" id="bulkSupervisorSelect" class="form-select">
                        <option value="">-- Independent --</option>
                        @foreach($supervisors as $supervisor)<option value="{{ $supervisor->id }}">{{ $supervisor->name }}</option>@endforeach
                    </select>
                </div>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-primary"><i class="fas fa-check me-1"></i> Assign</button></div>
        </form>
    </div></div>
</div>

{{-- Assign Independent Modal --}}
<div class="modal fade" id="assignIndependentModal" tabindex="-1">
    <div class="modal-dialog modal-lg"><div class="modal-content">
        <div class="modal-header"><h5 class="modal-title"><i class="fas fa-user-plus me-2"></i>Assign Independent Technicians</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <form id="assignIndependentForm">
            <div class="modal-body">
                <div class="mb-3"><label class="form-label">Select Technicians</label>
                    <select name="technician_ids[]" id="independentTechnicianSelect" class="form-select select2" multiple>
                        @foreach($independentTechnicians as $t)<option value="{{ $t->id }}">{{ $t->name }} ({{ $t->employee_id }})</option>@endforeach
                    </select>
                </div>
                <div class="mb-3"><label class="form-label">Assign to Supervisor</label>
                    <select name="supervisor_id" id="independentSupervisorSelect" class="form-select" required>
                        <option value="">-- Select --</option>
                        @foreach($supervisors as $supervisor)<option value="{{ $supervisor->id }}">{{ $supervisor->name }}</option>@endforeach
                    </select>
                </div>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-primary"><i class="fas fa-check me-1"></i> Assign</button></div>
        </form>
    </div></div>
</div>

{{-- Member Detail Modal --}}
<div class="modal fade" id="memberDetailModal" tabindex="-1">
    <div class="modal-dialog modal-lg"><div class="modal-content">
        <div class="modal-header"><h5 class="modal-title"><i class="fas fa-user me-2"></i>Member Details</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
            <div class="row">
                <div class="col-md-6">
                    <table class="table table-sm">
                        <tr><th width="40%">Name</th><td id="memberDetailName">-</td></tr>
                        <tr><th>Employee ID</th><td id="memberDetailEmployee">-</td></tr>
                        <tr><th>Email</th><td id="memberDetailEmail">-</td></tr>
                        <tr><th>Phone</th><td id="memberDetailPhone">-</td></tr>
                        <tr><th>Status</th><td id="memberDetailStatus">-</td></tr>
                        <tr><th>Supervisor</th><td id="memberDetailSupervisor">-</td></tr>
                    </table>
                </div>
                <div class="col-md-6">
                    <div class="card bg-light"><div class="card-body">
                        <h6>Performance</h6>
                        <div class="row text-center">
                            <div class="col-4"><h4 id="memberDetailTotalJobs" class="text-primary mb-0">0</h4><small>Total</small></div>
                            <div class="col-4"><h4 id="memberDetailCompletedJobs" class="text-success mb-0">0</h4><small>Completed</small></div>
                            <div class="col-4"><h4 id="memberDetailPendingJobs" class="text-warning mb-0">0</h4><small>Pending</small></div>
                        </div>
                    </div></div>
                </div>
            </div>
        </div>
        <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button></div>
    </div></div>
</div>

@push('scripts')
<script>
$(document).ready(function() {
    $('#independentTechnicianSelect').select2({ theme: 'bootstrap-5', placeholder: 'Select technicians...', dropdownParent: $('#assignIndependentModal') });
    
    $('#assignIndependentForm').on('submit', function(e) { e.preventDefault();
        var ids = $('#independentTechnicianSelect').val(), supId = $('#independentSupervisorSelect').val();
        if (!ids || ids.length === 0) { showToast('warning', 'Select technicians'); return; }
        if (!supId) { showToast('warning', 'Select supervisor'); return; }
        $.ajax({ url: '{{ route("admin.teams.bulk-assign") }}', method: 'POST', data: { technician_ids: ids, supervisor_id: supId },
            success: function(response) { $('#assignIndependentModal').modal('hide'); showToast('success', response.message); setTimeout(function() { location.reload(); }, 1000); },
            error: function(xhr) { showToast('error', xhr.responseJSON?.message || 'Failed'); }
        });
    });
});
</script>
@endpush
