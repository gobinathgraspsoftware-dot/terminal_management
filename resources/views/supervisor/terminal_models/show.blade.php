@extends('layouts.app')

@section('title', 'Terminal Model Details')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-12">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('supervisor.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('supervisor.terminal-models.index') }}">Terminal Models</a></li>
                    <li class="breadcrumb-item active">{{ $terminalModel->model_code }}</li>
                </ol>
            </nav>
            <div class="d-flex justify-content-between align-items-center">
                <h1 class="h3 mb-0">{{ $terminalModel->model_name }}</h1>
                <a href="{{ route('supervisor.terminal-models.index') }}" class="btn btn-secondary">
                    <i class="bi bi-arrow-left"></i> Back
                </a>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8">
            <!-- Basic Info -->
            <div class="card mb-3">
                <div class="card-header"><h5 class="mb-0">Basic Information</h5></div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="fw-bold">Model Code</label>
                            <p>{{ $terminalModel->model_code }}</p>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="fw-bold">Category</label>
                            <p><span class="badge bg-info">{{ $terminalModel->category->category_name ?? 'N/A' }}</span></p>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="fw-bold">Brand</label>
                            <p>{{ $terminalModel->brand ?? 'N/A' }}</p>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="fw-bold">Warranty</label>
                            <p>{{ $terminalModel->warranty_months }} months</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Specifications -->
            @if($terminalModel->specifications && count($terminalModel->specifications) > 0)
            <div class="card mb-3">
                <div class="card-header"><h5 class="mb-0">Specifications</h5></div>
                <div class="card-body">
                    <table class="table table-sm table-bordered">
                        <tbody>
                            @foreach($terminalModel->specifications as $spec)
                                @if(!empty($spec['key']))
                                <tr>
                                    <td width="40%"><strong>{{ $spec['key'] }}</strong></td>
                                    <td>{{ $spec['value'] ?? 'N/A' }}</td>
                                </tr>
                                @endif
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endif

            <!-- Stock Summary -->
            <div class="card mb-3">
                <div class="card-header"><h5 class="mb-0">Stock Summary</h5></div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-md-4">
                            <h4 class="text-success">{{ $stockSummary['total_in_stock'] }}</h4>
                            <small>In Stock</small>
                        </div>
                        <div class="col-md-4">
                            <h4 class="text-warning">{{ $stockSummary['total_issued'] }}</h4>
                            <small>Issued</small>
                        </div>
                        <div class="col-md-4">
                            <h4 class="text-primary">{{ $stockSummary['total_overall'] }}</h4>
                            <small>Total</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <!-- Image -->
            <div class="card mb-3">
                <div class="card-header"><h5 class="mb-0">Model Image</h5></div>
                <div class="card-body text-center">
                    @if($terminalModel->image_path)
                        <img src="{{ asset('storage/' . $terminalModel->image_path) }}" class="img-fluid rounded">
                    @else
                        <i class="bi bi-image text-muted" style="font-size: 4rem;"></i>
                        <p class="text-muted mt-2">No image</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
