@extends('layouts.app')

@section('title', 'Create Terminal Category')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">Create Terminal Category</h1>
            <p class="text-muted">Add a new terminal category</p>
        </div>
        <a href="{{ route('admin.terminal-categories.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Back to List
        </a>
    </div>

    <!-- Form Card -->
    <div class="row">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom">
                    <h5 class="mb-0">Category Information</h5>
                </div>
                <div class="card-body">
                    <form id="categoryForm" method="POST">
                        @csrf
                        @include('admin.terminal-categories._form', ['category' => null])
                        
                        <div class="d-flex justify-content-end gap-2 mt-4">
                            <a href="{{ route('admin.terminal-categories.index') }}" class="btn btn-secondary">
                                Cancel
                            </a>
                            <button type="submit" class="btn btn-primary" id="submitBtn">
                                <i class="bi bi-save me-1"></i> Create Category
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom">
                    <h5 class="mb-0"><i class="bi bi-info-circle me-2"></i>Guidelines</h5>
                </div>
                <div class="card-body">
                    <h6 class="fw-bold">Category Code</h6>
                    <p class="small text-muted">Leave blank to auto-generate. Format: TRM0001, RTR0001, etc.</p>
                    
                    <h6 class="fw-bold mt-3">Category Types</h6>
                    <ul class="small text-muted">
                        <li><strong>Terminal:</strong> POS terminals</li>
                        <li><strong>Router:</strong> Network routers</li>
                        <li><strong>SIM:</strong> SIM cards</li>
                        <li><strong>Accessory:</strong> Cables, adapters, etc.</li>
                        <li><strong>Other:</strong> Miscellaneous items</li>
                    </ul>
                    
                    <h6 class="fw-bold mt-3">Serial Tracking</h6>
                    <p class="small text-muted">Enable if this category requires unique serial number tracking for each unit.</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    $('#categoryForm').on('submit', function(e) {
        e.preventDefault();
        
        const form = $(this);
        const submitBtn = $('#submitBtn');
        const originalText = submitBtn.html();
        
        // Disable submit button
        submitBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Creating...');
        
        $.ajax({
            url: '{{ route('admin.terminal-categories.store') }}',
            method: 'POST',
            data: form.serialize(),
            success: function(response) {
                if (response.success) {
                    showToast('success', response.message);
                    setTimeout(() => {
                        window.location.href = '{{ route('admin.terminal-categories.index') }}';
                    }, 1000);
                } else {
                    showToast('error', response.message);
                    submitBtn.prop('disabled', false).html(originalText);
                }
            },
            error: function(xhr) {
                let message = 'Failed to create category';
                
                if (xhr.responseJSON?.errors) {
                    const errors = xhr.responseJSON.errors;
                    message = Object.values(errors).flat().join('<br>');
                } else if (xhr.responseJSON?.message) {
                    message = xhr.responseJSON.message;
                }
                
                showToast('error', message);
                submitBtn.prop('disabled', false).html(originalText);
            }
        });
    });
    
    function showToast(type, message) {
        const bgClass = type === 'success' ? 'bg-success' : 'bg-danger';
        const toast = `
            <div class="position-fixed bottom-0 end-0 p-3" style="z-index: 11">
                <div class="toast show align-items-center text-white ${bgClass} border-0" role="alert">
                    <div class="d-flex">
                        <div class="toast-body">${message}</div>
                        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                    </div>
                </div>
            </div>
        `;
        $('body').append(toast);
        setTimeout(() => $('.toast').remove(), 3000);
    }
});
</script>
@endpush
