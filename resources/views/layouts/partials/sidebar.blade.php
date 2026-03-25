@php
    $user = auth()->user();
    $role = $user->roles->first()?->name;
    $currentRoute = request()->route()->getName();
    $currentView = request()->query('view', '');
@endphp

<div class="sidebar-content p-3">
    <!-- User Info -->
    <div class="user-info mb-4 p-3 bg-light rounded">
        <div class="d-flex align-items-center">
            <div class="avatar me-3">
                @if($user->avatar)
                    <img src="{{ asset('storage/' . $user->avatar) }}"
                         alt="{{ $user->name }}"
                         class="rounded-circle"
                         style="width: 45px; height: 45px; object-fit: cover;">
                @else
                    <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 45px; height: 45px; font-size: 1.2rem;">
                        {{ strtoupper(substr($user->name, 0, 1)) }}
                    </div>
                @endif
            </div>
            <div class="flex-grow-1">
                <div class="fw-bold text-truncate">{{ $user->name }}</div>
                <small class="text-muted text-uppercase">
                    <span class="badge bg-{{ $role === 'admin' ? 'danger' : ($role === 'supervisor' ? 'warning' : 'info') }}">
                        {{ $role }}
                    </span>
                </small>
            </div>
        </div>
    </div>

    <!-- Navigation Menu -->
    <nav class="sidebar-nav">
        @if($role === 'admin')
            {{-- ============================================= --}}
            {{-- ADMIN MENU --}}
            {{-- ============================================= --}}

            <!-- Dashboard -->
            <div class="nav-section mb-3">
                <small class="text-muted text-uppercase fw-bold px-3">Main</small>
                <ul class="nav flex-column mt-2">
                    <li class="nav-item">
                        <a class="nav-link {{ str_contains($currentRoute, 'admin.dashboard') ? 'active' : '' }}"
                           href="{{ route('admin.dashboard') }}">
                            <i class="bi bi-speedometer2 me-2"></i> Dashboard
                        </a>
                    </li>
                </ul>
            </div>

            <!-- User Management Section -->
            @php
                $isUserRoute = str_contains($currentRoute ?? '', 'admin.users');
                $isRoleRoute = str_contains($currentRoute ?? '', 'admin.roles');
                $isPermissionRoute = str_contains($currentRoute ?? '', 'admin.permissions');
            @endphp
            <div class="nav-section mb-3">
                <small class="text-muted text-uppercase fw-bold px-3">User Management</small>
                <ul class="nav flex-column mt-2">
                    <li class="nav-item">
                        <a class="nav-link {{ $isUserRoute ? 'active' : '' }}"
                           href="{{ route('admin.users.index') }}">
                            <i class="bi bi-people me-2"></i> Users
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ $isRoleRoute ? 'active' : '' }}"
                           href="{{ route('admin.roles.index') }}">
                            <i class="bi bi-shield-lock me-2"></i> Roles
                        </a>
                    </li>
                    {{-- <li class="nav-item">
                        <a class="nav-link {{ $isPermissionRoute && !str_contains($currentRoute, 'matrix') ? 'active' : '' }}"
                           href="{{ route('admin.permissions.index') }}">
                            <i class="bi bi-key me-2"></i> Permissions
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ str_contains($currentRoute ?? '', 'admin.permissions.matrix') ? 'active' : '' }}"
                           href="{{ route('admin.permissions.matrix') }}">
                            <i class="bi bi-grid-3x3-gap me-2"></i> Permission Matrix
                        </a>
                    </li> --}}
                </ul>
            </div>

            <!-- Team Management Section -->
            @php
                $isTeamRoute = str_contains($currentRoute ?? '', 'admin.teams');
            @endphp
            <div class="nav-section mb-3">
                <small class="text-muted text-uppercase fw-bold px-3">Team Management</small>
                <ul class="nav flex-column mt-2">
                    <li class="nav-item">
                        <a class="nav-link {{ $isTeamRoute && ($currentView === '' || $currentView === 'all') ? 'active' : '' }}"
                           href="{{ route('admin.teams.index') }}">
                            <i class="bi bi-diagram-3 me-2"></i> All Teams
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ $isTeamRoute && $currentView === 'supervisors' ? 'active' : '' }}"
                           href="{{ route('admin.teams.index') }}?view=supervisors">
                            <i class="bi bi-person-badge me-2"></i> Supervisors
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ $isTeamRoute && $currentView === 'technicians' ? 'active' : '' }}"
                           href="{{ route('admin.teams.index') }}?view=technicians">
                            <i class="bi bi-person-gear me-2"></i> Technicians
                        </a>
                    </li>
                </ul>
            </div>

            @php
                $isTicketRoute = str_contains($currentRoute ?? '', 'admin.tickets');
            @endphp
            @can('view_tickets')
            <div class="nav-section mb-3">
                <small class="text-muted text-uppercase fw-bold px-3">Ticket Management</small>
                <ul class="nav flex-column mt-2">
                    <li class="nav-item">
                        <a class="nav-link {{ $isTicketRoute ? 'active' : '' }}"
                        href="{{ route('admin.tickets.index') }}">
                            <i class="bi bi-ticket-detailed me-2"></i> Tickets
                        </a>
                    </li>
                </ul>
            </div>
            @endcan

            <!-- Master Data Section (Cleaned: Removed Partners, Clients, Sites, Depots, Rate Cards) -->
            @php
                $isVendorRoute = str_contains($currentRoute ?? '', 'admin.vendors');
                $isJobTypeRoute = str_contains($currentRoute ?? '', 'admin.job-types');
                $isVendorTypeRoute = str_contains($currentRoute ?? '', 'admin.vendor-types');
                $isJobCategoryRoute = str_contains($currentRoute ?? '', 'admin.job-categories');
            @endphp
            <div class="nav-section mb-3">
                <small class="text-muted text-uppercase fw-bold px-3">Master Data</small>
                <ul class="nav flex-column mt-2">
                    @can('view_vendor_types')
                    <li class="nav-item">
                        <a class="nav-link {{ $isVendorTypeRoute ? 'active' : '' }}"
                        href="{{ route('admin.vendor-types.index') }}">
                            <i class="bi bi-tags me-2"></i> Vendor Types
                        </a>
                    </li>
                    @endcan
                    <li class="nav-item">
                        <a class="nav-link {{ $isVendorRoute ? 'active' : '' }}"
                           href="{{ route('admin.vendors.index') }}">
                            <i class="bi bi-truck me-2"></i> Vendors
                        </a>
                    </li>
                    @can('view_job_types')
                    <li class="nav-item">
                        <a class="nav-link {{ $isJobTypeRoute ? 'active' : '' }}"
                        href="{{ route('admin.job-types.index') }}">
                            <i class="bi bi-briefcase me-2"></i> Job Types
                        </a>
                    </li>
                    @endcan
                    @can('view_job_categories')
                    <li class="nav-item">
                        <a class="nav-link {{ $isJobCategoryRoute ? 'active' : '' }}"
                        href="{{ route('admin.job-categories.index') }}">
                            <i class="bi bi-folder me-2"></i> Job Categories
                        </a>
                    </li>
                    @endcan
                </ul>
            </div>

            <!-- Claim Management Section -->
            @canany(['view_all_claims', 'view_claims', 'verify_claims', 'bulk_pay_claims'])
            <div class="nav-section mb-3">
                <small class="text-muted text-uppercase fw-bold px-3">Claim Management</small>
                <ul class="nav flex-column mt-2">
                    @can('view_all_claims')
                    <li class="nav-item">
                        <a class="nav-link {{ str_contains($currentRoute, 'admin.claims.index') && !str_contains($currentRoute, 'admin.claims.ticket') && !str_contains($currentRoute, 'admin.claims.other') && !str_contains($currentRoute, 'admin.claims.bulk') ? 'active' : '' }}"
                           href="{{ route('admin.claims.index') }}">
                            <i class="bi bi-folder2-open me-2"></i> Overview
                        </a>
                    </li>
                    @endcan
                    @can('view_all_claims')
                    <li class="nav-item">
                        <a class="nav-link {{ str_contains($currentRoute, 'admin.claims.ticket-claims') || str_contains($currentRoute, 'admin.claims.create-ticket-claim') ? 'active' : '' }}"
                           href="{{ route('admin.claims.ticket-claims') }}">
                            <i class="bi bi-ticket-detailed me-2"></i> Ticket Claims
                        </a>
                    </li>
                    @endcan
                    @can('view_all_claims')
                    <li class="nav-item">
                        <a class="nav-link {{ str_contains($currentRoute, 'admin.claims.other-claims') || str_contains($currentRoute, 'admin.claims.create-other-claim') ? 'active' : '' }}"
                           href="{{ route('admin.claims.other-claims') }}">
                            <i class="bi bi-file-earmark-text me-2"></i> Other Claims
                        </a>
                    </li>
                    @endcan
                    @can('bulk_pay_claims')
                    <li class="nav-item">
                        <a class="nav-link {{ str_contains($currentRoute, 'admin.claims.bulk-payment') ? 'active' : '' }}"
                           href="{{ route('admin.claims.bulk-payment') }}">
                            <i class="bi bi-cash-stack me-2"></i> Bulk Payment
                        </a>
                    </li>
                    @endcan
                </ul>
            </div>
            @endcanany

            @php
                $isInventoryRoute = str_contains($currentRoute ?? '', 'admin.inventory');
            @endphp
            @canany(['view_inventory', 'create_stock_in', 'create_stock_out'])
            <div class="nav-section mb-3">
                <small class="text-muted text-uppercase fw-bold px-3">Inventory</small>
                <ul class="nav flex-column mt-2">
                    <li class="nav-item">
                        <a class="nav-link {{ $isInventoryRoute && !str_contains($currentRoute, 'stock-') && !str_contains($currentRoute, 'movements') && !str_contains($currentRoute, 'transfer') ? 'active' : '' }}"
                           href="{{ route('admin.inventory.index') }}">
                            <i class="bi bi-box-seam me-2"></i> Items
                        </a>
                    </li>
                    @can('create_stock_in')
                    <li class="nav-item">
                        <a class="nav-link {{ str_contains($currentRoute, 'admin.inventory.stock-in') ? 'active' : '' }}"
                           href="{{ route('admin.inventory.stock-in') }}">
                            <i class="bi bi-box-arrow-in-down me-2"></i> Stock In
                        </a>
                    </li>
                    @endcan
                    @can('create_stock_out')
                    <li class="nav-item">
                        <a class="nav-link {{ str_contains($currentRoute, 'admin.inventory.stock-out') ? 'active' : '' }}"
                           href="{{ route('admin.inventory.stock-out') }}">
                            <i class="bi bi-box-arrow-right me-2"></i> Stock Out
                        </a>
                    </li>
                    @endcan
                    @can('create_stock_return')
                    <li class="nav-item">
                        <a class="nav-link {{ str_contains($currentRoute, 'admin.inventory.stock-return') ? 'active' : '' }}"
                           href="{{ route('admin.inventory.stock-return') }}">
                            <i class="bi bi-box-arrow-up me-2"></i> Stock Return
                        </a>
                    </li>
                    @endcan
                    @can('view_stock_movements')
                    <li class="nav-item">
                        <a class="nav-link {{ str_contains($currentRoute, 'admin.inventory.movements') ? 'active' : '' }}"
                           href="{{ route('admin.inventory.movements') }}">
                            <i class="bi bi-arrow-left-right me-2"></i> Movements
                        </a>
                    </li>
                    @endcan
                    @can('create_stock_transfer')
                    <li class="nav-item">
                        <a class="nav-link {{ str_contains($currentRoute, 'admin.inventory.transfer') ? 'active' : '' }}"
                           href="{{ route('admin.inventory.transfer') }}">
                            <i class="bi bi-shuffle me-2"></i> Transfers
                        </a>
                    </li>
                    @endcan
                </ul>
            </div>
            @endcanany

            <!-- Profile Section -->
            <div class="nav-section mb-3">
                <small class="text-muted text-uppercase fw-bold px-3">My Profile</small>
                <ul class="nav flex-column mt-2">
                    <li class="nav-item">
                        <a class="nav-link {{ str_contains($currentRoute, 'admin.profile.index') ? 'active' : '' }}"
                           href="{{ route('admin.profile.index') }}">
                            <i class="bi bi-person-circle me-2"></i> View Profile
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ str_contains($currentRoute, 'admin.profile.edit') ? 'active' : '' }}"
                           href="{{ route('admin.profile.edit') }}">
                            <i class="bi bi-person-fill-gear me-2"></i> Edit Profile
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ str_contains($currentRoute, 'admin.profile.password') ? 'active' : '' }}"
                           href="{{ route('admin.profile.password') }}">
                            <i class="bi bi-key me-2"></i> Change Password
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ str_contains($currentRoute, 'admin.profile.avatar') ? 'active' : '' }}"
                           href="{{ route('admin.profile.avatar') }}">
                            <i class="bi bi-camera me-2"></i> Update Avatar
                        </a>
                    </li>
                </ul>
            </div>

        @elseif($role === 'supervisor')
            {{-- ============================================= --}}
            {{-- SUPERVISOR MENU --}}
            {{-- ============================================= --}}

            <!-- Dashboard -->
            <div class="nav-section mb-3">
                <small class="text-muted text-uppercase fw-bold px-3">Main</small>
                <ul class="nav flex-column mt-2">
                    <li class="nav-item">
                        <a class="nav-link {{ str_contains($currentRoute, 'supervisor.dashboard') ? 'active' : '' }}"
                           href="{{ route('supervisor.dashboard') }}">
                            <i class="bi bi-speedometer2 me-2"></i> Dashboard
                        </a>
                    </li>
                </ul>
            </div>

            <!-- Team Management Section -->
            @php
                $isTeamRoute = str_contains($currentRoute ?? '', 'supervisor.teams');
                $isInternalSupervisor = ($user->supervisor_type === 'internal');
                $isExternalSupervisor = ($user->supervisor_type === 'external');
            @endphp

            @if($isInternalSupervisor)
            <div class="nav-section mb-3">
                <small class="text-muted text-uppercase fw-bold px-3">Team Management</small>
                <ul class="nav flex-column mt-2">
                    <li class="nav-item">
                        <a class="nav-link {{ $isTeamRoute ? 'active' : '' }}"
                           href="{{ route('supervisor.teams.index') }}">
                            <i class="bi bi-people-fill me-2"></i> My Team
                            @php
                                $teamCount = $user->technicians()->count();
                            @endphp
                            @if($teamCount > 0)
                                <span class="badge bg-primary ms-auto">{{ $teamCount }}</span>
                            @endif
                        </a>
                    </li>
                </ul>
            </div>
            @elseif($isExternalSupervisor)
            <div class="nav-section mb-3">
                <small class="text-muted text-uppercase fw-bold px-3">My Profile</small>
                <ul class="nav flex-column mt-2">
                    <li class="nav-item">
                        <a class="nav-link {{ $isTeamRoute ? 'active' : '' }}"
                           href="{{ route('supervisor.teams.index') }}">
                            <i class="bi bi-person-badge me-2"></i> My Overview
                            <span class="badge bg-warning text-dark ms-auto">External</span>
                        </a>
                    </li>
                </ul>
            </div>
            @endif

            @php
                $isTicketRoute = str_contains($currentRoute ?? '', 'supervisor.tickets');
            @endphp
            @can('view_tickets')
            <div class="nav-section mb-3">
                <small class="text-muted text-uppercase fw-bold px-3">Ticket Management</small>
                <ul class="nav flex-column mt-2">
                    <li class="nav-item">
                        <a class="nav-link {{ $isTicketRoute ? 'active' : '' }}"
                        href="{{ route('supervisor.tickets.index') }}">
                            <i class="bi bi-ticket-detailed me-2"></i> Tickets
                        </a>
                    </li>
                </ul>
            </div>
            @endcan

            <!-- Master Data Section (Cleaned: Removed Partners, Clients, Sites, Depots, Rate Cards) -->
            @php
                $isVendorRoute = str_contains($currentRoute ?? '', 'supervisor.vendors');
            @endphp
            @can('view_vendors')
            <div class="nav-section mb-3">
                <small class="text-muted text-uppercase fw-bold px-3">Master Data</small>
                <ul class="nav flex-column mt-2">
                    <li class="nav-item">
                        <a class="nav-link {{ $isVendorRoute ? 'active' : '' }}"
                            href="{{ route('supervisor.vendors.index') }}">
                            <i class="bi bi-truck me-2"></i> Vendors
                        </a>
                    </li>
                </ul>
            </div>
            @endcan

            <!-- Claims Section -->
            @canany(['view_claims', 'view_team_claims', 'create_claims'])
            <div class="nav-section mb-3">
                <small class="text-muted text-uppercase fw-bold px-3">Claims</small>
                <ul class="nav flex-column mt-2">
                    <li class="nav-item">
                        <a class="nav-link {{ $currentRoute === 'supervisor.claims.index' ? 'active' : '' }}"
                           href="{{ route('supervisor.claims.index') }}">
                            <i class="bi bi-folder2-open me-2"></i> Claims Overview
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ str_contains($currentRoute, 'supervisor.claims.ticket-claims') ? 'active' : '' }}"
                           href="{{ route('supervisor.claims.ticket-claims') }}">
                            <i class="bi bi-ticket-detailed me-2"></i> Ticket Claims
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ str_contains($currentRoute, 'supervisor.claims.other-claims') ? 'active' : '' }}"
                           href="{{ route('supervisor.claims.other-claims') }}">
                            <i class="bi bi-file-earmark-text me-2"></i> Other Claims
                        </a>
                    </li>
                    @if(auth()->user()->supervisor_type !== 'internal')
                    <li class="nav-item">
                        <a class="nav-link {{ str_contains($currentRoute, 'supervisor.claims.create') ? 'active' : '' }}"
                           href="{{ route('supervisor.claims.create') }}">
                            <i class="bi bi-plus-circle me-2"></i> Submit Other Claim
                        </a>
                    </li>
                    @endif
                </ul>
            </div>
            @endcanany

            @php
                $isInventoryRoute = str_contains($currentRoute ?? '', 'supervisor.inventory');
            @endphp
            @canany(['view_inventory', 'create_stock_in', 'create_stock_out'])
            <div class="nav-section mb-3">
                <small class="text-muted text-uppercase fw-bold px-3">Inventory</small>
                <ul class="nav flex-column mt-2">
                    <li class="nav-item">
                        <a class="nav-link {{ $isInventoryRoute && !str_contains($currentRoute, 'stock-') && !str_contains($currentRoute, 'movements') && !str_contains($currentRoute, 'transfer') ? 'active' : '' }}"
                           href="{{ route('supervisor.inventory.index') }}">
                            <i class="bi bi-box-seam me-2"></i> Items
                        </a>
                    </li>
                    @can('create_stock_in')
                    <li class="nav-item">
                        <a class="nav-link {{ str_contains($currentRoute, 'supervisor.inventory.stock-in') ? 'active' : '' }}"
                           href="{{ route('supervisor.inventory.stock-in') }}">
                            <i class="bi bi-box-arrow-in-down me-2"></i> Stock In
                        </a>
                    </li>
                    @endcan
                    @can('create_stock_out')
                    <li class="nav-item">
                        <a class="nav-link {{ str_contains($currentRoute, 'supervisor.inventory.stock-out') ? 'active' : '' }}"
                           href="{{ route('supervisor.inventory.stock-out') }}">
                            <i class="bi bi-box-arrow-right me-2"></i> Stock Out
                        </a>
                    </li>
                    @endcan
                    @can('view_stock_movements')
                    <li class="nav-item">
                        <a class="nav-link {{ str_contains($currentRoute, 'supervisor.inventory.movements') ? 'active' : '' }}"
                           href="{{ route('supervisor.inventory.movements') }}">
                            <i class="bi bi-arrow-left-right me-2"></i> Movements
                        </a>
                    </li>
                    @endcan
                    @can('create_stock_transfer')
                    <li class="nav-item">
                        <a class="nav-link {{ str_contains($currentRoute, 'supervisor.inventory.transfer') ? 'active' : '' }}"
                           href="{{ route('supervisor.inventory.transfer') }}">
                            <i class="bi bi-shuffle me-2"></i> Transfers
                        </a>
                    </li>
                    @endcan
                </ul>
            </div>
            @endcanany

            <!-- Profile Section -->
            <div class="nav-section mb-3">
                <small class="text-muted text-uppercase fw-bold px-3">My Profile</small>
                <ul class="nav flex-column mt-2">
                    <li class="nav-item">
                        <a class="nav-link {{ str_contains($currentRoute, 'supervisor.profile.index') ? 'active' : '' }}"
                           href="{{ route('supervisor.profile.index') }}">
                            <i class="bi bi-person-circle me-2"></i> View Profile
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ str_contains($currentRoute, 'supervisor.profile.edit') ? 'active' : '' }}"
                           href="{{ route('supervisor.profile.edit') }}">
                            <i class="bi bi-person-fill-gear me-2"></i> Edit Profile
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ str_contains($currentRoute, 'supervisor.profile.password') ? 'active' : '' }}"
                           href="{{ route('supervisor.profile.password') }}">
                            <i class="bi bi-key me-2"></i> Change Password
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ str_contains($currentRoute, 'supervisor.profile.avatar') ? 'active' : '' }}"
                           href="{{ route('supervisor.profile.avatar') }}">
                            <i class="bi bi-camera me-2"></i> Update Avatar
                        </a>
                    </li>
                </ul>
            </div>

        @elseif($role === 'technician')
            {{-- ============================================= --}}
            {{-- TECHNICIAN MENU --}}
            {{-- ============================================= --}}

            <!-- Dashboard -->
            <div class="nav-section mb-3">
                <small class="text-muted text-uppercase fw-bold px-3">Main</small>
                <ul class="nav flex-column mt-2">
                    <li class="nav-item">
                        <a class="nav-link {{ str_contains($currentRoute, 'technician.dashboard') ? 'active' : '' }}"
                           href="{{ route('technician.dashboard') }}">
                            <i class="bi bi-speedometer2 me-2"></i> Dashboard
                        </a>
                    </li>
                </ul>
            </div>

            @php
                $isTicketRoute = str_contains($currentRoute ?? '', 'technician.tickets');
            @endphp
            @can('view_tickets')
            <div class="nav-section mb-3">
                <small class="text-muted text-uppercase fw-bold px-3">Ticket Management</small>
                <ul class="nav flex-column mt-2">
                    <li class="nav-item">
                        <a class="nav-link {{ $isTicketRoute ? 'active' : '' }}"
                        href="{{ route('technician.tickets.index') }}">
                            <i class="bi bi-ticket-detailed me-2"></i> My Tickets
                        </a>
                    </li>
                </ul>
            </div>
            @endcan

            <!-- Claims & Payouts Section -->
            <div class="nav-section mb-3">
                <small class="text-muted text-uppercase fw-bold px-3">Claims & Payouts</small>
                <ul class="nav flex-column mt-2">
                    <li class="nav-item">
                        <a class="nav-link {{ $currentRoute === 'technician.claims.index' ? 'active' : '' }}"
                           href="{{ route('technician.claims.index') }}">
                            <i class="bi bi-folder2-open me-2"></i> Claims Overview
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ str_contains($currentRoute, 'technician.claims.ticket-claims') ? 'active' : '' }}"
                           href="{{ route('technician.claims.ticket-claims') }}">
                            <i class="bi bi-ticket-detailed me-2"></i> Ticket Claims
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ str_contains($currentRoute, 'technician.claims.other-claims') ? 'active' : '' }}"
                           href="{{ route('technician.claims.other-claims') }}">
                            <i class="bi bi-file-earmark-text me-2"></i> Other Claims
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ str_contains($currentRoute, 'technician.claims.create') ? 'active' : '' }}"
                           href="{{ route('technician.claims.create') }}">
                            <i class="bi bi-plus-circle me-2"></i> Submit Other Claim
                        </a>
                    </li>
                </ul>
            </div>

            @php
                $isInventoryRoute = str_contains($currentRoute ?? '', 'technician.inventory');
            @endphp
            @canany(['view_own_inventory', 'view_stock_movements'])
            <div class="nav-section mb-3">
                <small class="text-muted text-uppercase fw-bold px-3">My Inventory</small>
                <ul class="nav flex-column mt-2">
                    <li class="nav-item">
                        <a class="nav-link {{ $isInventoryRoute ? 'active' : '' }}"
                           href="{{ route('technician.inventory.index') }}">
                            <i class="bi bi-box-seam me-2"></i> My Stock
                        </a>
                    </li>
                </ul>
            </div>
            @endcanany

            <!-- Profile Section -->
            <div class="nav-section mb-3">
                <small class="text-muted text-uppercase fw-bold px-3">My Profile</small>
                <ul class="nav flex-column mt-2">
                    <li class="nav-item">
                        <a class="nav-link {{ str_contains($currentRoute, 'technician.profile.index') ? 'active' : '' }}"
                           href="{{ route('technician.profile.index') }}">
                            <i class="bi bi-person-circle me-2"></i> View Profile
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ str_contains($currentRoute, 'technician.profile.edit') ? 'active' : '' }}"
                           href="{{ route('technician.profile.edit') }}">
                            <i class="bi bi-person-fill-gear me-2"></i> Edit Profile
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ str_contains($currentRoute, 'technician.profile.password') ? 'active' : '' }}"
                           href="{{ route('technician.profile.password') }}">
                            <i class="bi bi-key me-2"></i> Change Password
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ str_contains($currentRoute, 'technician.profile.avatar') ? 'active' : '' }}"
                           href="{{ route('technician.profile.avatar') }}">
                            <i class="bi bi-camera me-2"></i> Update Avatar
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ str_contains($currentRoute, 'technician.profile.bank-details') ? 'active' : '' }}"
                           href="{{ route('technician.profile.bank-details') }}">
                            <i class="bi bi-bank me-2"></i> Bank Details
                        </a>
                    </li>
                </ul>
            </div>

        @endif
    </nav>
