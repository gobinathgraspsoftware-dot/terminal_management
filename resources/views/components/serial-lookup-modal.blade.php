{{-- Serial Lookup Modal - Reusable Component --}}
{{-- Include in any view with: @include('components.serial-lookup-modal') --}}

<div class="modal fade" id="serialLookupModal" tabindex="-1" aria-labelledby="serialLookupModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="serialLookupModalLabel">
                    <i class="bi bi-search me-2"></i>Serial Number Lookup
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <!-- Search Tabs -->
                <ul class="nav nav-tabs mb-3" id="lookupTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="single-tab" data-bs-toggle="tab" data-bs-target="#singleLookup"
                                type="button" role="tab">
                            <i class="bi bi-search me-1"></i> Single Lookup
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="batch-tab" data-bs-toggle="tab" data-bs-target="#batchLookup"
                                type="button" role="tab">
                            <i class="bi bi-list-check me-1"></i> Batch Lookup
                        </button>
                    </li>
                </ul>

                <div class="tab-content" id="lookupTabContent">
                    <!-- Single Serial Lookup -->
                    <div class="tab-pane fade show active" id="singleLookup" role="tabpanel">
                        <div class="input-group mb-3">
                            <span class="input-group-text bg-warning text-dark">
                                <i class="bi bi-upc-scan"></i>
                            </span>
                            <input type="text" class="form-control form-control-lg" id="modalSerialInput"
                                   placeholder="Type or scan serial number..." autocomplete="off" autofocus>
                            <button class="btn btn-primary" type="button" id="btnModalSearch">
                                <i class="bi bi-search"></i> Search
                            </button>
                        </div>

                        <!-- Autocomplete Suggestions -->
                        <div id="autocompleteSuggestions" class="list-group mb-3" style="display:none;"></div>

                        <!-- Single Result -->
                        <div id="singleResult" style="display:none;">
                            <div class="card border-primary">
                                <div class="card-header bg-primary text-white d-flex justify-content-between">
                                    <span id="resultSerialNo" class="fw-bold"></span>
                                    <span id="resultStatusBadge"></span>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <table class="table table-sm table-borderless mb-0">
                                                <tr><td class="text-muted">Model</td><td id="resultModel">-</td></tr>
                                                <tr><td class="text-muted">Category</td><td id="resultCategory">-</td></tr>
                                                <tr><td class="text-muted">Location</td><td id="resultLocation">-</td></tr>
                                            </table>
                                        </div>
                                        <div class="col-md-6">
                                            <table class="table table-sm table-borderless mb-0">
                                                <tr><td class="text-muted">GRN</td><td id="resultGrn">-</td></tr>
                                                <tr><td class="text-muted">GRN Date</td><td id="resultGrnDate">-</td></tr>
                                                <tr><td class="text-muted">Warranty</td><td id="resultWarranty">-</td></tr>
                                            </table>
                                        </div>
                                    </div>
                                    <div class="mt-3 text-end">
                                        <a href="#" id="resultViewLink" class="btn btn-sm btn-info">
                                            <i class="bi bi-eye me-1"></i> View Full Detail
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Not Found -->
                        <div id="singleNotFound" class="alert alert-warning" style="display:none;">
                            <i class="bi bi-exclamation-triangle me-2"></i>
                            <span id="notFoundMessage">Serial number not found.</span>
                        </div>
                    </div>

                    <!-- Batch Lookup -->
                    <div class="tab-pane fade" id="batchLookup" role="tabpanel">
                        <div class="mb-3">
                            <label class="form-label">Enter serial numbers (one per line):</label>
                            <textarea class="form-control" id="batchSerialInput" rows="6"
                                      placeholder="ABC12345&#10;DEF67890&#10;GHI11223..."></textarea>
                            <small class="text-muted">Maximum 100 serial numbers per batch.</small>
                        </div>
                        <div class="mb-3 text-end">
                            <button class="btn btn-primary" id="btnBatchSearch">
                                <i class="bi bi-search me-1"></i> Lookup Batch
                            </button>
                        </div>

                        <!-- Batch Results -->
                        <div id="batchResults" style="display:none;">
                            <div class="d-flex justify-content-between mb-2">
                                <div>
                                    <span class="badge bg-success" id="batchFoundCount">0 found</span>
                                    <span class="badge bg-danger" id="batchNotFoundCount">0 not found</span>
                                </div>
                                <small class="text-muted" id="batchTotalCount">Total: 0</small>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-sm table-striped">
                                    <thead>
                                        <tr>
                                            <th>Serial No</th>
                                            <th>Found</th>
                                            <th>Status</th>
                                            <th>Model</th>
                                            <th>Location</th>
                                        </tr>
                                    </thead>
                                    <tbody id="batchResultsBody"></tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
