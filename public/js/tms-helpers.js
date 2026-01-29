/**
 * TMS JavaScript Helper Functions
 * Include this file in your app layout after jQuery and Bootstrap
 */

// ==========================================
// LOADING OVERLAY
// ==========================================

/**
 * Show loading overlay
 */
function showLoading(message = 'Processing...') {
    // Remove existing overlay if any
    hideLoading();
    
    const overlay = $(`
        <div id="loadingOverlay" class="position-fixed top-0 start-0 w-100 h-100 d-flex align-items-center justify-content-center" 
             style="background: rgba(0,0,0,0.5); z-index: 9999;">
            <div class="bg-white rounded-3 p-4 text-center shadow-lg">
                <div class="spinner-border text-primary mb-3" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mb-0 text-dark">${message}</p>
            </div>
        </div>
    `);
    
    $('body').append(overlay);
}

/**
 * Hide loading overlay
 */
function hideLoading() {
    $('#loadingOverlay').remove();
}

// ==========================================
// TOAST NOTIFICATIONS
// ==========================================

/**
 * Initialize toast container
 */
function initToastContainer() {
    if ($('#toastContainer').length === 0) {
        $('body').append(`
            <div id="toastContainer" class="position-fixed top-0 end-0 p-3" style="z-index: 9999;">
            </div>
        `);
    }
}

/**
 * Show toast notification
 * @param {string} message - Message to display
 * @param {string} type - Type: 'success', 'error', 'warning', 'info'
 * @param {number} duration - Duration in milliseconds (default: 3000)
 */
function showToast(message, type = 'info', duration = 3000) {
    initToastContainer();
    
    const icons = {
        success: 'bi-check-circle-fill',
        error: 'bi-x-circle-fill',
        warning: 'bi-exclamation-triangle-fill',
        info: 'bi-info-circle-fill'
    };
    
    const bgColors = {
        success: 'bg-success',
        error: 'bg-danger',
        warning: 'bg-warning',
        info: 'bg-info'
    };
    
    const textColors = {
        success: 'text-white',
        error: 'text-white',
        warning: 'text-dark',
        info: 'text-white'
    };
    
    const toastId = 'toast_' + Date.now();
    const icon = icons[type] || icons.info;
    const bgColor = bgColors[type] || bgColors.info;
    const textColor = textColors[type] || textColors.info;
    
    const toast = $(`
        <div id="${toastId}" class="toast ${bgColor} ${textColor}" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="toast-header ${bgColor} ${textColor}">
                <i class="bi ${icon} me-2"></i>
                <strong class="me-auto">${type.charAt(0).toUpperCase() + type.slice(1)}</strong>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
            <div class="toast-body">
                ${message}
            </div>
        </div>
    `);
    
    $('#toastContainer').append(toast);
    
    const bsToast = new bootstrap.Toast(document.getElementById(toastId), {
        autohide: true,
        delay: duration
    });
    
    bsToast.show();
    
    // Remove from DOM after hidden
    document.getElementById(toastId).addEventListener('hidden.bs.toast', function () {
        $(this).remove();
    });
}

// ==========================================
// SWEETALERT2 ALTERNATIVES (if not using SweetAlert2)
// ==========================================

/**
 * Show confirmation dialog using Bootstrap modal
 * @param {object} options - Configuration options
 * @returns {Promise}
 */
function showConfirm(options = {}) {
    return new Promise((resolve) => {
        const defaults = {
            title: 'Confirm Action',
            message: 'Are you sure you want to proceed?',
            confirmText: 'Yes, Proceed',
            cancelText: 'Cancel',
            confirmClass: 'btn-primary',
            icon: 'bi-question-circle'
        };
        
        const config = { ...defaults, ...options };
        
        // Remove existing modal
        $('#confirmModal').remove();
        
        const modal = $(`
            <div class="modal fade" id="confirmModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">
                                <i class="bi ${config.icon} me-2"></i>${config.title}
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <p class="mb-0">${config.message}</p>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">${config.cancelText}</button>
                            <button type="button" class="btn ${config.confirmClass}" id="confirmModalBtn">${config.confirmText}</button>
                        </div>
                    </div>
                </div>
            </div>
        `);
        
        $('body').append(modal);
        
        const bsModal = new bootstrap.Modal(document.getElementById('confirmModal'));
        
        $('#confirmModalBtn').on('click', function() {
            bsModal.hide();
            resolve(true);
        });
        
        document.getElementById('confirmModal').addEventListener('hidden.bs.modal', function () {
            $(this).remove();
            resolve(false);
        });
        
        bsModal.show();
    });
}

