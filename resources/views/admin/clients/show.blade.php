@extends('layouts.app')

@section('title', 'Client Details')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">{{ $client->client_name }}</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.clients.index') }}">Clients</a></li>
                    <li class="breadcrumb-item active">{{ $client->client_code }}</li>
                </ol>
            </nav>
        </div>
        <div>
            @can('edit_clients')
            <a href="{{ route('admin.clients.edit', $client) }}" class="btn btn-primary me-2">
                <i class="bi bi-pencil me-1"></i> Edit Client
            </a>
            @endcan
            <a href="{{ route('admin.clients.index') }}" class="btn btn-secondary">
                <i class="bi bi-arrow-left me-1"></i> Back to List
            </a>
        </div>
    </div>

    <!-- Client Info Card -->
    <div class="row mb-4">
        <div class="col-md-8">
            <div class="card">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p class="mb-2"><strong>Client Code:</strong> {{ $client->client_code }}</p>
                            <p class="mb-2"><strong>Company:</strong> {{ $client->company_name ?? '-' }}</p>
                            <p class="mb-2"><strong>Partner:</strong> 
                                @if($client->partner)
                                <a href="{{ route('admin.partners.show', $client->partner) }}">
                                    {{ $client->partner->partner_name }}
                                </a>
                                @else
                                -
                                @endif
                            </p>
                        </div>
                        <div class="col-md-6">
                            <p class="mb-2"><strong>Status:</strong> {!! $client->status_badge !!}</p>
                            <p class="mb-2"><strong>Payment Terms:</strong> {{ $client->payment_terms }} days</p>
                            <p class="mb-2"><strong>Credit Limit:</strong> 
                                @if($client->credit_limit)
                                RM {{ number_format($client->credit_limit, 2) }}
                                @else
                                No Limit
                                @endif
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <h6 class="text-muted mb-3">Quick Stats</h6>
                    <p class="mb-2"><strong>Sites:</strong> {{ $statistics['total_sites'] }}</p>
                    <p class="mb-2"><strong>Jobs:</strong> {{ $statistics['total_jobs'] }}</p>
                    <p class="mb-2"><strong>Invoices:</strong> {{ $statistics['total_invoices'] }}</p>
                    <p class="mb-0"><strong>Outstanding:</strong> 
                        <span class="text-danger">RM {{ number_format($statistics['total_outstanding'], 2) }}</span>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabs -->
    <ul class="nav nav-tabs mb-4" id="clientTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="details-tab" data-bs-toggle="tab" data-bs-target="#details" type="button">
                <i class="bi bi-info-circle me-1"></i> Details
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="sites-tab" data-bs-toggle="tab" data-bs-target="#sites" type="button">
                <i class="bi bi-geo-alt me-1"></i> Sites ({{ $client->sites->count() }})
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="contacts-tab" data-bs-toggle="tab" data-bs-target="#contacts" type="button">
                <i class="bi bi-people me-1"></i> Contacts ({{ $client->contacts->count() }})
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="jobs-tab" data-bs-toggle="tab" data-bs-target="#jobs" type="button">
                <i class="bi bi-briefcase me-1"></i> Jobs ({{ $client->jobOrders->count() }})
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="invoices-tab" data-bs-toggle="tab" data-bs-target="#invoices" type="button">
                <i class="bi bi-receipt me-1"></i> Invoices ({{ $client->invoices->count() }})
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="aging-tab" data-bs-toggle="tab" data-bs-target="#aging" type="button">
                <i class="bi bi-clock-history me-1"></i> Aging
            </button>
        </li>
    </ul>

    <!-- Tab Content -->
    <div class="tab-content" id="clientTabsContent">
        <!-- Details Tab -->
        <div class="tab-pane fade show active" id="details" role="tabpanel">
            <div class="row">
                <div class="col-md-6">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Company Information</h5>
                        </div>
                        <div class="card-body">
                            <table class="table table-sm table-borderless">
                                <tr>
                                    <td width="40%"><strong>Client Code:</strong></td>
                                    <td>{{ $client->client_code }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Client Name:</strong></td>
                                    <td>{{ $client->client_name }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Company Name:</strong></td>
                                    <td>{{ $client->company_name ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Registration No:</strong></td>
                                    <td>{{ $client->registration_no ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Tax ID:</strong></td>
                                    <td>{{ $client->tax_id ?? '-' }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Billing Address</h5>
                        </div>
                        <div class="card-body">
                            @if($client->billing_address)
                                <p class="mb-1">{{ $client->billing_address }}</p>
                                <p class="mb-1">{{ $client->billing_postcode }} {{ $client->billing_city }}</p>
                                <p class="mb-1">{{ $client->billing_state }}</p>
                                <p class="mb-0">{{ $client->billing_country }}</p>
                            @else
                                <p class="text-muted mb-0">No address provided</p>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Financial Information</h5>
                        </div>
                        <div class="card-body">
                            <table class="table table-sm table-borderless">
                                <tr>
                                    <td width="40%"><strong>Payment Terms:</strong></td>
                                    <td>{{ $client->payment_terms }} days</td>
                                </tr>
                                <tr>
                                    <td><strong>Credit Limit:</strong></td>
                                    <td>
                                        @if($client->credit_limit)
                                        RM {{ number_format($client->credit_limit, 2) }}
                                        @else
                                        No Limit
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>Total Outstanding:</strong></td>
                                    <td class="text-danger"><strong>RM {{ number_format($statistics['total_outstanding'], 2) }}</strong></td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Primary Contact</h5>
                        </div>
                        <div class="card-body">
                            <table class="table table-sm table-borderless">
                                <tr>
                                    <td width="40%"><strong>Name:</strong></td>
                                    <td>{{ $client->pic_name ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Email:</strong></td>
                                    <td>{{ $client->pic_email ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Phone:</strong></td>
                                    <td>{{ $client->pic_phone ?? '-' }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    @if($client->notes)
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Notes</h5>
                        </div>
                        <div class="card-body">
                            <p class="mb-0">{{ $client->notes }}</p>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Sites Tab -->
        <div class="tab-pane fade" id="sites" role="tabpanel">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Client Sites</h5>
                </div>
                <div class="card-body">
                    @if($client->sites->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Site Code</th>
                                    <th>Site Name</th>
                                    <th>Address</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($client->sites as $site)
                                <tr>
                                    <td>{{ $site->site_code }}</td>
                                    <td>{{ $site->site_name }}</td>
                                    <td>{{ $site->city }}, {{ $site->state }}</td>
                                    <td>{!! $site->status_badge !!}</td>
                                    <td>
                                        <a href="{{ route('admin.sites.show', $site) }}" class="btn btn-sm btn-info">
                                            <i class="bi bi-eye"></i> View
                                        </a>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @else
                    <p class="text-muted mb-0">No sites found for this client.</p>
                    @endif
                </div>
            </div>
        </div>

        <!-- Contacts Tab -->
        <div class="tab-pane fade" id="contacts" role="tabpanel">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Client Contacts</h5>
                    @can('edit_clients')
                    <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addContactModal">
                        <i class="bi bi-plus-circle me-1"></i> Add Contact
                    </button>
                    @endcan
                </div>
                <div class="card-body">
                    @if($client->contacts->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Title</th>
                                    <th>Email</th>
                                    <th>Phone</th>
                                    <th>Primary</th>
                                    <th>Status</th>
                                    <th width="150">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($client->contacts as $contact)
                                <tr>
                                    <td>{{ $contact->contact_name }}</td>
                                    <td>{{ $contact->contact_title ?? '-' }}</td>
                                    <td>{{ $contact->contact_email ?? '-' }}</td>
                                    <td>{{ $contact->contact_phone ?? '-' }}</td>
                                    <td>
                                        @if($contact->is_primary)
                                        <span class="badge bg-success">Primary</span>
                                        @else
                                        <button class="btn btn-sm btn-outline-primary set-primary" data-id="{{ $contact->id }}">
                                            Set Primary
                                        </button>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge bg-{{ $contact->status == 'active' ? 'success' : 'secondary' }}">
                                            {{ ucfirst($contact->status) }}
                                        </span>
                                    </td>
                                    <td>
                                        @can('edit_clients')
                                        <button class="btn btn-sm btn-primary edit-contact" 
                                                data-id="{{ $contact->id }}"
                                                data-name="{{ $contact->contact_name }}"
                                                data-title="{{ $contact->contact_title }}"
                                                data-email="{{ $contact->contact_email }}"
                                                data-phone="{{ $contact->contact_phone }}"
                                                data-status="{{ $contact->status }}">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        @if(!$contact->is_primary)
                                        <button class="btn btn-sm btn-danger delete-contact" data-id="{{ $contact->id }}">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                        @endif
                                        @endcan
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @else
                    <p class="text-muted mb-0">No contacts found for this client.</p>
                    @endif
                </div>
            </div>
        </div>

        <!-- Jobs Tab -->
        <div class="tab-pane fade" id="jobs" role="tabpanel">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Job Orders</h5>
                </div>
                <div class="card-body">
                    @if($client->jobOrders->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Job No</th>
                                    <th>Site</th>
                                    <th>Type</th>
                                    <th>Status</th>
                                    <th>Created</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($client->jobOrders as $job)
                                <tr>
                                    <td>{{ $job->job_no }}</td>
                                    <td>{{ $job->site->site_name ?? '-' }}</td>
                                    <td>{{ $job->job_type }}</td>
                                    <td>{!! $job->status_badge !!}</td>
                                    <td>{{ $job->created_at->format('Y-m-d') }}</td>
                                    <td>
                                        <a href="{{ route('admin.jobs.show', $job) }}" class="btn btn-sm btn-info">
                                            <i class="bi bi-eye"></i> View
                                        </a>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @else
                    <p class="text-muted mb-0">No job orders found for this client.</p>
                    @endif
                </div>
            </div>
        </div>

        <!-- Invoices Tab -->
        <div class="tab-pane fade" id="invoices" role="tabpanel">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Invoices</h5>
                </div>
                <div class="card-body">
                    @if($client->invoices->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Invoice No</th>
                                    <th>Date</th>
                                    <th>Due Date</th>
                                    <th>Amount</th>
                                    <th>Paid</th>
                                    <th>Balance</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($client->invoices as $invoice)
                                <tr>
                                    <td>{{ $invoice->invoice_no }}</td>
                                    <td>{{ $invoice->invoice_date->format('Y-m-d') }}</td>
                                    <td>{{ $invoice->due_date ? $invoice->due_date->format('Y-m-d') : '-' }}</td>
                                    <td>RM {{ number_format($invoice->total_amount, 2) }}</td>
                                    <td>RM {{ number_format($invoice->paid_amount, 2) }}</td>
                                    <td class="text-danger">RM {{ number_format($invoice->total_amount - $invoice->paid_amount, 2) }}</td>
                                    <td>{!! $invoice->status_badge !!}</td>
                                    <td>
                                        <a href="{{ route('admin.invoices.show', $invoice) }}" class="btn btn-sm btn-info">
                                            <i class="bi bi-eye"></i> View
                                        </a>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @else
                    <p class="text-muted mb-0">No invoices found for this client.</p>
                    @endif
                </div>
            </div>
        </div>

        <!-- Aging Tab -->
        <div class="tab-pane fade" id="aging" role="tabpanel">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Aging Summary</h5>
                </div>
                <div class="card-body">
                    <div class="row mb-4">
                        <div class="col-md-2">
                            <div class="card text-center">
                                <div class="card-body">
                                    <h6 class="text-muted">Current</h6>
                                    <h4 class="text-success">RM {{ number_format($agingSummary['current'], 2) }}</h4>
                                    <small class="text-muted">Not Due</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="card text-center bg-warning bg-opacity-10">
                                <div class="card-body">
                                    <h6 class="text-muted">1-30 Days</h6>
                                    <h4 class="text-warning">RM {{ number_format($agingSummary['1-30'], 2) }}</h4>
                                    <small class="text-muted">Overdue</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="card text-center bg-danger bg-opacity-10">
                                <div class="card-body">
                                    <h6 class="text-muted">31-60 Days</h6>
                                    <h4 class="text-danger">RM {{ number_format($agingSummary['31-60'], 2) }}</h4>
                                    <small class="text-muted">Overdue</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="card text-center bg-danger bg-opacity-25">
                                <div class="card-body">
                                    <h6 class="text-muted">61-90 Days</h6>
                                    <h4 class="text-danger">RM {{ number_format($agingSummary['61-90'], 2) }}</h4>
                                    <small class="text-muted">Overdue</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="card text-center bg-dark text-white">
                                <div class="card-body">
                                    <h6>Over 90 Days</h6>
                                    <h4>RM {{ number_format($agingSummary['over_90'], 2) }}</h4>
                                    <small>Overdue</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="card text-center border-primary">
                                <div class="card-body">
                                    <h6 class="text-muted">Total</h6>
                                    <h4 class="text-primary">RM {{ number_format($agingSummary['total'], 2) }}</h4>
                                    <small class="text-muted">Outstanding</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Contact Modal -->
<div class="modal fade" id="addContactModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Contact</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="addContactForm">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Contact Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="contact_name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Title</label>
                        <input type="text" class="form-control" name="contact_title">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" class="form-control" name="contact_email">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Phone</label>
                        <input type="text" class="form-control" name="contact_phone">
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="is_primary" id="is_primary">
                        <label class="form-check-label" for="is_primary">
                            Set as Primary Contact
                        </label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Contact</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Contact Modal -->
<div class="modal fade" id="editContactModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Contact</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="editContactForm">
                <input type="hidden" name="contact_id" id="edit_contact_id">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Contact Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="contact_name" id="edit_contact_name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Title</label>
                        <input type="text" class="form-control" name="contact_title" id="edit_contact_title">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" class="form-control" name="contact_email" id="edit_contact_email">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Phone</label>
                        <input type="text" class="form-control" name="contact_phone" id="edit_contact_phone">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <select class="form-select" name="status" id="edit_contact_status">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Contact</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Add Contact
    $('#addContactForm').on('submit', function(e) {
        e.preventDefault();
        
        $.ajax({
            url: '{{ route("admin.clients.contacts.store", $client) }}',
            type: 'POST',
            data: $(this).serialize() + '&_token={{ csrf_token() }}',
            success: function(response) {
                if (response.success) {
                    alert('Contact added successfully');
                    location.reload();
                }
            },
            error: function(xhr) {
                alert('Failed to add contact: ' + (xhr.responseJSON?.message || 'Unknown error'));
            }
        });
    });

    // Edit Contact
    $('.edit-contact').on('click', function() {
        const id = $(this).data('id');
        const name = $(this).data('name');
        const title = $(this).data('title');
        const email = $(this).data('email');
        const phone = $(this).data('phone');
        const status = $(this).data('status');
        
        $('#edit_contact_id').val(id);
        $('#edit_contact_name').val(name);
        $('#edit_contact_title').val(title);
        $('#edit_contact_email').val(email);
        $('#edit_contact_phone').val(phone);
        $('#edit_contact_status').val(status);
        
        $('#editContactModal').modal('show');
    });

    $('#editContactForm').on('submit', function(e) {
        e.preventDefault();
        
        const contactId = $('#edit_contact_id').val();
        
        $.ajax({
            url: `/admin/clients/{{ $client->id }}/contacts/${contactId}`,
            type: 'PUT',
            data: $(this).serialize() + '&_token={{ csrf_token() }}',
            success: function(response) {
                if (response.success) {
                    alert('Contact updated successfully');
                    location.reload();
                }
            },
            error: function(xhr) {
                alert('Failed to update contact: ' + (xhr.responseJSON?.message || 'Unknown error'));
            }
        });
    });

    // Delete Contact
    $('.delete-contact').on('click', function() {
        if (!confirm('Are you sure you want to delete this contact?')) return;
        
        const contactId = $(this).data('id');
        
        $.ajax({
            url: `/admin/clients/{{ $client->id }}/contacts/${contactId}`,
            type: 'DELETE',
            data: { _token: '{{ csrf_token() }}' },
            success: function(response) {
                if (response.success) {
                    alert('Contact deleted successfully');
                    location.reload();
                }
            },
            error: function(xhr) {
                alert('Failed to delete contact: ' + (xhr.responseJSON?.message || 'Unknown error'));
            }
        });
    });

    // Set Primary Contact
    $('.set-primary').on('click', function() {
        const contactId = $(this).data('id');
        
        $.ajax({
            url: `/admin/clients/{{ $client->id }}/contacts/${contactId}/set-primary`,
            type: 'POST',
            data: { _token: '{{ csrf_token() }}' },
            success: function(response) {
                if (response.success) {
                    alert('Primary contact updated successfully');
                    location.reload();
                }
            },
            error: function(xhr) {
                alert('Failed to set primary contact: ' + (xhr.responseJSON?.message || 'Unknown error'));
            }
        });
    });
});
</script>
@endpush
