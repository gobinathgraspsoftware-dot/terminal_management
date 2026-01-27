<!-- Bulk Assignment Modal -->
<div class="modal fade" id="bulkAssignModal" tabindex="-1" aria-labelledby="bulkAssignModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="bulkAssignModalLabel">
                    <i class="fas fa-users me-2"></i>Bulk Assign Technicians
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="bulkAssignForm">
                @csrf
                <div class="modal-body">
                    <input type="hidden" id="bulk_technician_ids" name="technician_ids">
                    
                    <!-- Selection Summary -->
                    <div class="alert alert-info mb-4">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Selected Technicians:</strong> 
                        <span id="selected_count" class="badge bg-primary">0</span>
                    </div>

                    <!-- Supervisor Selection -->
                    <div class="mb-4">
                        <label for="bulk_supervisor_id" class="form-label fw-bold">
                            Assign All To <span class="text-danger">*</span>
                        </label>
                        <select class="form-select form-select-lg" 
                                id="bulk_supervisor_id" 
                                name="supervisor_id" 
                                required>
                            <option value="">Select Supervisor or Independent</option>
                            <option value="independent" class="text-warning fw-bold">
                                🔓 Make All Independent
                            </option>
                            <optgroup label="Available Supervisors">
                                @foreach($supervisors as $supervisor)
                                    <option value="{{ $supervisor->id }}">
                                        {{ $supervisor->name }} 
                                        <span class="text-muted">
                                            (Current: {{ $supervisor->technicians_count }} technicians)
                                        </span>
                                    </option>
                                @endforeach
                            </optgroup>
                        </select>
                        <small class="text-muted d-block mt-2">
                            <i class="fas fa-lightbulb me-1"></i>
                            All selected technicians will be assigned to the chosen supervisor
                        </small>
                    </div>

                    <!-- Preview Section -->
                    <div id="bulk_preview" class="d-none">
                        <div class="card bg-light">
                            <div class="card-header">
                                <h6 class="mb-0">
                                    <i class="fas fa-eye me-2"></i>Assignment Preview
                                </h6>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <strong>Action:</strong>
                                        <p id="preview_action" class="mb-2"></p>
                                    </div>
                                    <div class="col-md-6">
                                        <strong>Technicians Affected:</strong>
                                        <p id="preview_count" class="mb-2"></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Bulk Assignment Notes -->
                    <div class="mt-4">
                        <label for="bulk_notes" class="form-label">Bulk Assignment Notes (Optional)</label>
                        <textarea class="form-control" 
                                  id="bulk_notes" 
                                  name="notes" 
                                  rows="3" 
                                  placeholder="Add notes about this bulk assignment operation..."></textarea>
                    </div>

                    <!-- Warning for Reassignments -->
                    <div id="reassignment_warning" class="alert alert-warning mt-4 d-none">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong>Note:</strong> Some selected technicians may already be assigned to other supervisors. 
                        This operation will reassign them.
                    </div>

                    <!-- Confirmation Checkbox for Large Batches -->
                    <div id="confirmation_checkbox" class="form-check mt-4 d-none">
                        <input class="form-check-input" 
                               type="checkbox" 
                               id="confirm_bulk_assign">
                        <label class="form-check-label" for="confirm_bulk_assign">
                            I confirm that I want to perform this bulk assignment operation
                        </label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times"></i> Cancel
                    </button>
                    <button type="submit" class="btn btn-primary" id="submitBulkAssignment">
                        <i class="fas fa-check-double"></i> Assign All Technicians
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
$(document).ready(function() {
    // Initialize Select2 for bulk supervisor dropdown
    $('#bulk_supervisor_id').select2({
        theme: 'bootstrap-5',
        dropdownParent: $('#bulkAssignModal'),
        placeholder: 'Select Supervisor or Independent',
        allowClear: true
    });

    // Update preview when supervisor is selected
    $('#bulk_supervisor_id').on('change', function() {
        const supervisorId = $(this).val();
        const selectedCount = JSON.parse($('#bulk_technician_ids').val() || '[]').length;
        
        if (supervisorId && selectedCount > 0) {
            let supervisorName = 'Independent';
            
            if (supervisorId !== 'independent') {
                const option = $(this).find('option:selected');
                supervisorName = option.text().split('(')[0].trim();
            }
            
            $('#preview_action').html(`Assign to <strong>${supervisorName}</strong>`);
            $('#preview_count').html(`<strong>${selectedCount}</strong> technician(s)`);
            $('#bulk_preview').removeClass('d-none');
            
            // Show confirmation for large batches
            if (selectedCount >= 10) {
                $('#confirmation_checkbox').removeClass('d-none');
                $('#confirm_bulk_assign').prop('required', true);
            } else {
                $('#confirmation_checkbox').addClass('d-none');
                $('#confirm_bulk_assign').prop('required', false);
            }
        } else {
            $('#bulk_preview').addClass('d-none');
        }
    });

    // Handle bulk assignment form submission
    $('#bulkAssignForm').on('submit', function(e) {
        e.preventDefault();
        
        const technicianIds = JSON.parse($('#bulk_technician_ids').val() || '[]');
        const supervisorId = $('#bulk_supervisor_id').val();
        const notes = $('#bulk_notes').val();

        if (technicianIds.length === 0) {
            showAlert('warning', 'No technicians selected for bulk assignment');
            return;
        }

        // Disable submit button
        $('#submitBulkAssignment').prop('disabled', true)
            .html('<i class="fas fa-spinner fa-spin"></i> Processing...');

        $.ajax({
            url: '/teams/bulk-assign',
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                technician_ids: technicianIds,
                supervisor_id: supervisorId === 'independent' ? null : supervisorId,
                notes: notes
            },
            success: function(response) {
                // Show success message
                showAlert('success', response.message);
                
                // Close modal
                $('#bulkAssignModal').modal('hide');
                
                // Reset form
                $('#bulkAssignForm')[0].reset();
                $('#bulk_supervisor_id').val('').trigger('change');
                $('#bulk_preview').addClass('d-none');
                
                // Reload page after short delay
                setTimeout(function() {
                    location.reload();
                }, 2000);
            },
            error: function(xhr) {
                // Show error message
                let errorMessage = 'Failed to perform bulk assignment';
                
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                } else if (xhr.responseJSON && xhr.responseJSON.errors) {
                    const errors = Object.values(xhr.responseJSON.errors).flat();
                    errorMessage = errors.join('<br>');
                }
                
                showAlert('danger', errorMessage);
                
                // Re-enable submit button
                $('#submitBulkAssignment').prop('disabled', false)
                    .html('<i class="fas fa-check-double"></i> Assign All Technicians');
            }
        });
    });

    // Reset form when modal is closed
    $('#bulkAssignModal').on('hidden.bs.modal', function() {
        $('#bulkAssignForm')[0].reset();
        $('#bulk_supervisor_id').val('').trigger('change');
        $('#bulk_preview').addClass('d-none');
        $('#confirmation_checkbox').addClass('d-none');
        $('#reassignment_warning').addClass('d-none');
        $('#submitBulkAssignment').prop('disabled', false)
            .html('<i class="fas fa-check-double"></i> Assign All Technicians');
    });

    // Validate selection when modal is shown
    $('#bulkAssignModal').on('show.bs.modal', function() {
        const technicianIds = JSON.parse($('#bulk_technician_ids').val() || '[]');
        
        if (technicianIds.length === 0) {
            showAlert('warning', 'Please select at least one technician');
            return false;
        }

        // Check if any are already assigned
        let hasAssigned = false;
        technicianIds.forEach(function(id) {
            const tech = allTechnicians.find(t => t.id == id);
            if (tech && tech.supervisor_id) {
                hasAssigned = true;
            }
        });

        if (hasAssigned) {
            $('#reassignment_warning').removeClass('d-none');
        }
    });
});
</script>

<style>
    #bulkAssignModal .modal-header {
        border-bottom: none;
    }
    
    #bulkAssignModal .select2-container--bootstrap-5 .select2-selection--single {
        height: 48px;
        font-size: 1.1rem;
    }
    
    #bulk_preview {
        animation: fadeIn 0.3s ease-in;
    }
    
    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(-10px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
</style>
@endpush
