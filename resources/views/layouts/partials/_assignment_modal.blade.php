<!-- Assignment Modal -->
<div class="modal fade" id="assignmentModal" tabindex="-1" aria-labelledby="assignmentModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="assignmentModalLabel">Assign Technician</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="assignmentForm">
                @csrf
                <div class="modal-body">
                    <input type="hidden" id="technician_id" name="technician_id">
                    
                    <!-- Current Assignment -->
                    <div class="mb-3">
                        <label class="form-label fw-bold">Current Assignment</label>
                        <div id="current_assignment">
                            <span class="badge bg-secondary">Loading...</span>
                        </div>
                    </div>

                    <!-- New Assignment -->
                    <div class="mb-3">
                        <label for="supervisor_id" class="form-label fw-bold">
                            Assign To <span class="text-danger">*</span>
                        </label>
                        <select class="form-select" id="supervisor_id" name="supervisor_id" required>
                            <option value="">Select Supervisor or Independent</option>
                            <option value="independent" class="text-warning">
                                <i class="fas fa-user-circle"></i> Make Independent
                            </option>
                            <optgroup label="Supervisors">
                                @foreach($supervisors as $supervisor)
                                    <option value="{{ $supervisor->id }}">
                                        {{ $supervisor->name }} 
                                        ({{ $supervisor->technicians_count }} technicians)
                                    </option>
                                @endforeach
                            </optgroup>
                        </select>
                        <small class="text-muted">
                            Select "Make Independent" to remove supervisor assignment
                        </small>
                    </div>

                    <!-- Assignment Notes (Optional) -->
                    <div class="mb-3">
                        <label for="assignment_notes" class="form-label">Notes (Optional)</label>
                        <textarea class="form-control" 
                                  id="assignment_notes" 
                                  name="notes" 
                                  rows="3" 
                                  placeholder="Add any notes about this assignment..."></textarea>
                    </div>

                    <!-- Warning for Active Jobs -->
                    <div id="active_jobs_warning" class="alert alert-warning d-none">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong>Warning:</strong> This technician has active jobs. 
                        Consider completing them before reassignment.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times"></i> Cancel
                    </button>
                    <button type="submit" class="btn btn-primary" id="submitAssignment">
                        <i class="fas fa-check"></i> Assign Technician
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
$(document).ready(function() {
    // Initialize Select2 for supervisor dropdown
    $('#supervisor_id').select2({
        theme: 'bootstrap-5',
        dropdownParent: $('#assignmentModal'),
        placeholder: 'Select Supervisor or Independent',
        allowClear: true
    });

    // Handle form submission
    $('#assignmentForm').on('submit', function(e) {
        e.preventDefault();
        
        const technicianId = $('#technician_id').val();
        const supervisorId = $('#supervisor_id').val();
        const notes = $('#assignment_notes').val();

        // Disable submit button
        $('#submitAssignment').prop('disabled', true)
            .html('<i class="fas fa-spinner fa-spin"></i> Assigning...');

        $.ajax({
            url: '/teams/assign',
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                technician_id: technicianId,
                supervisor_id: supervisorId === 'independent' ? null : supervisorId,
                notes: notes
            },
            success: function(response) {
                // Show success message
                showAlert('success', response.message);
                
                // Close modal
                $('#assignmentModal').modal('hide');
                
                // Reset form
                $('#assignmentForm')[0].reset();
                $('#supervisor_id').val('').trigger('change');
                
                // Reload page after short delay
                setTimeout(function() {
                    location.reload();
                }, 1500);
            },
            error: function(xhr) {
                // Show error message
                let errorMessage = 'Failed to assign technician';
                
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                } else if (xhr.responseJSON && xhr.responseJSON.errors) {
                    const errors = Object.values(xhr.responseJSON.errors).flat();
                    errorMessage = errors.join('<br>');
                }
                
                showAlert('danger', errorMessage);
                
                // Re-enable submit button
                $('#submitAssignment').prop('disabled', false)
                    .html('<i class="fas fa-check"></i> Assign Technician');
            }
        });
    });

    // Reset form when modal is closed
    $('#assignmentModal').on('hidden.bs.modal', function() {
        $('#assignmentForm')[0].reset();
        $('#supervisor_id').val('').trigger('change');
        $('#active_jobs_warning').addClass('d-none');
        $('#submitAssignment').prop('disabled', false)
            .html('<i class="fas fa-check"></i> Assign Technician');
    });

    // Check for active jobs when technician is selected
    $('#technician_id').on('change', function() {
        const technicianId = $(this).val();
        
        if (technicianId) {
            // Find technician in allTechnicians array
            const technician = allTechnicians.find(t => t.id == technicianId);
            
            if (technician && technician.active_jobs > 0) {
                $('#active_jobs_warning').removeClass('d-none');
            } else {
                $('#active_jobs_warning').addClass('d-none');
            }
        }
    });
});

// Show alert function (if not already defined)
if (typeof showAlert !== 'function') {
    function showAlert(type, message) {
        const alertHtml = `
            <div class="alert alert-${type} alert-dismissible fade show position-fixed top-0 start-50 translate-middle-x mt-3" 
                 role="alert" 
                 style="z-index: 9999; min-width: 300px;">
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        `;
        
        $('body').append(alertHtml);
        
        // Auto-dismiss after 5 seconds
        setTimeout(function() {
            $('.alert').alert('close');
        }, 5000);
    }
}
</script>

<style>
    .select2-container--bootstrap-5 .select2-selection {
        min-height: 38px;
    }
</style>
@endpush
