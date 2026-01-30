{{-- 
    Multiple Contacts Component
    Usage: @include('components.contact-form', ['contacts' => $model->contacts ?? []])
    
    This component allows adding/removing multiple contacts dynamically
--}}

@php
    $contacts = $contacts ?? [];
    $fieldPrefix = $fieldPrefix ?? 'contacts';
@endphp

<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i class="bi bi-people me-2"></i>Contact Persons</h5>
        <button type="button" class="btn btn-sm btn-primary" id="addContactBtn">
            <i class="bi bi-plus-circle me-1"></i>Add Contact
        </button>
    </div>
    <div class="card-body">
        <div id="contactsContainer">
            @if(count($contacts) > 0)
                @foreach($contacts as $index => $contact)
                <div class="contact-row mb-3 p-3 border rounded" data-index="{{ $index }}">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label small">Contact Name</label>
                            <input type="text" 
                                   class="form-control" 
                                   name="{{ $fieldPrefix }}[{{ $index }}][name]" 
                                   value="{{ old($fieldPrefix.'.'.$index.'.name', $contact->name ?? '') }}" 
                                   placeholder="Full Name">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small">Designation</label>
                            <input type="text" 
                                   class="form-control" 
                                   name="{{ $fieldPrefix }}[{{ $index }}][designation]" 
                                   value="{{ old($fieldPrefix.'.'.$index.'.designation', $contact->designation ?? '') }}" 
                                   placeholder="e.g. Manager">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small">Email</label>
                            <input type="email" 
                                   class="form-control" 
                                   name="{{ $fieldPrefix }}[{{ $index }}][email]" 
                                   value="{{ old($fieldPrefix.'.'.$index.'.email', $contact->email ?? '') }}" 
                                   placeholder="email@example.com">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small">Phone</label>
                            <input type="text" 
                                   class="form-control" 
                                   name="{{ $fieldPrefix }}[{{ $index }}][phone]" 
                                   value="{{ old($fieldPrefix.'.'.$index.'.phone', $contact->phone ?? '') }}" 
                                   placeholder="+60123456789">
                        </div>
                        <div class="col-md-1">
                            <label class="form-label small">Primary</label>
                            <div class="form-check form-switch mt-2">
                                <input type="checkbox" 
                                       class="form-check-input primary-contact-check" 
                                       name="{{ $fieldPrefix }}[{{ $index }}][is_primary]" 
                                       value="1" 
                                       {{ old($fieldPrefix.'.'.$index.'.is_primary', $contact->is_primary ?? false) ? 'checked' : '' }}>
                            </div>
                        </div>
                        <div class="col-md-1 d-flex align-items-end">
                            <button type="button" class="btn btn-sm btn-danger remove-contact-btn w-100" title="Remove Contact">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    </div>
                </div>
                @endforeach
            @else
                <div class="text-center text-muted py-3">
                    <i class="bi bi-person-plus fs-1"></i>
                    <p class="mt-2">No contacts added yet. Click "Add Contact" to begin.</p>
                </div>
            @endif
        </div>
    </div>
</div>

@push('scripts')
<script>
$(document).ready(function() {
    let contactIndex = {{ count($contacts) }};
    
    // Contact row template
    function getContactTemplate(index) {
        return `
            <div class="contact-row mb-3 p-3 border rounded" data-index="${index}">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label small">Contact Name</label>
                        <input type="text" class="form-control" name="{{ $fieldPrefix }}[${index}][name]" placeholder="Full Name">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small">Designation</label>
                        <input type="text" class="form-control" name="{{ $fieldPrefix }}[${index}][designation]" placeholder="e.g. Manager">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small">Email</label>
                        <input type="email" class="form-control" name="{{ $fieldPrefix }}[${index}][email]" placeholder="email@example.com">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small">Phone</label>
                        <input type="text" class="form-control" name="{{ $fieldPrefix }}[${index}][phone]" placeholder="+60123456789">
                    </div>
                    <div class="col-md-1">
                        <label class="form-label small">Primary</label>
                        <div class="form-check form-switch mt-2">
                            <input type="checkbox" class="form-check-input primary-contact-check" name="{{ $fieldPrefix }}[${index}][is_primary]" value="1">
                        </div>
                    </div>
                    <div class="col-md-1 d-flex align-items-end">
                        <button type="button" class="btn btn-sm btn-danger remove-contact-btn w-100" title="Remove Contact">
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>
                </div>
            </div>
        `;
    }
    
    // Add contact
    $('#addContactBtn').click(function() {
        // Remove "no contacts" message if present
        $('#contactsContainer .text-center.text-muted').remove();
        
        // Add new contact row
        $('#contactsContainer').append(getContactTemplate(contactIndex));
        contactIndex++;
    });
    
    // Remove contact
    $(document).on('click', '.remove-contact-btn', function() {
        $(this).closest('.contact-row').remove();
        
        // Show "no contacts" message if no contacts left
        if ($('.contact-row').length === 0) {
            $('#contactsContainer').html(`
                <div class="text-center text-muted py-3">
                    <i class="bi bi-person-plus fs-1"></i>
                    <p class="mt-2">No contacts added yet. Click "Add Contact" to begin.</p>
                </div>
            `);
        }
    });
    
    // Ensure only one primary contact
    $(document).on('change', '.primary-contact-check', function() {
        if ($(this).is(':checked')) {
            // Uncheck all other primary checkboxes
            $('.primary-contact-check').not(this).prop('checked', false);
        }
    });
});
</script>
@endpush