/**
 * Show delete confirmation
 */
function confirmDelete(itemName = 'this item') {
    return showConfirm({
        title: 'Delete Confirmation',
        message: `Are you sure you want to delete ${itemName}? This action cannot be undone.`,
        confirmText: 'Yes, Delete',
        cancelText: 'Cancel',
        confirmClass: 'btn-danger',
        icon: 'bi-trash'
    });
}

// ==========================================
// AJAX SETUP
// ==========================================

/**
 * Setup AJAX defaults
 */
$(document).ready(function() {
    // Set CSRF token for all AJAX requests
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });
    
    // Global AJAX error handler
    $(document).ajaxError(function(event, xhr, settings, error) {
        hideLoading();
        
        if (xhr.status === 401) {
            showToast('Session expired. Please login again.', 'error');
            setTimeout(() => {
                window.location.href = '/login';
            }, 2000);
        } else if (xhr.status === 403) {
            showToast('You do not have permission to perform this action.', 'error');
        } else if (xhr.status === 419) {
            showToast('Session expired. Please refresh the page.', 'error');
        } else if (xhr.status === 500) {
            showToast('Server error. Please try again later.', 'error');
        }
    });
});

// ==========================================
// FORM HELPERS
// ==========================================

/**
 * Clear form validation errors
 */
function clearFormErrors(form) {
    $(form).find('.is-invalid').removeClass('is-invalid');
    $(form).find('.invalid-feedback').remove();
}

/**
 * Show form validation errors
 */
function showFormErrors(form, errors) {
    clearFormErrors(form);
    
    $.each(errors, function(field, messages) {
        // Handle nested field names (e.g., sla_rules.0.sla_type)
        let fieldName = field.replace(/\./g, '\\[').replace(/\[/g, '[').replace(/\]/g, ']');
        if (field.includes('.')) {
            fieldName = field.split('.').join('][');
            fieldName = fieldName.replace('][', '[') + ']';
        }
        
        let input = $(form).find(`[name="${field}"], [name="${fieldName}"]`);
        
        if (input.length === 0) {
            // Try with array notation
            input = $(form).find(`[name="${field.replace(/\.\d+\./g, '[')}"]`);
        }
        
        if (input.length > 0) {
            input.addClass('is-invalid');
            input.after(`<div class="invalid-feedback">${messages[0]}</div>`);
        } else {
            // Show general error toast if field not found
            showToast(messages[0], 'error');
        }
    });
}

/**
 * Reset form to initial state
 */
function resetForm(form) {
    clearFormErrors(form);
    $(form)[0].reset();
    $(form).find('select').trigger('change'); // Update Select2
}

// ==========================================
// UTILITY FUNCTIONS
// ==========================================

/**
 * Format number with commas
 */
function formatNumber(num, decimals = 2) {
    return parseFloat(num).toLocaleString('en-MY', {
        minimumFractionDigits: decimals,
        maximumFractionDigits: decimals
    });
}

/**
 * Format currency (MYR)
 */
function formatCurrency(amount) {
    return 'RM ' + formatNumber(amount, 2);
}

/**
 * Format date
 */
function formatDate(date, format = 'DD/MM/YYYY') {
    if (!date) return '-';
    const d = new Date(date);
    const day = String(d.getDate()).padStart(2, '0');
    const month = String(d.getMonth() + 1).padStart(2, '0');
    const year = d.getFullYear();
    
    return format
        .replace('DD', day)
        .replace('MM', month)
        .replace('YYYY', year);
}

/**
 * Debounce function
 */
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

/**
 * Copy text to clipboard
 */
function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(() => {
        showToast('Copied to clipboard!', 'success', 2000);
    }).catch(() => {
        showToast('Failed to copy', 'error');
    });
}

console.log('TMS Helpers loaded successfully');
