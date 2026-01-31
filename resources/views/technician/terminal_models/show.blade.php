@extends('layouts.app')

@section('title', 'Model Details')

@section('content')
<div class="container-fluid">
    <div class="row mb-3">
        <div class="col-12">
            <a href="{{ route('technician.terminal-models.index') }}" class="btn btn-sm btn-secondary mb-2">
                <i class="bi bi-arrow-left"></i> Back
            </a>
            <h4 class="mb-0">{{ $terminalModel->model_name }}</h4>
            <small class="text-muted">{{ $terminalModel->model_code }}</small>
        </div>
    </div>

    <!-- Image Card -->
    @if($terminalModel->image_path)
    <div class="card mb-3">
        <div class="card-body text-center p-2">
            <img src="{{ asset('storage/' . $terminalModel->image_path) }}" class="img-fluid rounded" style="max-height: 250px;">
        </div>
    </div>
    @endif

    <!-- Info Card -->
    <div class="card mb-3">
        <div class="card-header py-2"><strong>Information</strong></div>
        <div class="card-body">
            <div class="row">
                <div class="col-6 mb-2">
                    <small class="text-muted d-block">Category</small>
                    <strong>{{ $terminalModel->category->category_name ?? 'N/A' }}</strong>
                </div>
                <div class="col-6 mb-2">
                    <small class="text-muted d-block">Brand</small>
                    <strong>{{ $terminalModel->brand ?? 'N/A' }}</strong>
                </div>
                <div class="col-6 mb-2">
                    <small class="text-muted d-block">Warranty</small>
                    <strong>{{ $terminalModel->warranty_months }} months</strong>
                </div>
                <div class="col-6 mb-2">
                    <small class="text-muted d-block">Serial Tracking</small>
                    <strong>{{ $terminalModel->is_serial_tracked ? 'Yes' : 'No' }}</strong>
                </div>
            </div>
        </div>
    </div>

    <!-- Specifications -->
    @if($terminalModel->specifications && count($terminalModel->specifications) > 0)
    <div class="card mb-3">
        <div class="card-header py-2"><strong>Specifications</strong></div>
        <div class="list-group list-group-flush">
            @foreach($terminalModel->specifications as $spec)
                @if(!empty($spec['key']))
                <div class="list-group-item py-2">
                    <small class="text-muted d-block">{{ $spec['key'] }}</small>
                    <strong>{{ $spec['value'] ?? 'N/A' }}</strong>
                </div>
                @endif
            @endforeach
        </div>
    </div>
    @endif

    <!-- Accessories -->
    @if(count($accessories) > 0)
    <div class="card mb-3">
        <div class="card-header py-2"><strong>Default Accessories</strong></div>
        <div class="list-group list-group-flush">
            @foreach($accessories as $accessory)
            <div class="list-group-item py-2">
                <strong>{{ $accessory->model_name }}</strong>
                <br><small class="text-muted">{{ $accessory->model_code }}</small>
            </div>
            @endforeach
        </div>
    </div>
    @endif
</div>
@endsection
