<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'TMS - Terminal Management System')</title>

    <!-- Bootstrap 5.3.2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

    <!-- DataTables CSS -->
    <link href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css" rel="stylesheet">

    <!-- Select2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet">

    <!-- Custom Styles -->
    <style>
        :root {
            --sidebar-width: 250px;
            --header-height: 60px;
            --primary-color: #0d6efd;
            --secondary-color: #6c757d;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fa;
        }

        /* Header */
        .app-header {
            height: var(--header-height);
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1030;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .app-header .navbar-brand {
            font-weight: 700;
            font-size: 1.5rem;
            color: white !important;
        }

        .app-header .nav-link {
            color: rgba(255,255,255,0.9) !important;
        }

        .app-header .nav-link:hover {
            color: white !important;
        }

        /* Sidebar */
        .app-sidebar {
            position: fixed;
            top: var(--header-height);
            left: 0;
            bottom: 0;
            width: var(--sidebar-width);
            background: white;
            box-shadow: 2px 0 10px rgba(0,0,0,0.05);
            overflow-y: auto;
            transition: transform 0.3s ease;
            z-index: 1020;
        }

        .app-sidebar.collapsed {
            transform: translateX(-100%);
        }

        /* Main Content */
        .app-content {
            margin-top: var(--header-height);
            margin-left: var(--sidebar-width);
            padding: 25px;
            min-height: calc(100vh - var(--header-height));
            transition: margin-left 0.3s ease;
        }

        .app-content.expanded {
            margin-left: 0;
        }

        /* Mobile Responsive */
        @media (max-width: 768px) {
            .app-sidebar {
                transform: translateX(-100%);
            }

            .app-sidebar.show {
                transform: translateX(0);
            }

            .app-content {
                margin-left: 0;
            }
        }

        /* Page Header */
        .page-header {
            background: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 25px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }

        .page-header h1 {
            margin: 0;
            font-size: 1.75rem;
            font-weight: 600;
            color: #2d3748;
        }

        .page-header .breadcrumb {
            margin: 10px 0 0 0;
            background: transparent;
            padding: 0;
        }

        /* Cards */
        .card {
            border: none;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            margin-bottom: 25px;
        }

        .card-header {
            background: white;
            border-bottom: 1px solid #e2e8f0;
            font-weight: 600;
            color: #2d3748;
            padding: 15px 20px;
        }

        /* Stats Cards */
        .stats-card {
            background: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .stats-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }

        .stats-card .stats-icon {
            width: 50px;
            height: 50px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }

        .stats-card .stats-value {
            font-size: 2rem;
            font-weight: 700;
            color: #2d3748;
            margin: 10px 0 5px 0;
        }

        .stats-card .stats-label {
            color: #718096;
            font-size: 0.875rem;
            font-weight: 500;
        }

        /* Buttons */
        .btn {
            border-radius: 6px;
            font-weight: 500;
            padding: 8px 16px;
        }

        /* Tables */
        .table {
            background: white;
        }

        /* Loading Overlay */
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(255,255,255,0.9);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 9999;
        }

        /* Alerts */
        .alert {
            border-radius: 8px;
            border: none;
        }

        /* Badge */
        .badge {
            font-weight: 500;
            padding: 0.35em 0.65em;
        }

        /* User Dropdown */
        .user-dropdown .dropdown-menu {
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            border: none;
            margin-top: 10px;
        }

        /* Sidebar Toggle */
        .sidebar-toggle {
            background: rgba(255,255,255,0.1);
            border: none;
            color: white;
            width: 35px;
            height: 35px;
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background 0.2s;
        }

        .sidebar-toggle:hover {
            background: rgba(255,255,255,0.2);
        }
    </style>

    @stack('styles')
