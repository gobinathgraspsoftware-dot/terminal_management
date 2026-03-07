@extends('layouts.app')

@section('title', 'Vendors')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">Vendors</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('supervisor.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item active">Vendors</li>
                </ol>
            </nav>
        </div>
    </div>

    {{-- Statistics --}}
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="card text-center">
                <div class="card-body py-3">
                    <div class="h4 mb-0 text-primary">{{ $statistics['total'] ?? 0 }}</div>
                    <small class="text-muted">Total Vendors</small>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card text-center">
                <div class="card-body py-3">
                    <div class="h4 mb-0 text-success">{{ $statistics['active'] ?? 0 }}</div>
                    <small class="text-muted">Active</small>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card text-center">
                <div class="card-body py-3">
                    <div class="h4 mb-0 text-info">{{ $statistics['suppliers'] ?? 0 }}</div>
                    <small class="text-muted">Suppliers</small>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card text-center">
                <div class="card-body py-3">
                    <div class="h4 mb-0 text-dark">{{ $statistics['total_branches'] ?? 0 }}</div>
                    <small class="text-muted">Branches</small>
                </div>
            </div>
        </div>
    </div>

    {{-- Filter --}}
    <div class="card mb-4">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Vendor Type</label>
                    <select class="form-select" id="filterType">
                        <option value="">All Types</option>
                        <option value="supplier">Supplier</option>
                        <option value="subcon">Sub-contractor</option>
                        <option value="courier">Courier</option>
                        <option value="other">Other</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    {{-- DataTable --}}
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table id="vendorsTable" class="table table-hover table-striped w-100">
                    <thead>
                        <tr>
                            <th>Code</th>
                            <th>Name</th>
                            <th>Type</th>
                            <th>PIC</th>
                            <th>Branches</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    var table = $('#vendorsTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route("supervisor.vendors.datatable") }}',
            data: function(d) {
                d.vendor_type = $('#filterType').val();
            }
        },
        columns: [
            { data: 'vendor_code', name: 'vendor_code' },
            { data: 'vendor_name', name: 'vendor_name' },
            { data: 'vendor_type_badge', name: 'vendor_type', searchable: false, orderable: false },
            { data: 'pic_info', name: 'pic_name', searchable: false, orderable: false },
            { data: 'branches_count_display', name: 'branches_count', searchable: false, orderable: false },
            { data: 'status_badge', name: 'status', searchable: false, orderable: false },
            { data: 'actions', name: 'actions', orderable: false, searchable: false }
        ],
        order: [[0, 'desc']],
        responsive: true
    });

    $('#filterType').on('change', function() { table.draw(); });
});
</script>
@endpush
