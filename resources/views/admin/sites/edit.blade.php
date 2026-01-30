@extends('layouts.app')

@section('title', 'Edit Site')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">Edit Site</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.sites.index') }}">Sites</a></li>
                    <li class="breadcrumb-item active">{{ $site->site_code }}</li>
                </ol>
            </nav>
        </div>
        <div>
            <a href="{{ route('admin.sites.show', $site) }}" class="btn btn-info me-2">
                <i class="bi bi-eye me-1"></i> View Details
            </a>
            <a href="{{ route('admin.sites.index') }}" class="btn btn-secondary">
                <i class="bi bi-arrow-left me-1"></i> Back to List
            </a>
        </div>
    </div>

    <!-- Form Card -->
    <div class="row">
        <div class="col-lg-10 mx-auto">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom">
                    <h5 class="mb-0">Site Information - {{ $site->site_code }}</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.sites.update', $site) }}" method="POST" id="site-form">
                        @csrf
                        @method('PUT')
                        @include('admin.sites._form')
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Initialize Select2 for client dropdown
    $('#client_id').select2({
        theme: 'bootstrap-5',
        placeholder: 'Select a client',
        allowClear: true
    });

    // Initialize Select2 for state dropdown
    $('#state').select2({
        theme: 'bootstrap-5',
        placeholder: 'Select a state',
        allowClear: true
    });

    // Form submission with validation
    $('#site-form').on('submit', function(e) {
        e.preventDefault();
        
        const form = $(this);
        const submitBtn = form.find('button[type="submit"]');
        const originalText = submitBtn.html();
        
        // Disable submit button
        submitBtn.prop('disabled', true).html('<i class="bi bi-hourglass-split me-1"></i> Updating...');
        
        // Clear previous errors
        $('.is-invalid').removeClass('is-invalid');
        $('.invalid-feedback').remove();
        
        $.ajax({
            url: form.attr('action'),
            type: 'POST',
            data: form.serialize(),
            success: function(response) {
                if (response.success) {
                    // Show success message
                    alert('Site updated successfully!');
                    // Redirect to site details
                    window.location.href = '{{ route("admin.sites.show", $site) }}';
                }
            },
            error: function(xhr) {
                // Re-enable submit button
                submitBtn.prop('disabled', false).html(originalText);
                
                if (xhr.status === 422) {
                    // Validation errors
                    const errors = xhr.responseJSON.errors;
                    $.each(errors, function(field, messages) {
                        const input = $('[name="' + field + '"]');
                        input.addClass('is-invalid');
                        input.after('<div class="invalid-feedback">' + messages[0] + '</div>');
                    });
                } else {
                    alert('Error: ' + (xhr.responseJSON?.message || 'Failed to update site'));
                }
            }
        });
    });

    // GPS Capture functionality
    $('#btn-capture-gps').on('click', function() {
        const btn = $(this);
        
        if (!navigator.geolocation) {
            alert('Geolocation is not supported by your browser');
            return;
        }
        
        btn.prop('disabled', true).html('<i class="bi bi-hourglass-split me-1"></i> Getting Location...');
        
        navigator.geolocation.getCurrentPosition(
            function(position) {
                $('#latitude').val(position.coords.latitude.toFixed(8));
                $('#longitude').val(position.coords.longitude.toFixed(8));
                btn.prop('disabled', false).html('<i class="bi bi-geo-alt me-1"></i> Capture GPS');
                alert('GPS coordinates captured successfully!');
            },
            function(error) {
                btn.prop('disabled', false).html('<i class="bi bi-geo-alt me-1"></i> Capture GPS');
                alert('Error getting location: ' + error.message);
            },
            {
                enableHighAccuracy: true,
                timeout: 10000,
                maximumAge: 0
            }
        );
    });
});
</script>
@endpush
