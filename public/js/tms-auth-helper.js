/**
 * TMS Authentication Helper
 * Client-side JavaScript for handling authentication in AJAX requests
 * 
 * Requirements:
 * - jQuery 3.7+
 * - SweetAlert2 (optional, for better alerts)
 * - Bootstrap 5 (for styling)
 * 
 * Usage: Include this file in your main layout after jQuery
 */

(function($) {
    'use strict';

    // =========================================
    // AJAX SETUP
    // =========================================
    
    /**
     * Global AJAX setup with CSRF token
     */
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
            'X-Requested-With': 'XMLHttpRequest'
        }
    });

    // =========================================
    // ERROR HANDLERS
    // =========================================

    /**
     * Global AJAX error handler
     */
    $(document).ajaxError(function(event, xhr, settings, thrownError) {
        console.log('AJAX Error:', {
            status: xhr.status,
            url: settings.url,
            error: thrownError
        });

        // Handle authentication errors (401)
        if (xhr.status === 401) {
            handleAuthenticationError(xhr);
            return;
        }

        // Handle authorization errors (403)
        if (xhr.status === 403) {
            handleAuthorizationError(xhr);
            return;
        }

        // Handle CSRF token mismatch (419)
        if (xhr.status === 419) {
            handleCSRFError(xhr);
            return;
        }

        // Handle server errors (500)
        if (xhr.status === 500) {
            handleServerError(xhr);
            return;
        }

        // Handle validation errors (422)
        if (xhr.status === 422) {
            handleValidationError(xhr);
            return;
        }
    });

    /**
     * Handle authentication errors (401)
     */
    function handleAuthenticationError(xhr) {
        const response = xhr.responseJSON || {};
        const message = response.message || 'Your session has expired. Please login again.';
        const redirect = response.redirect || '/login';

        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'warning',
                title: 'Session Expired',
                text: message,
                confirmButtonText: 'Login Again',
                allowOutsideClick: false,
                allowEscapeKey: false
            }).then(() => {
                window.location.href = redirect;
            });
        } else {
            alert(message);
            window.location.href = redirect;
        }
    }

    /**
     * Handle authorization errors (403)
     */
    function handleAuthorizationError(xhr) {
        const response = xhr.responseJSON || {};
        const message = response.message || 'You do not have permission to access this resource.';

        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'error',
                title: 'Access Denied',
                text: message,
                confirmButtonText: 'OK'
            });
        } else {
            alert(message);
        }
    }

    /**
     * Handle CSRF token errors (419)
     */
    function handleCSRFError(xhr) {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'warning',
                title: 'Page Expired',
                text: 'Your page has expired. Refreshing...',
                timer: 2000,
                showConfirmButton: false
            }).then(() => {
                window.location.reload();
            });
        } else {
            alert('Your page has expired. Please refresh.');
            window.location.reload();
        }
    }

    /**
     * Handle server errors (500)
     */
    function handleServerError(xhr) {
        const message = 'An error occurred while processing your request. Please try again.';
        
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'error',
                title: 'Server Error',
                text: message,
                confirmButtonText: 'OK'
            });
        } else {
            alert(message);
        }
    }

    /**
     * Handle validation errors (422)
     */
    function handleValidationError(xhr) {
        const response = xhr.responseJSON || {};
        const errors = response.errors || {};
        
        // Display first error message
        const firstError = Object.values(errors)[0];
        const message = Array.isArray(firstError) ? firstError[0] : firstError;

        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'warning',
                title: 'Validation Error',
                text: message || 'Please check your input and try again.',
                confirmButtonText: 'OK'
            });
        } else {
            alert(message || 'Please check your input and try again.');
        }

        // Highlight error fields if form exists
        displayFormErrors(errors);
    }

    /**
     * Display validation errors on form fields
     */
    function displayFormErrors(errors) {
        // Clear existing errors
        $('.is-invalid').removeClass('is-invalid');
        $('.invalid-feedback').remove();

        // Add new errors
        Object.keys(errors).forEach(function(field) {
            const $field = $('[name="' + field + '"]');
            const errorMessages = Array.isArray(errors[field]) ? errors[field] : [errors[field]];
            
            if ($field.length) {
                $field.addClass('is-invalid');
                $field.after('<div class="invalid-feedback d-block">' + errorMessages[0] + '</div>');
            }
        });
    }

    // =========================================
    // AUTH CHECK
    // =========================================

    /**
     * Check authentication status
     */
    window.checkAuth = function() {
        return $.get('/api/auth/check')
            .done(function(data) {
                console.log('Auth check successful:', data);
                return data;
            })
            .fail(function() {
                console.log('Auth check failed - user not authenticated');
                return null;
            });
    };

    /**
     * Periodic auth check (optional - enable if needed)
     */
    function startAuthChecker(intervalMinutes) {
        const interval = (intervalMinutes || 5) * 60 * 1000; // Default: 5 minutes
        
        setInterval(function() {
            $.get('/api/auth/check')
                .fail(function() {
                    console.log('Auth check failed - redirecting to login');
                    window.location.href = '/login';
                });
        }, interval);
    }

    // Uncomment to enable periodic auth check
    // startAuthChecker(5); // Check every 5 minutes

    // =========================================
    // SESSION TIMEOUT WARNING
    // =========================================

    /**
     * Warn user before session timeout
     */
    function setupSessionTimeoutWarning(timeoutMinutes) {
        const timeout = (timeoutMinutes || 60) * 60 * 1000; // Default: 60 minutes
        const warningTime = timeout - (5 * 60 * 1000); // Warn 5 minutes before timeout

        let warningTimer = null;
        let timeoutTimer = null;

        function resetTimers() {
            clearTimeout(warningTimer);
            clearTimeout(timeoutTimer);

            // Show warning 5 minutes before timeout
            warningTimer = setTimeout(function() {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Session Expiring Soon',
                        text: 'Your session will expire in 5 minutes. Do you want to continue?',
                        showCancelButton: true,
                        confirmButtonText: 'Continue',
                        cancelButtonText: 'Logout'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            // Refresh session by making a request
                            $.get('/api/auth/check');
                            resetTimers();
                        } else {
                            // Logout
                            window.location.href = '/logout';
                        }
                    });
                }
            }, warningTime);

            // Auto logout on timeout
            timeoutTimer = setTimeout(function() {
                window.location.href = '/login?timeout=1';
            }, timeout);
        }

        // Reset timers on user activity
        $(document).on('click keypress mousemove', function() {
            resetTimers();
        });

        // Initialize timers
        resetTimers();
    }

    // Uncomment to enable session timeout warning
    // setupSessionTimeoutWarning(60); // 60 minutes timeout

    // =========================================
    // LOADING INDICATOR
    // =========================================

    /**
     * Show loading indicator during AJAX requests
     */
    let ajaxRequestCount = 0;

    $(document).ajaxStart(function() {
        ajaxRequestCount++;
        if (ajaxRequestCount === 1) {
            showLoadingIndicator();
        }
    });

    $(document).ajaxComplete(function() {
        ajaxRequestCount--;
        if (ajaxRequestCount === 0) {
            hideLoadingIndicator();
        }
    });

    function showLoadingIndicator() {
        if ($('#ajax-loading-indicator').length === 0) {
            $('body').append(`
                <div id="ajax-loading-indicator" style="
                    position: fixed;
                    top: 0;
                    left: 0;
                    right: 0;
                    bottom: 0;
                    background: rgba(0,0,0,0.3);
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    z-index: 9999;
                ">
                    <div class="spinner-border text-light" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            `);
        }
    }

    function hideLoadingIndicator() {
        $('#ajax-loading-indicator').remove();
    }

    // =========================================
    // UTILITY FUNCTIONS
    // =========================================

    /**
     * Make authenticated AJAX request
     */
    window.ajaxRequest = function(url, method, data, success, error) {
        return $.ajax({
            url: url,
            type: method || 'GET',
            data: data || {},
            dataType: 'json',
            success: success || function(response) {
                console.log('Request successful:', response);
            },
            error: error || function(xhr) {
                console.log('Request failed:', xhr);
            }
        });
    };

    /**
     * Check if user has specific role
     */
    window.hasRole = function(role) {
        return checkAuth().then(function(data) {
            return data && data.role === role;
        });
    };

    /**
     * Get current user info
     */
    window.getCurrentUser = function() {
        return checkAuth().then(function(data) {
            return data ? data.user : null;
        });
    };

    // =========================================
    // INITIALIZATION
    // =========================================

    $(document).ready(function() {
        console.log('TMS Auth Helper initialized');

        // Add auth status indicator to header (optional)
        const authStatus = $('meta[name="auth-status"]').attr('content');
        if (authStatus === 'authenticated') {
            console.log('User is authenticated');
        }

        // Check for timeout parameter in URL
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('timeout') === '1') {
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'warning',
                    title: 'Session Expired',
                    text: 'Your session has expired due to inactivity.',
                    confirmButtonText: 'OK'
                });
            }
        }
    });

})(jQuery);