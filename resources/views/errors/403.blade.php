@extends('layouts.app')
@section('title', '403 - Forbidden')

@section('content')
<div class="container-fluid">
    <div class="row justify-content-center mt-5">
        <div class="col-md-6 text-center">
            <div class="card border-0 shadow-sm">
                <div class="card-body py-5">
                    <div class="mb-4">
                        <i class="bi bi-shield-lock text-danger" style="font-size: 4rem;"></i>
                    </div>
                    <h2 class="fw-bold text-danger mb-3">403</h2>
                    <h5 class="text-muted mb-3">Access Forbidden</h5>
                    <p class="text-muted mb-4">
                        {{ $exception->getMessage() ?: 'You do not have permission to access this page.' }}
                    </p>
                    <div class="d-flex justify-content-center gap-3">
                        <a href="{{ url()->previous() }}" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-left me-1"></i> Go Back
                        </a>
                        <a href="{{ route('dashboard') }}" class="btn btn-primary">
                            <i class="bi bi-house me-1"></i> Dashboard
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