$(document).ready(function() {
    var role = '{{ auth()->user()->roles->first()?->name ?? "admin" }}';
    var baseDetailUrl = '/' + role + '/inventory-serials/';

    // =========================================================================
    // SINGLE LOOKUP - Autocomplete
    // =========================================================================
    var acTimer;
    $('#modalSerialInput').on('input', function() {
        clearTimeout(acTimer);
        var term = $(this).val().trim();
        var suggestions = $('#autocompleteSuggestions');

        if (term.length < 2) {
            suggestions.hide().empty();
            return;
        }

        acTimer = setTimeout(function() {
            $.ajax({
                url: '{{ route("api.serials.autocomplete") }}',
                data: { term: term },
                success: function(response) {
                    if (response.success && response.data.length > 0) {
                        var html = '';
                        response.data.forEach(function(item) {
                            var availClass = item.available ? 'list-group-item-success' : '';
                            html += '<a href="#" class="list-group-item list-group-item-action ac-item ' + availClass + '"'
                                  + ' data-serial="' + item.serial_no + '">'
                                  + '<div class="d-flex justify-content-between">'
                                  + '<strong>' + item.serial_no + '</strong>'
                                  + '<small class="text-muted">' + item.status_label + '</small>'
                                  + '</div>'
                                  + '<small class="text-muted">' + item.model + ' — ' + item.location + '</small>'
                                  + '</a>';
                        });
                        suggestions.html(html).show();
                    } else {
                        suggestions.hide().empty();
                    }
                }
            });
        }, 300);
    });

    // Autocomplete selection
    $(document).on('click', '.ac-item', function(e) {
        e.preventDefault();
        var serial = $(this).data('serial');
        $('#modalSerialInput').val(serial);
        $('#autocompleteSuggestions').hide().empty();
        performSingleLookup(serial);
    });

    // Search button / Enter key
    $('#btnModalSearch').on('click', function() {
        var serial = $('#modalSerialInput').val().trim();
        if (serial) performSingleLookup(serial);
    });
    $('#modalSerialInput').on('keypress', function(e) {
        if (e.which === 13) {
            e.preventDefault();
            var serial = $(this).val().trim();
            if (serial) performSingleLookup(serial);
        }
    });

    function performSingleLookup(serialNo) {
        $('#autocompleteSuggestions').hide();
        $('#singleResult, #singleNotFound').hide();

        $.ajax({
            url: '{{ route("api.serials.search") }}',
            data: { q: serialNo },
            success: function(response) {
                if (response.success && response.data.length > 0) {
                    // Show first exact match or first result
                    var match = response.data.find(function(s) {
                        return s.serial_no.toLowerCase() === serialNo.toLowerCase();
                    }) || response.data[0];

                    $('#resultSerialNo').text(match.serial_no);
                    $('#resultStatusBadge').html(match.status_badge);
                    $('#resultModel').text(match.model_name);
                    $('#resultCategory').text(match.category);
                    $('#resultLocation').text(match.location_name);
                    $('#resultGrn').text(match.grn_no);
                    $('#resultGrnDate').text(match.grn_date);
                    $('#resultWarranty').html(match.warranty);
                    $('#resultViewLink').attr('href', baseDetailUrl + match.id);
                    $('#singleResult').show();
                } else {
                    $('#notFoundMessage').text('Serial number "' + serialNo + '" not found in the system.');
                    $('#singleNotFound').show();
                }
            },
            error: function() {
                $('#notFoundMessage').text('Error searching for serial number.');
                $('#singleNotFound').show();
            }
        });
    }

    // =========================================================================
    // BATCH LOOKUP
    // =========================================================================
    $('#btnBatchSearch').on('click', function() {
        var text = $('#batchSerialInput').val().trim();
        if (!text) { showToast('Please enter serial numbers.', 'warning'); return; }

        var serials = text.split('\n').map(function(s) { return s.trim(); }).filter(function(s) { return s.length > 0; });
        if (serials.length === 0) { showToast('No valid serial numbers entered.', 'warning'); return; }
        if (serials.length > 100) { showToast('Maximum 100 serial numbers per batch.', 'warning'); return; }

        var btn = $(this);
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Searching...');

        $.ajax({
            url: '{{ route("api.serials.batch-lookup") }}',
            type: 'POST',
            data: { serial_numbers: serials },
            success: function(response) {
                btn.prop('disabled', false).html('<i class="bi bi-search me-1"></i> Lookup Batch');

                if (response.success) {
                    var html = '';
                    response.data.forEach(function(item) {
                        var rowClass = item.found ? '' : 'table-danger';
                        html += '<tr class="' + rowClass + '">'
                              + '<td>' + item.serial_no + '</td>'
                              + '<td>' + (item.found ? '<i class="bi bi-check-circle text-success"></i>' : '<i class="bi bi-x-circle text-danger"></i>') + '</td>'
                              + '<td>' + (item.status_label || '-') + '</td>'
                              + '<td>' + (item.model || '-') + '</td>'
                              + '<td>' + (item.location || '-') + '</td>'
                              + '</tr>';
                    });

                    $('#batchResultsBody').html(html);
                    $('#batchFoundCount').text(response.summary.found + ' found');
                    $('#batchNotFoundCount').text(response.summary.not_found + ' not found');
                    $('#batchTotalCount').text('Total: ' + response.summary.total);
                    $('#batchResults').show();
                }
            },
            error: function() {
                btn.prop('disabled', false).html('<i class="bi bi-search me-1"></i> Lookup Batch');
                showToast('Batch lookup failed.', 'error');
            }
        });
    });

    // Reset on modal close
    $('#serialLookupModal').on('hidden.bs.modal', function() {
        $('#modalSerialInput').val('');
        $('#autocompleteSuggestions').hide().empty();
        $('#singleResult, #singleNotFound, #batchResults').hide();
    });

    // Focus input when modal opens
    $('#serialLookupModal').on('shown.bs.modal', function() {
        $('#modalSerialInput').focus();
    });
});
</script>
@endpush
