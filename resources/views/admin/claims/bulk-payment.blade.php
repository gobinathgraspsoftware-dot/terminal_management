@extends('layouts.app')
@section('title', 'Bulk Payment Processing')

@section('content')
<div class="page-header">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div>
            <h1><i class="bi bi-cash-coin me-2 text-warning"></i>Bulk Payment Processing</h1>
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.claims.index') }}">Claims</a></li>
                <li class="breadcrumb-item active">Bulk Payment</li>
            </ol>
        </div>
        <a href="{{ route('admin.claims.payment-history') }}" class="btn btn-outline-success btn-sm">
            <i class="bi bi-clock-history me-1"></i>View Payment History
        </a>
    </div>
</div>

{{-- Summary Cards --}}
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="stats-card text-center">
            <div class="stats-icon bg-success bg-opacity-10 text-success mx-auto mb-2"><i class="bi bi-check-circle fs-4"></i></div>
            <div class="stats-value text-success">{{ $verifiedCount }}</div>
            <div class="stats-label">Verified (Ready)</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stats-card text-center">
            <div class="stats-icon bg-success bg-opacity-10 text-success mx-auto mb-2"><i class="bi bi-currency-dollar fs-4"></i></div>
            <div class="stats-value text-success" style="font-size:1.4rem">RM {{ number_format($verifiedAmount, 2) }}</div>
            <div class="stats-label">Total Verified Amount</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stats-card text-center">
            <div class="stats-icon bg-warning bg-opacity-10 text-warning mx-auto mb-2"><i class="bi bi-hourglass-split fs-4"></i></div>
            <div class="stats-value text-warning">{{ $pendingCount }}</div>
            <div class="stats-label">Pending Payment</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stats-card text-center">
            <div class="stats-icon bg-warning bg-opacity-10 text-warning mx-auto mb-2"><i class="bi bi-currency-dollar fs-4"></i></div>
            <div class="stats-value text-warning" style="font-size:1.4rem">RM {{ number_format($pendingAmount, 2) }}</div>
            <div class="stats-label">Total Pending Amount</div>
        </div>
    </div>
</div>

{{-- Filter + Action Bar --}}
<div class="card mb-3">
    <div class="card-body py-2">
        <div class="d-flex align-items-center gap-3 flex-wrap">
            <div class="d-flex align-items-center gap-2">
                <label class="form-label mb-0 fw-semibold small text-nowrap">Category:</label>
                <select id="categoryFilter" class="form-select form-select-sm" style="width:140px">
                    <option value="all" {{ $category==='all'?'selected':'' }}>All</option>
                    <option value="ticket" {{ $category==='ticket'?'selected':'' }}>Ticket Claims</option>
                    <option value="other" {{ $category==='other'?'selected':'' }}>Other Claims</option>
                </select>
            </div>
            <div class="d-flex align-items-center gap-2">
                <label class="form-label mb-0 fw-semibold small text-nowrap">Status:</label>
                <select id="statusFilter" class="form-select form-select-sm" style="width:160px">
                    <option value="">Verified + Pending</option>
                    <option value="verified">Verified Only</option>
                    <option value="pending_payment">Pending Only</option>
                </select>
            </div>
            <div class="ms-auto d-flex gap-2">
                <button id="btnSelectAll" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-check-all me-1"></i>Select All
                </button>
                <button id="btnClearSelection" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-x me-1"></i>Clear
                </button>
            </div>
        </div>
    </div>
</div>

{{-- DataTable --}}
<div class="card">
    <div class="card-header d-flex align-items-center justify-content-between">
        <span><i class="bi bi-list-check me-2"></i>Claims for Payment</span>
        <div id="selectionSummary" class="text-muted small d-none">
            <span id="selectedCount">0</span> selected &middot; RM <span id="selectedTotal">0.00</span>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table id="bulkPaymentTable" class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width:40px"><input type="checkbox" id="selectAllCheckbox" class="form-check-input"></th>
                        <th>Claim No</th>
                        <th>Category</th>
                        <th>Claimant</th>
                        <th>Ticket No</th>
                        <th>Vendor</th>
                        <th class="text-end">Amount (RM)</th>
                        <th>Status</th>
                        <th>Submitted</th>
                    </tr>
                </thead>
                <tbody></tbody>
                <tfoot>
                    <tr class="table-secondary fw-bold">
                        <td colspan="6" class="text-end">Selected Total:</td>
                        <td class="text-end" id="footerTotal">RM 0.00</td>
                        <td colspan="2"></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>

{{-- Sticky Action Footer --}}
<div class="position-sticky bottom-0 bg-white border-top shadow p-3 mt-3 rounded" id="actionFooter" style="display:none!important">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div>
            <span class="fw-semibold"><span id="footerSelectedCount">0</span> claim(s) selected</span>
            <span class="text-muted ms-2">Total: <strong class="text-success">RM <span id="footerSelectedTotal">0.00</span></strong></span>
        </div>
        <div class="d-flex gap-2">
            <button class="btn btn-warning btn-sm" id="btnProcessPayment">
                <i class="bi bi-hourglass-split me-1"></i>Move to Pending Payment
            </button>
            <button class="btn btn-success btn-sm" id="btnMarkPaid">
                <i class="bi bi-check-circle me-1"></i>Mark as Paid
            </button>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