</div>

<style>
    .sidebar-nav .nav-section {
        border-bottom: 1px solid #e2e8f0;
        padding-bottom: 15px;
    }

    .sidebar-nav .nav-section:last-child {
        border-bottom: none;
    }

    .sidebar-nav .nav-link {
        color: #4a5568;
        padding: 10px 15px;
        border-radius: 6px;
        margin-bottom: 3px;
        transition: all 0.2s;
        font-size: 0.9rem;
        display: flex;
        align-items: center;
    }

    .sidebar-nav .nav-link:hover {
        background-color: #f7fafc;
        color: #2d3748;
    }

    .sidebar-nav .nav-link.active {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
    }

    .sidebar-nav .nav-link.active:hover {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
    }

    .sidebar-nav .nav-link i {
        font-size: 1.1rem;
        width: 20px;
    }

    .sidebar-nav .badge {
        font-size: 0.7rem;
        padding: 0.25em 0.5em;
    }

    .user-info .avatar {
        flex-shrink: 0;
    }

    /* Scrollable sidebar */
    .sidebar-content {
        height: calc(100vh - 60px);
        overflow-y: auto;
    }

    .sidebar-content::-webkit-scrollbar {
        width: 5px;
    }

    .sidebar-content::-webkit-scrollbar-track {
        background: #f1f1f1;
    }

    .sidebar-content::-webkit-scrollbar-thumb {
        background: #c1c1c1;
        border-radius: 3px;
    }

    .sidebar-content::-webkit-scrollbar-thumb:hover {
        background: #a1a1a1;
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var sidebar = document.querySelector('.sidebar-content');
    if (!sidebar) return;

    // Restore saved scroll position
    var saved = sessionStorage.getItem('sidebar_scroll');
    if (saved !== null) {
        sidebar.scrollTop = parseInt(saved, 10);
    }

    // Save scroll position on scroll (debounced)
    var timer = null;
    sidebar.addEventListener('scroll', function() {
        if (timer) clearTimeout(timer);
        timer = setTimeout(function() {
            sessionStorage.setItem('sidebar_scroll', sidebar.scrollTop);
        }, 100);
    });

    // Also save before navigating away
    window.addEventListener('beforeunload', function() {
        sessionStorage.setItem('sidebar_scroll', sidebar.scrollTop);
    });
});
</script>
