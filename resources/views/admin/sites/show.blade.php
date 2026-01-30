@extends('layouts.app')

@section('title', 'Site Details - ' . $site->site_code)

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">{{ $site->site_name }}</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.sites.index') }}">Sites</a></li>
                    <li class="breadcrumb-item active">{{ $site->site_code }}</li>
                </ol>
            </nav>
        </div>
        <div>
            @can('edit_sites')
            <a href="{{ route('admin.sites.edit', $site) }}" class="btn btn-warning me-2">
                <i class="bi bi-pencil me-1"></i> Edit
            </a>
            @endcan
            <a href="{{ route('admin.sites.index') }}" class="btn btn-secondary">
                <i class="bi bi-arrow-left me-1"></i> Back to List
            </a>
        </div>
    </div>

    <div class="row">
        <!-- Site Info Card -->
        <div class="col-lg-4 mb-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <i class="bi bi-info-circle me-2"></i>Site Information
                    </h5>
                </div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-5">Site Code:</dt>
                        <dd class="col-sm-7"><strong>{{ $site->site_code }}</strong></dd>

                        <dt class="col-sm-5">Client:</dt>
                        <dd class="col-sm-7">
                            @if($site->client)
                                <a href="{{ route('admin.clients.show', $site->client) }}">
                                    {{ $site->client->client_name }}
                                </a>
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </dd>

                        <dt class="col-sm-5">Status:</dt>
                        <dd class="col-sm-7">
                            @if($site->status === 'active')
                                <span class="badge bg-success">Active</span>
                            @else
                                <span class="badge bg-secondary">Inactive</span>
                            @endif
                        </dd>

                        <dt class="col-sm-5 border-top pt-2">Address:</dt>
                        <dd class="col-sm-7 border-top pt-2">{{ $site->full_address }}</dd>

                        @if($site->operating_hours)
                        <dt class="col-sm-5">Operating Hours:</dt>
                        <dd class="col-sm-7">{{ $site->operating_hours }}</dd>
                        @endif

                        @if($site->latitude && $site->longitude)
                        <dt class="col-sm-5 border-top pt-2">GPS:</dt>
                        <dd class="col-sm-7 border-top pt-2">
                            {{ $site->latitude }}, {{ $site->longitude }}
                            <br>
                            <a href="https://www.google.com/maps?q={{ $site->latitude }},{{ $site->longitude }}" 
                               target="_blank" class="btn btn-sm btn-outline-primary mt-1">
                                <i class="bi bi-map me-1"></i> View on Map
                            </a>
                        </dd>
                        @endif
                    </dl>

                    @if($site->notes)
                    <div class="alert alert-info mt-3">
                        <strong>Notes:</strong><br>
                        {{ $site->notes }}
                    </div>
                    @endif
                </div>
            </div>

            <!-- PIC Card -->
            @if($site->pic_name || $site->pic_phone || $site->pic_email)
            <div class="card border-0 shadow-sm mt-3">
                <div class="card-header bg-secondary text-white">
                    <h6 class="mb-0">
                        <i class="bi bi-person me-2"></i>Person In Charge
                    </h6>
                </div>
                <div class="card-body">
                    @if($site->pic_name)
                    <p class="mb-1"><strong>{{ $site->pic_name }}</strong></p>
                    @endif
                    @if($site->pic_phone)
                    <p class="mb-1">
                        <i class="bi bi-telephone me-1"></i>
                        <a href="tel:{{ $site->pic_phone }}">{{ $site->pic_phone }}</a>
                    </p>
                    @endif
                    @if($site->pic_email)
                    <p class="mb-0">
                        <i class="bi bi-envelope me-1"></i>
                        <a href="mailto:{{ $site->pic_email }}">{{ $site->pic_email }}</a>
                    </p>
                    @endif
                </div>
            </div>
            @endif
        </div>

        <!-- Tabs Section -->
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom">
                    <ul class="nav nav-tabs card-header-tabs" id="site-tabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="contacts-tab" data-bs-toggle="tab" 
                                    data-bs-target="#contacts" type="button" role="tab">
                                <i class="bi bi-people me-1"></i> Contacts
                                <span class="badge bg-primary ms-1">{{ $site->contacts->count() }}</span>
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="assets-tab" data-bs-toggle="tab" 
                                    data-bs-target="#assets" type="button" role="tab">
                                <i class="bi bi-box-seam me-1"></i> Installed Assets
                                <span class="badge bg-info ms-1">{{ $site->siteAssets->count() }}</span>
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="jobs-tab" data-bs-toggle="tab" 
                                    data-bs-target="#jobs" type="button" role="tab">
                                <i class="bi bi-clipboard-check me-1"></i> Job History
                            </button>
                        </li>
                    </ul>
                </div>
                <div class="card-body">
                    <div class="tab-content" id="site-tabs-content">
                        <!-- Contacts Tab -->
                        <div class="tab-pane fade show active" id="contacts" role="tabpanel">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h5>Site Contacts</h5>
                                @can('edit_sites')
                                <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#contactModal" onclick="addContact()">
                                    <i class="bi bi-plus-circle me-1"></i> Add Contact
                                </button>
                                @endcan
                            </div>

                            <div id="contacts-list">
                                @forelse($site->contacts as $contact)
                                <div class="card mb-2 contact-card" data-contact-id="{{ $contact->id }}">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div>
                                                <h6 class="mb-1">
                                                    {{ $contact->contact_name }}
                                                    @if($contact->is_primary)
                                                        <span class="badge bg-success ms-2">Primary</span>
                                                    @endif
                                                </h6>
                                                @if($contact->contact_title)
                                                    <p class="text-muted small mb-1">{{ $contact->contact_title }}</p>
                                                @endif
                                                @if($contact->contact_phone)
                                                    <p class="mb-1">
                                                        <i class="bi bi-telephone me-1"></i>
                                                        <a href="tel:{{ $contact->contact_phone }}">{{ $contact->contact_phone }}</a>
                                                    </p>
                                                @endif
                                                @if($contact->contact_email)
                                                    <p class="mb-0">
                                                        <i class="bi bi-envelope me-1"></i>
                                                        <a href="mailto:{{ $contact->contact_email }}">{{ $contact->contact_email }}</a>
                                                    </p>
                                                @endif
                                            </div>
                                            @can('edit_sites')
                                            <div class="btn-group">
                                                <button class="btn btn-sm btn-outline-warning" onclick="editContact({{ $contact->id }})">
                                                    <i class="bi bi-pencil"></i>
                                                </button>
                                                <button class="btn btn-sm btn-outline-danger" onclick="deleteContact({{ $contact->id }})">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </div>
                                            @endcan
                                        </div>
                                    </div>
                                </div>
                                @empty
                                <div class="alert alert-info">
                                    <i class="bi bi-info-circle me-2"></i>No contacts added yet.
                                </div>
                                @endforelse
                            </div>
                        </div>

                        <!-- Assets Tab -->
                        <div class="tab-pane fade" id="assets" role="tabpanel">
                            @include('admin.sites._asset_tab')
                        </div>

                        <!-- Jobs Tab -->
                        <div class="tab-pane fade" id="jobs" role="tabpanel">
                            @include('admin.sites._job_history_tab')
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Contact Modal -->
@include('admin.sites._contact_modal')
@endsection

