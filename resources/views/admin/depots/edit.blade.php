@extends('layouts.app')

@section('title', 'Edit Depot')

@section('content')
<div class="container-fluid">
    <div class="row mb-3">
        <div class="col-md-12">
            <h2 class="mb-1">Edit Depot: {{ $depot->depot_name }}</h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.depots.index') }}">Depots</a></li>
                    <li class="breadcrumb-item active">Edit</li>
                </ol>
            </nav>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <form action="{{ route('admin.depots.update', $depot->id) }}" method="POST" id="depotForm">
                        @csrf
                        @method('PUT')
                        @include('admin.depots._form', ['depot' => $depot, 'submitText' => 'Update Depot'])
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
    // Form submission with AJAX
    $('#depotForm').on('submit', function(e) {
        e.preventDefault();
        
        let form = $(this);
        let submitBtn = form.find('button[type="submit"]');
        let originalText = submitBtn.html();
        
        // Disable submit button
        submitBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>Updating...');
        
        // Clear previous errors
        $('.is-invalid').removeClass('is-invalid');
        $('.invalid-feedback').remove();
        
        $.ajax({
            url: form.attr('action'),
            type: 'POST',
            data: form.serialize(),
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            dataType: 'json',
            success: function(response) {
                console.log('Success response:', response);
                
                if (response.success) {
                    // Show success message
                    if (typeof toastr !== 'undefined') {
                        toastr.success(response.message || 'Depot updated successfully!');
                    } else {
                        alert(response.message || 'Depot updated successfully!');
                    }
                    
                    // Redirect after short delay
                    setTimeout(function() {
                        window.location.href = "{{ route('admin.depots.index') }}";
                    }, 500);
                } else {
                    // Show error message
                    if (typeof toastr !== 'undefined') {
                        toastr.error(response.message || 'Failed to update depot.');
                    } else {
                        alert(response.message || 'Failed to update depot.');
                    }
                    submitBtn.prop('disabled', false).html(originalText);
                }
            },
            error: function(xhr, status, error) {
                console.error('Error response:', xhr);
                
                // Handle validation errors (422)
                if (xhr.status === 422) {
                    let errors = xhr.responseJSON?.errors || {};
                    let errorCount = 0;
                    
                    for (let field in errors) {
                        errorCount++;
                        let errorMsg = errors[field][0];
                        let input = form.find('[name="' + field + '"]');
                        
                        if (input.length) {
                            input.addClass('is-invalid');
                            input.after('<div class="invalid-feedback d-block">' + errorMsg + '</div>');
                        }
                    }
                    
                    // Show error notification
                    let message = errorCount > 0 ? 'Please correct the errors in the form.' : 'Validation failed.';
                    if (typeof toastr !== 'undefined') {
                        toastr.error(message);
                    } else {
                        alert(message);
                    }
                } 
                // Handle other errors
                else {
                    let errorMessage = 'Failed to update depot.';
                    
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMessage = xhr.responseJSON.message;
                    } else if (xhr.statusText) {
                        errorMessage = 'Error: ' + xhr.statusText;
                    }
                    
                    if (typeof toastr !== 'undefined') {
                        toastr.error(errorMessage);
                    } else {
                        alert(errorMessage);
                    }
                }
                
                // Re-enable submit button
                submitBtn.prop('disabled', false).html(originalText);
            },
            complete: function() {
                console.log('Request completed');
            }
        });
    });
    
    // Clear validation errors on input
    $('input, select, textarea').on('input change', function() {
        $(this).removeClass('is-invalid');
        $(this).next('.invalid-feedback').remove();
    });
});
</script>
@endpush
