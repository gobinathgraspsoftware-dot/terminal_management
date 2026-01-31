@extends('layouts.app')

@section('title', 'Edit Terminal Category')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">Edit Terminal Category</h1>
            <p class="text-muted">Update category: {{ $category->category_name }}</p>
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
                        @method('PUT')
                        @include('admin.terminal-categories._form', ['category' => $category])
                        
                        <div class="d-flex justify-content-end gap-2 mt-4">
                            <a href="{{ route('admin.terminal-categories.index') }}" class="btn btn-secondary">
                                Cancel
                            </a>
                            <button type="submit" class="btn btn-primary" id="submitBtn">
                                <i class="bi bi-save me-1"></i> Update Category
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Associated Models Card -->
            @if($category->terminalModels->count() > 0)
            <div class="card border-0 shadow-sm mt-4">
                <div class="card-header bg-white border-bottom">
                    <h5 class="mb-0">Associated Terminal Models ({{ $category->terminalModels->count() }})</h5>
                </div>
                <div class="card-body">
                    <div class="list-group list-group-flush">
                        @foreach($category->terminalModels->take(10) as $model)
                        <div class="list-group-item px-0">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <strong>{{ $model->model_code }}</strong> - {{ $model->model_name }}
                                    @if($model->brand)
                                        <span class="text-muted">({{ $model->brand }})</span>
                                    @endif
                                </div>
                                <span class="badge bg-{{ $model->status === 'active' ? 'success' : 'secondary' }}">
                                    {{ ucfirst($model->status) }}
                                </span>
                            </div>
                        </div>
                        @endforeach
                    </div>
                    @if($category->terminalModels->count() > 10)
                        <p class="text-muted small mt-2 mb-0">
                            ... and {{ $category->terminalModels->count() - 10 }} more models
                        </p>
                    @endif
                </div>
            </div>
            @endif
        </div>

        <div class="col-lg-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom">
                    <h5 class="mb-0"><i class="bi bi-info-circle me-2"></i>Category Details</h5>
                </div>
                <div class="card-body">
                    <table class="table table-sm">
                        <tbody>
                            <tr>
                                <th width="40%">Category Code:</th>
                                <td><code>{{ $category->category_code }}</code></td>
                            </tr>
                            <tr>
                                <th>Type:</th>
                                <td><span class="badge bg-primary">{{ ucfirst($category->category_type) }}</span></td>
                            </tr>
                            <tr>
                                <th>Serial Tracking:</th>
                                <td>
                                    @if($category->is_serial_tracked)
                                        <i class="bi bi-check-circle-fill text-success"></i> Required
                                    @else
                                        <i class="bi bi-x-circle-fill text-danger"></i> Not Required
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <th>Sort Order:</th>
                                <td>{{ $category->sort_order }}</td>
                            </tr>
                            <tr>
                                <th>Status:</th>
                                <td>
                                    <span class="badge bg-{{ $category->status === 'active' ? 'success' : 'danger' }}">
                                        {{ ucfirst($category->status) }}
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <th>Terminal Models:</th>
                                <td><strong>{{ $category->terminalModels->count() }}</strong></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card border-0 shadow-sm mt-3">
                <div class="card-header bg-white border-bottom">
                    <h5 class="mb-0"><i class="bi bi-info-circle me-2"></i>Guidelines</h5>
                </div>
                <div class="card-body">
                    <p class="small text-muted">
                        <i class="bi bi-exclamation-triangle text-warning me-1"></i>
                        Changing the category type or serial tracking settings may affect associated terminal models.
                    </p>
                    
                    @if($category->terminalModels->count() > 0)
                    <div class="alert alert-warning small mb-0">
                        <i class="bi bi-info-circle me-1"></i>
                        This category has {{ $category->terminalModels->count() }} associated models and cannot be deleted.
                    </div>
                    @endif
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
        submitBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Updating...');
        
        $.ajax({
            url: '{{ route('admin.terminal-categories.update', $category->id) }}',
            method: 'PUT',
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
                let message = 'Failed to update category';
                
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