@push('scripts')
<script>
let currentContactId = null;

function addContact() {
    currentContactId = null;
    $('#contactModalLabel').text('Add New Contact');
    $('#contact-form')[0].reset();
    $('#is_primary').prop('checked', false);
}

function editContact(contactId) {
    currentContactId = contactId;
    $('#contactModalLabel').text('Edit Contact');
    
    // Load contact data
    $.get(`/admin/sites/{{ $site->id }}/contacts/${contactId}`, function(response) {
        if (response.success) {
            const contact = response.contact;
            $('#contact_name').val(contact.contact_name);
            $('#contact_title').val(contact.contact_title);
            $('#contact_email').val(contact.contact_email);
            $('#contact_phone').val(contact.contact_phone);
            $('#is_primary').prop('checked', contact.is_primary);
            $('#contact_notes').val(contact.notes);
            
            $('#contactModal').modal('show');
        }
    });
}

function deleteContact(contactId) {
    if (confirm('Are you sure you want to delete this contact?')) {
        $.ajax({
            url: `/admin/sites/{{ $site->id }}/contacts/${contactId}`,
            type: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                if (response.success) {
                    $(`.contact-card[data-contact-id="${contactId}"]`).fadeOut(function() {
                        $(this).remove();
                    });
                    alert('Contact deleted successfully');
                }
            },
            error: function(xhr) {
                alert('Error: ' + (xhr.responseJSON?.message || 'Failed to delete contact'));
            }
        });
    }
}

$(document).ready(function() {
    // Contact form submission
    $('#contact-form').on('submit', function(e) {
        e.preventDefault();
        
        const form = $(this);
        const url = currentContactId 
            ? `/admin/sites/{{ $site->id }}/contacts/${currentContactId}`
            : `/admin/sites/{{ $site->id }}/contacts`;
        const method = currentContactId ? 'PUT' : 'POST';
        
        $.ajax({
            url: url,
            type: method,
            data: form.serialize(),
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                if (response.success) {
                    $('#contactModal').modal('hide');
                    alert('Contact saved successfully');
                    location.reload();
                }
            },
            error: function(xhr) {
                if (xhr.status === 422) {
                    const errors = xhr.responseJSON.errors;
                    // Handle validation errors
                    alert('Validation error: ' + Object.values(errors)[0][0]);
                } else {
                    alert('Error: ' + (xhr.responseJSON?.message || 'Failed to save contact'));
                }
            }
        });
    });
});
</script>
@endpush