</head>
<body>
    <!-- Header -->
    <nav class="navbar navbar-expand-lg app-header">
        <div class="container-fluid">
            <!-- Sidebar Toggle -->
            <button class="sidebar-toggle me-3" id="sidebarToggle">
                <i class="bi bi-list"></i>
            </button>

            <!-- Brand -->
            <a class="navbar-brand" href="{{ route('dashboard') }}">
                <i class="bi bi-terminal"></i> TMS
            </a>

            <!-- Right Side -->
            <div class="ms-auto d-flex align-items-center">
                <!-- Notifications -->
                <div class="dropdown me-3">
                    <button class="btn btn-link text-white position-relative" type="button" id="notificationsDropdown" data-bs-toggle="dropdown">
                        <i class="bi bi-bell fs-5"></i>
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size: 0.65rem;">
                            3
                        </span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end" style="width: 300px;">
                        <li><h6 class="dropdown-header">Notifications</h6></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="#"><small>No new notifications</small></a></li>
                    </ul>
                </div>

                <!-- User Dropdown -->
                <div class="dropdown user-dropdown">
                    <button class="btn btn-link text-white text-decoration-none dropdown-toggle" type="button" id="userDropdown" data-bs-toggle="dropdown">
                        <i class="bi bi-person-circle me-1"></i>
                        {{ auth()->user()->name }}
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li>
                            <div class="dropdown-header">
                                <strong>{{ auth()->user()->name }}</strong><br>
                                <small class="text-muted">{{ auth()->user()->email }}</small>
                            </div>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="#"><i class="bi bi-person me-2"></i> Profile</a></li>
                        <li><a class="dropdown-item" href="#"><i class="bi bi-gear me-2"></i> Settings</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <form action="{{ route('logout') }}" method="POST">
                                @csrf
                                <button type="submit" class="dropdown-item text-danger">
                                    <i class="bi bi-box-arrow-right me-2"></i> Logout
                                </button>
                            </form>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>

    <!-- Sidebar -->
    <aside class="app-sidebar" id="sidebar">
        @include('layouts.partials.sidebar')
    </aside>

    <!-- Main Content -->
    <main class="app-content" id="mainContent">
        <!-- Flash Messages -->
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle me-2"></i>
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-circle me-2"></i>
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if(session('warning'))
            <div class="alert alert-warning alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle me-2"></i>
                {{ session('warning') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if(session('info'))
            <div class="alert alert-info alert-dismissible fade show" role="alert">
                <i class="bi bi-info-circle me-2"></i>
                {{ session('info') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <!-- Page Content -->
        @yield('content')
    </main>

    <!-- jQuery 3.7.1 -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

    <!-- Bootstrap 5.3.2 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

    <!-- DataTables -->
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>

    <!-- Select2 -->
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- Global JavaScript -->
    <script>
        // CSRF Token Setup for AJAX
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        // Sidebar Toggle
        $(document).ready(function() {
            const sidebar = $('#sidebar');
            const mainContent = $('#mainContent');
            const sidebarToggle = $('#sidebarToggle');

            sidebarToggle.on('click', function() {
                if (window.innerWidth > 768) {
                    sidebar.toggleClass('collapsed');
                    mainContent.toggleClass('expanded');
                } else {
                    sidebar.toggleClass('show');
                }
            });

            // Close sidebar on mobile when clicking outside
            $(document).on('click', function(e) {
                if (window.innerWidth <= 768) {
                    if (!$(e.target).closest('#sidebar, #sidebarToggle').length) {
                        sidebar.removeClass('show');
                    }
                }
            });

            // Auto-dismiss alerts after 5 seconds
            setTimeout(function() {
                $('.alert').fadeOut('slow');
            }, 5000);
        });

        // Global loading overlay function
        function showLoading() {
            $('body').append('<div class="loading-overlay"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></div>');
        }

        function hideLoading() {
            $('.loading-overlay').remove();
        }

        // Global success toast
        function showToast(message, type = 'success') {
            const Toast = Swal.mixin({
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true,
            });

            Toast.fire({
                icon: type,
                title: message
            });
        }

        // Global confirm dialog
        function confirmAction(title, text, confirmCallback) {
            Swal.fire({
                title: title,
                text: text,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#0d6efd',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, proceed!',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    confirmCallback();
                }
            });
        }
    </script>

    @stack('scripts')
</body>
</html>
