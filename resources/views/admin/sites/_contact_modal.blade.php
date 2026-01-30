{{-- Contact Management Modal --}}

<div class="modal fade" id="contactModal" tabindex="-1" aria-labelledby="contactModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="contactModalLabel">Add New Contact</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="contact-form">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="contact_name" class="form-label">Contact Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="contact_name" name="contact_name" required>
                    </div>

                    <div class="mb-3">
                        <label for="contact_title" class="form-label">Job Title</label>
                        <input type="text" class="form-control" id="contact_title" name="contact_title" 
                               placeholder="e.g. Branch Manager">
                    </div>

                    <div class="mb-3">
                        <label for="contact_phone" class="form-label">Phone Number</label>
                        <input type="text" class="form-control" id="contact_phone" name="contact_phone" 
                               placeholder="+60123456789">
                    </div>

                    <div class="mb-3">
                        <label for="contact_email" class="form-label">Email Address</label>
                        <input type="email" class="form-control" id="contact_email" name="contact_email" 
                               placeholder="contact@example.com">
                    </div>

                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="is_primary" name="is_primary" value="1">
                            <label class="form-check-label" for="is_primary">
                                Set as Primary Contact
                            </label>
                            <small class="form-text text-muted d-block">
                                Setting this contact as primary will unset other primary contacts.
                            </small>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="contact_notes" class="form-label">Notes</label>
                        <textarea class="form-control" id="contact_notes" name="notes" rows="2" 
                                  placeholder="Enter any additional notes"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle me-1"></i> Cancel
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-circle me-1"></i> Save Contact
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
