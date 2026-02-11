@extends('layouts.app')

@section('title', 'Edit Quotation')

@section('content')
<div class="pagetitle">
    <h1>Edit Quotation</h1>
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('supervisor.dashboard') }}">Home</a></li>
            <li class="breadcrumb-item"><a href="{{ route('supervisor.quotations.index') }}">Quotations</a></li>
            <li class="breadcrumb-item active">Edit {{ $quotation->quotation_no }}</li>
        </ol>
    </nav>
</div>

<section class="section">
    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Edit Quotation: {{ $quotation->quotation_no }}</h5>

                    @if($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form id="quotation-form" method="POST" action="{{ route('supervisor.quotations.update', $quotation) }}">
                        @csrf
                        @method('PUT')
                        @include('supervisor.quotations._form')
                        
                        <div class="row mt-3">
                            <div class="col-md-12">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-save"></i> Update Quotation
                                </button>
                                <a href="{{ route('supervisor.quotations.show', $quotation) }}" class="btn btn-secondary">
                                    <i class="bi bi-x-circle"></i> Cancel
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
