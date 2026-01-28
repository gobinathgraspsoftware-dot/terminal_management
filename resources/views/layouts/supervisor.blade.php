<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Supervisor') - TMS</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    
    <style>
        :root { --sidebar-width: 260px; --header-height: 60px; --primary-color: #0d6efd; --sidebar-bg: #1e3a5f; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f8f9fa; }
        .sidebar { position: fixed; top: 0; left: 0; width: var(--sidebar-width); height: 100vh; background: var(--sidebar-bg); z-index: 1000; transition: all 0.3s ease; }
        .sidebar-brand { height: var(--header-height); display: flex; align-items: center; padding: 0 1.5rem; background: rgba(0,0,0,0.1); }
        .sidebar-brand h4 { color: #fff; margin: 0; font-weight: 700; }
        .sidebar-nav { padding: 1rem 0; height: calc(100vh - var(--header-height)); overflow-y: auto; }
        .sidebar-nav .nav-link { color: rgba(255,255,255,0.7); padding: 0.75rem 1.5rem; display: flex; align-items: center; transition: all 0.2s ease; border-left: 3px solid transparent; }
        .sidebar-nav .nav-link:hover, .sidebar-nav .nav-link.active { color: #fff; background: rgba(255,255,255,0.1); border-left-color: var(--primary-color); }
        .sidebar-nav .nav-link i { width: 24px; margin-right: 10px; text-align: center; }
        .nav-section-title { color: rgba(255,255,255,0.4); font-size: 0.75rem; text-transform: uppercase; letter-spacing: 1px; padding: 1rem 1.5rem 0.5rem; margin-top: 0.5rem; }
        .main-content { margin-left: var(--sidebar-width); min-height: 100vh; }
        .main-header { height: var(--header-height); background: #fff; border-bottom: 1px solid #e9ecef; display: flex; align-items: center; justify-content: space-between; padding: 0 1.5rem; position: sticky; top: 0; z-index: 100; }
        .page-content { padding: 1.5rem; }
        .card { border: none; box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075); }
        .user-dropdown .dropdown-toggle::after { display: none; }
        .user-dropdown .user-avatar { width: 36px; height: 36px; border-radius: 50%; object-fit: cover; }
        @media (max-width: 991.98px) { .sidebar { transform: translateX(-100%); } .sidebar.show { transform: translateX(0); } .main-content { margin-left: 0; } }
        .sidebar-overlay { display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 999; }
        .sidebar-overlay.show { display: block; }
    </style>
    @stack('styles')
</head>
<body>
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-brand">
            <h4>TMS <span class="badge bg-primary">Supervisor</span></h4>
        </div>
        <nav class="sidebar-nav">
            <ul class="nav flex-column">
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('supervisor.dashboard') ? 'active' : '' }}" href="{{ route('supervisor.dashboard') }}">
                        <i class="fas fa-tachometer-alt"></i><span>Dashboard</span>
                    </a>
                </li>
                <div class="nav-section-title">Team</div>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('supervisor.teams.*') ? 'active' : '' }}" href="{{ route('supervisor.teams.index') }}">
                        <i class="fas fa-users"></i><span>My Team</span>
                    </a>
                </li>
                <div class="nav-section-title">Operations</div>
                <li class="nav-item"><a class="nav-link" href="#"><i class="fas fa-clipboard-list"></i><span>Job Orders</span></a></li>
                <li class="nav-item"><a class="nav-link" href="#"><i class="fas fa-tasks"></i><span>Assign Jobs</span></a></li>
                <div class="nav-section-title">Finance</div>
                <li class="nav-item"><a class="nav-link" href="#"><i class="fas fa-receipt"></i><span>Team Claims</span></a></li>
                <li class="nav-item"><a class="nav-link" href="#"><i class="fas fa-dollar-sign"></i><span>Commissions</span></a></li>
                <div class="nav-section-title">Reports</div>
                <li class="nav-item"><a class="nav-link" href="#"><i class="fas fa-chart-bar"></i><span>Team Reports</span></a></li>
            </ul>
        </nav>
    </aside>
    
    <main class="main-content">
        <header class="main-header">
            <div class="d-flex align-items-center">
                <button class="btn btn-link text-dark d-lg-none me-2" id="sidebarToggle"><i class="fas fa-bars"></i></button>
                @yield('page-header')
            </div>
            <div class="d-flex align-items-center">
                <div class="d-none d-md-flex me-4">
                    <div class="text-end me-3">
                        <small class="text-muted d-block">Team Members</small>
                        <strong class="text-primary">{{ auth()->user()->technicians()->count() ?? 0 }}</strong>
                    </div>
                </div>
                <div class="dropdown user-dropdown">
                    <button class="btn btn-link text-dark dropdown-toggle d-flex align-items-center" data-bs-toggle="dropdown">
                        <img src="{{ auth()->user()->avatar_url ?? 'https://ui-avatars.com/api/?name=' . urlencode(auth()->user()->name) }}" alt="Avatar" class="user-avatar me-2">
                        <span class="d-none d-md-inline">{{ auth()->user()->name }}</span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><span class="dropdown-item-text text-muted small">{{ auth()->user()->email }}</span></li>
                        <li><span class="dropdown-item-text"><span class="badge bg-primary">Supervisor</span></span></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="#"><i class="fas fa-user me-2"></i>Profile</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <form action="{{ route('logout') }}" method="POST">
                                @csrf
                                <button type="submit" class="dropdown-item text-danger"><i class="fas fa-sign-out-alt me-2"></i>Logout</button>
                            </form>
                        </li>
                    </ul>
                </div>
            </div>
        </header>
        
        <div class="page-content">
            @if(session('success'))<div class="alert alert-success alert-dismissible fade show"><i class="fas fa-check-circle me-2"></i>{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>@endif
            @if(session('error'))<div class="alert alert-danger alert-dismissible fade show"><i class="fas fa-exclamation-circle me-2"></i>{{ session('error') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>@endif
            @yield('content')
        </div>
    </main>
    
    <div class="sidebar-overlay" id="sidebarOverlay"></div>
    
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <script>
        $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') } });
        $('#sidebarToggle').on('click', function() { $('#sidebar').toggleClass('show'); $('#sidebarOverlay').toggleClass('show'); });
        $('#sidebarOverlay').on('click', function() { $('#sidebar').removeClass('show'); $(this).removeClass('show'); });
        $(document).ready(function() { $('.select2').select2({ theme: 'bootstrap-5' }); });
        function showToast(type, message) { Swal.fire({ icon: type, title: message, toast: true, position: 'top-end', showConfirmButton: false, timer: 3000, timerProgressBar: true }); }
    </script>
    @stack('scripts')
</body>
</html>
