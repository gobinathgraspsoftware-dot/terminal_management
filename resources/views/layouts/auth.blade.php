<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Login') - {{ config('app.name', 'TMS') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">

    <!-- Custom CSS -->
    <style>
        :root {
            --primary-color: #2563eb;
            --secondary-color: #64748b;
            --success-color: #10b981;
            --danger-color: #ef4444;
            --warning-color: #f59e0b;
            --info-color: #3b82f6;
            --dark-color: #1e293b;
            --light-color: #f8fafc;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .auth-container {
            width: 100%;
            max-width: 450px;
        }

        .auth-card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            overflow: hidden;
            animation: slideUp 0.5s ease-out;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .auth-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 40px 30px;
            text-align: center;
            color: white;
        }

        .auth-logo {
            width: 80px;
            height: 80px;
            margin: 0 auto 20px;
            background: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 36px;
            color: #667eea;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        .auth-title {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 8px;
        }

        .auth-subtitle {
            font-size: 14px;
            opacity: 0.9;
        }

        .auth-body {
            padding: 40px 30px;
        }

        .form-label {
            font-weight: 500;
            color: var(--dark-color);
            margin-bottom: 8px;
            font-size: 14px;
        }

        .form-control {
            padding: 12px 16px;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 15px;
            transition: all 0.3s;
        }

        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.1);
        }

        .input-group-text {
            background: #f8fafc;
            border: 2px solid #e2e8f0;
            border-right: none;
            color: var(--secondary-color);
        }

        .input-group .form-control {
            border-left: none;
        }

        .input-group:focus-within .input-group-text {
            border-color: var(--primary-color);
            background: #eff6ff;
            color: var(--primary-color);
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            padding: 12px 24px;
            font-weight: 600;
            border-radius: 8px;
            transition: all 0.3s;
            font-size: 15px;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.4);
        }

        .btn-primary:active {
            transform: translateY(0);
        }

        .form-check-input:checked {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }

        .form-check-label {
            font-size: 14px;
            color: var(--secondary-color);
        }

        .auth-link {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 500;
            font-size: 14px;
            transition: all 0.3s;
        }

        .auth-link:hover {
            color: #764ba2;
            text-decoration: underline;
        }

        .alert {
            border-radius: 8px;
            border: none;
            padding: 12px 16px;
            font-size: 14px;
        }

        .alert-success {
            background-color: #d1fae5;
            color: #065f46;
        }

        .alert-danger {
            background-color: #fee2e2;
            color: #991b1b;
        }

        .alert-info {
            background-color: #dbeafe;
            color: #1e40af;
        }

        .divider {
            display: flex;
            align-items: center;
            text-align: center;
            margin: 24px 0;
        }

        .divider::before,
        .divider::after {
            content: '';
            flex: 1;
            border-bottom: 1px solid #e2e8f0;
        }

        .divider span {
            padding: 0 16px;
            color: var(--secondary-color);
            font-size: 13px;
        }

        .auth-footer {
            text-align: center;
            padding: 20px 30px;
            background: #f8fafc;
            border-top: 1px solid #e2e8f0;
            font-size: 13px;
            color: var(--secondary-color);
        }

        .password-toggle {
            position: absolute;
            right: 16px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: var(--secondary-color);
            z-index: 10;
        }

        .password-toggle:hover {
            color: var(--primary-color);
        }

        .form-floating {
            position: relative;
        }

        @media (max-width: 576px) {
            .auth-header {
                padding: 30px 20px;
            }

            .auth-body {
                padding: 30px 20px;
            }

            .auth-title {
                font-size: 24px;
            }
        }
    </style>

    @stack('styles')
</head>
<body>
    <div class="auth-container">
        <div class="auth-card">
            <!-- Header -->
            <div class="auth-header">
                <div class="auth-logo">
                    <i class="fas fa-cash-register"></i>
                </div>
                <h1 class="auth-title">@yield('page-title', 'TMS')</h1>
                <p class="auth-subtitle">@yield('page-subtitle', 'Terminal Management System')</p>
            </div>

            <!-- Body -->
            <div class="auth-body">
                @yield('content')
            </div>

            <!-- Footer -->
            <div class="auth-footer">
                @yield('footer', '&copy; ' . date('Y') . ' TMS. All rights reserved.')
            </div>
        </div>
    </div>

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Common JavaScript -->
    <script>
        // CSRF Token Setup for AJAX
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        // Auto-hide alerts after 5 seconds
        $(document).ready(function() {
            setTimeout(function() {
                $('.alert').fadeOut('slow');
            }, 5000);
        });

        // Password toggle functionality
        function togglePassword(inputId) {
            const input = document.getElementById(inputId);
            const icon = event.currentTarget.querySelector('i');
            
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }
    </script>

    @stack('scripts')
</body>
</html>         