var selectedClaims = {};
var claimAmounts   = {};

function refreshSelectionUI() {
    var ids = Object.keys(selectedClaims).filter(id => selectedClaims[id]);
    var total = ids.reduce(function(s, id) { return s + (claimAmounts[id] || 0); }, 0);

    $('#selectedCount, #footerSelectedCount').text(ids.length);
    var fmt = parseFloat(total).toFixed(2);
    $('#selectedTotal, #footerSelectedTotal').text(fmt);
    $('#footerTotal').text('RM ' + fmt);

    if (ids.length > 0) {
        $('#selectionSummary').removeClass('d-none');
        $('#actionFooter').css('display', 'block');
    } else {
        $('#selectionSummary').addClass('d-none');
        $('#actionFooter').css('display', 'none !important');
    }
}

$(function () {
    var table = $('#bulkPaymentTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route("admin.claims.bulk-payment-data") }}',
            data: function (d) {
                d.category     = $('#categoryFilter').val();
                d.status_filter = $('#statusFilter').val();
            }
        },
        columns: [
            {
                data: 'id', orderable: false, searchable: false,
                render: function (id, t, row) {
                    var chk = selectedClaims[id] ? 'checked' : '';
                    return '<input type="checkbox" class="form-check-input row-check" data-id="'+id+'" data-amount="'+row.total_amount+'" '+chk+'>';
                }
            },
            { data: 'claim_no' },
            { data: 'category', orderable: false },
            { data: 'claimant', orderable: false },
            { data: 'ticket_no', orderable: false },
            { data: 'vendor', orderable: false },
            { data: 'total_amount', className: 'text-end' },
            { data: 'status', orderable: false },
            { data: 'submitted_at' }
        ],
        order: [[8, 'asc']],
        pageLength: 25,
        language: { processing: '<div class="spinner-border spinner-border-sm text-primary"></div>' },
        drawCallback: function() {
            refreshSelectionUI();
        }
    });

    // Category / Status filter
    $('#categoryFilter, #statusFilter').on('change', function() {
        selectedClaims = {}; claimAmounts = {};
        table.ajax.reload();
    });

    // Row checkbox
    $(document).on('change', '.row-check', function () {
        var id     = $(this).data('id');
        var amt    = parseFloat($(this).data('amount').toString().replace(/,/g,'')) || 0;
        selectedClaims[id] = $(this).is(':checked');
        claimAmounts[id]   = amt;
        refreshSelectionUI();
    });

    // Select all on current page
    $('#selectAllCheckbox').on('change', function () {
        var checked = $(this).is(':checked');
        $('.row-check').each(function () {
            var id  = $(this).data('id');
            var amt = parseFloat($(this).data('amount').toString().replace(/,/g,'')) || 0;
            selectedClaims[id] = checked;
            claimAmounts[id]   = amt;
            $(this).prop('checked', checked);
        });
        refreshSelectionUI();
    });

    // Select all button (full dataset query)
    $('#btnSelectAll').on('click', function () {
        $.get('{{ route("admin.claims.bulk-payment-data") }}', {
            category: $('#categoryFilter').val(),
            status_filter: $('#statusFilter').val(),
            length: 9999, start: 0, draw: 1
        }, function (res) {
            if (res.data) {
                res.data.forEach(function(row) {
                    var amt = parseFloat(row.total_amount.toString().replace(/,/g,'')) || 0;
                    selectedClaims[row.id] = true;
                    claimAmounts[row.id]   = amt;
                });
                table.ajax.reload();
            }
        });
    });

    $('#btnClearSelection').on('click', function () {
        selectedClaims = {}; claimAmounts = {};
        table.ajax.reload();
    });

    // Move to Pending Payment
    $('#btnProcessPayment').on('click', function () {
        var ids = Object.keys(selectedClaims).filter(id => selectedClaims[id]);
        if (!ids.length) { showToast('Select at least one claim.', 'warning'); return; }
        confirmAction('Move to Pending Payment?', ids.length + ' claim(s) will be queued for payment.', function() {
            $.post('{{ route("admin.claims.process-bulk-payment") }}', {
                _token: '{{ csrf_token() }}',
                claim_ids: ids
            }).done(function(r) {
                if (r.success) {
                    showToast(r.message, 'success');
                    selectedClaims = {}; claimAmounts = {};
                    table.ajax.reload();
                    setTimeout(() => location.reload(), 2000);
                } else showToast(r.message, 'error');
            });
        });
    });

    // Mark as Paid
    $('#btnMarkPaid').on('click', function () {
        var ids = Object.keys(selectedClaims).filter(id => selectedClaims[id]);
        if (!ids.length) { showToast('Select at least one claim.', 'warning'); return; }
        confirmAction('Mark as Paid?', ids.length + ' claim(s) will be marked as Paid.', function() {
            $.post('{{ route("admin.claims.mark-paid") }}', {
                _token: '{{ csrf_token() }}',
                claim_ids: ids
            }).done(function(r) {
                if (r.success) {
                    showToast(r.message, 'success');
                    selectedClaims = {}; claimAmounts = {};
                    table.ajax.reload();
                    setTimeout(() => location.reload(), 2000);
                } else showToast(r.message, 'error');
            });
        });
    });
});
</script>
@endpush
