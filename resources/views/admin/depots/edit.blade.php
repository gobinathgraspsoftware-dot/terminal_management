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
    // Form submission
    $('#depotForm').on('submit', function(e) {
        e.preventDefault();
        
        let form = $(this);
        let submitBtn = form.find('button[type="submit"]');
        let originalText = submitBtn.html();
        
        // Disable submit button
        submitBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>Updating...');
        
        $.ajax({
            url: form.attr('action'),
            type: 'POST',
            data: form.serialize(),
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message);
                    setTimeout(function() {
                        window.location.href = "{{ route('admin.depots.index') }}";
                    }, 1000);
                } else {
                    toastr.error(response.message);
                    submitBtn.prop('disabled', false).html(originalText);
                }
            },
            error: function(xhr) {
                // Handle validation errors
                if (xhr.status === 422) {
                    let errors = xhr.responseJSON.errors;
                    for (let field in errors) {
                        let errorMsg = errors[field][0];
                        let input = form.find('[name="' + field + '"]');
                        input.addClass('is-invalid');
                        input.next('.invalid-feedback').remove();
                        input.after('<div class="invalid-feedback d-block">' + errorMsg + '</div>');
                    }
                    toastr.error('Please correct the errors in the form.');
                } else {
                    toastr.error(xhr.responseJSON?.message || 'Failed to update depot.');
                }
                submitBtn.prop('disabled', false).html(originalText);
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
