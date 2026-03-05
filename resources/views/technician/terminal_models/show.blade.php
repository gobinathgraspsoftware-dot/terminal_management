@extends('layouts.app')

@section('title', $terminalModel->model_name . ' - Terminal Models - TMS')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1"><i class="bi bi-box-seam me-2"></i>{{ $terminalModel->model_name }}</h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('technician.terminal-models.index') }}">Terminal Models</a></li>
                    <li class="breadcrumb-item active">{{ $terminalModel->model_code }}</li>
                </ol>
            </nav>
        </div>
        <a href="{{ route('technician.terminal-models.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Back
        </a>
    </div>

    <div class="row">
        <div class="col-md-8">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white"><h6 class="mb-0"><i class="bi bi-info-circle me-2"></i>Model Details</h6></div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4 text-center mb-3">
                            @if($terminalModel->image_url)
                                <img src="{{ $terminalModel->image_url }}" alt="{{ $terminalModel->model_name }}"
                                     class="img-fluid img-thumbnail rounded" style="max-width:250px;max-height:250px;object-fit:contain;">
                            @else
                                <div class="bg-light rounded d-flex align-items-center justify-content-center" style="width:200px;height:200px;margin:0 auto;">
                                    <div class="text-center text-muted"><i class="bi bi-image" style="font-size:3rem;"></i><p class="mb-0 small">No Image</p></div>
                                </div>
                            @endif
                        </div>
                        <div class="col-md-8">
                            <table class="table table-borderless">
                                <tr><th class="text-muted" style="width:40%;">Model Code</th><td><strong>{{ $terminalModel->model_code }}</strong></td></tr>
                                <tr><th class="text-muted">Model Name</th><td>{{ $terminalModel->model_name }}</td></tr>
                                <tr><th class="text-muted">Category</th><td>@if($terminalModel->category)<span class="badge bg-info">{{ $terminalModel->category->category_name }}</span>@else - @endif</td></tr>
                                <tr><th class="text-muted">Brand</th><td>{{ $terminalModel->brand ?? '-' }}</td></tr>
                                <tr><th class="text-muted">Warranty</th><td>{{ $terminalModel->warranty_months }} months</td></tr>
                                <tr><th class="text-muted">Serial Tracked</th><td>{!! $terminalModel->is_serial_tracked ? '<span class="badge bg-primary">Yes</span>' : '<span class="badge bg-secondary">No</span>' !!}</td></tr>
                            </table>
                        </div>
                    </div>
                    @if($terminalModel->description)
                    <div class="mt-3"><h6 class="text-muted">Description</h6><p>{{ $terminalModel->description }}</p></div>
                    @endif
                </div>
            </div>

            @if($terminalModel->specifications && is_array($terminalModel->specifications) && count($terminalModel->specifications) > 0)
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white"><h6 class="mb-0"><i class="bi bi-list-check me-2"></i>Specifications</h6></div>
                <div class="card-body">
                    <table class="table table-sm table-striped">
                        <thead><tr><th style="width:40%;">Specification</th><th>Value</th></tr></thead>
                        <tbody>
                            @foreach($terminalModel->specifications as $spec)
                                @if(!empty($spec['key']) || !empty($spec['value']))
                                <tr><td><strong>{{ $spec['key'] ?? '-' }}</strong></td><td>{{ $spec['value'] ?? '-' }}</td></tr>
                                @endif
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endif
        </div>

        <div class="col-md-4">
            @if($accessories->count() > 0)
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white"><h6 class="mb-0"><i class="bi bi-puzzle me-2"></i>Default Accessories</h6></div>
                <div class="card-body">
                    <ul class="list-group list-group-flush">
                        @foreach($accessories as $accessory)
                        <li class="list-group-item d-flex align-items-center px-0">
                            @if($accessory->image_url)
                                <img src="{{ $accessory->image_url }}" class="img-thumbnail me-2" style="width:40px;height:40px;object-fit:cover;">
                            @else
                                <div class="bg-light rounded me-2 d-flex align-items-center justify-content-center" style="width:40px;height:40px;"><i class="bi bi-puzzle text-muted"></i></div>
                            @endif
                            <div><strong>{{ $accessory->model_name }}</strong><br><small class="text-muted">{{ $accessory->model_code }}</small></div>
                        </li>
                        @endforeach
                    </ul>
                </div>
            </div>
            @endif
            <div class="card border-0 shadow-sm"><div class="card-header bg-white"><h6 class="mb-0"><i class="bi bi-clock me-2"></i>Metadata</h6></div><div class="card-body"><table class="table table-sm table-borderless mb-0"><tr><td class="text-muted">Created</td><td>{{ $terminalModel->created_at->format('d M Y H:i') }}</td></tr><tr><td class="text-muted">Updated</td><td>{{ $terminalModel->updated_at->format('d M Y H:i') }}</td></tr></table></div></div>
        </div>
    </div>
</div>
@endsection
