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

            <!-- Master Data Section (NEW - Includes Partners) -->
            @php
                $isPartnerRoute = str_contains($currentRoute ?? '', 'admin.partners');
                $isClientRoute = str_contains($currentRoute ?? '', 'admin.clients');
                $isVendorRoute = str_contains($currentRoute ?? '', 'admin.vendors');
                $isSiteRoute = str_contains($currentRoute ?? '', 'admin.sites');
                $isDepotRoute = str_contains($currentRoute ?? '', 'admin.depots');
                $ischargeCatelogRoute = str_contains($currentRoute ?? '', 'admin.charge-catalog');
                $isRateCardRoute = str_contains($currentRoute ?? '', 'admin.rate-cards');
                $isTerminalRoute = str_contains($currentRoute ?? '', 'admin.terminal-models');
                $isCategoryRoute = str_contains($currentRoute ?? '', 'admin.terminal-categories');
                $isJobTypeRoute = str_contains($currentRoute ?? '', 'admin.job-types');
                $isVendorTypeRoute = str_contains($currentRoute ?? '', 'admin.vendor-types');
                $isJobCategoryRoute = str_contains($currentRoute ?? '', 'admin.job-categories');
            @endphp
            <div class="nav-section mb-3">
                <small class="text-muted text-uppercase fw-bold px-3">Master Data</small>
                <ul class="nav flex-column mt-2">
                    {{-- <li class="nav-item">
                        <a class="nav-link {{ $isPartnerRoute ? 'active' : '' }}"
                           href="{{ route('admin.partners.index') }}">
                            <i class="bi bi-building-fill me-2"></i> Partners
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ $isClientRoute ? 'active' : '' }}"
                           href="{{ route('admin.clients.index') }}">
                            <i class="bi bi-shop me-2"></i> Clients
                        </a>
                    </li> --}}
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
                    {{-- <li class="nav-item">
                        <a class="nav-link {{ $isSiteRoute ? 'active' : '' }}"
                           href="{{ route('admin.sites.index') }}">
                            <i class="bi bi-geo-alt me-2"></i> Sites
                        </a>
                    </li> --}}
                    {{-- @can('view_depots')
                    <li class="nav-item">
                        <a class="nav-link {{ $isDepotRoute ? 'active' : '' }}"
                        href="{{ route('admin.depots.index') }}">
                            <i class="bi bi-building me-2"></i> Depots
                        </a>
                    </li>
                    @endcan --}}
                    <li class="nav-item">
                        <a href="{{ route('admin.charge-catalog.index') }}"
                        class="nav-link {{ $ischargeCatelogRoute ? 'active' : '' }}">
                            <i class="bi bi-tag"></i> Job Catalog
                        </a>
                    </li>
                    {{-- @can('view_rate_cards')
                    <li class="nav-item">
                        <a href="{{ route('admin.rate-cards.index') }}"
                        class="nav-link {{ $isRateCardRoute ? 'active' : '' }}">
                            <i class="bi bi-credit-card-2-front"></i> Rate Cards
                        </a>
                    </li>
                    @endcan --}}
                    @can('view_models')
                    <li class="nav-item">
                        <a class="nav-link {{ $isTerminalRoute ? 'active' : '' }}"
                        href="{{ route('admin.terminal-models.index') }}">
                            <i class="bi bi-box-seam"></i>
                            <span>Terminal Models</span>
                            @if($unreadNotifications ?? 0)
                                <span class="badge bg-danger ms-auto">{{ $unreadNotifications }}</span>
                            @endif
                        </a>
                    </li>
                    @endcan
                    <li class="nav-item">
                        <a class="nav-link {{ $isCategoryRoute ? 'active' : '' }}"
                        href="{{ route('admin.terminal-categories.index') }}">
                            <i class="bi bi-grid-3x3-gap me-2"></i> Categories
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

            <!-- Job Management Section -->
            {{-- @php
                $isJobRoute = str_contains($currentRoute ?? '', 'admin.jobs');
            @endphp
            <div class="nav-section mb-3">
                <small class="text-muted text-uppercase fw-bold px-3">Job Management</small>
                <ul class="nav flex-column mt-2">
                    <li class="nav-item">
                        <a class="nav-link {{ $isJobRoute ? 'active' : '' }}" href="#">
                            <i class="bi bi-clipboard-check me-2"></i> All Jobs
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#">
                            <i class="bi bi-clock-history me-2"></i> Job History
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#">
                            <i class="bi bi-exclamation-triangle me-2"></i> SLA Tracking
                        </a>
                    </li>
                </ul>
            </div> --}}

            @php
                $isInventoryMgmtRoute = str_contains($currentRoute ?? '', 'admin.inventory-management');
            @endphp
            <!-- Inventory Section -->
            <div class="nav-section mb-3">
                <small class="text-muted text-uppercase fw-bold px-3">Inventory</small>
                <ul class="nav flex-column mt-2">

                    @can('view_inventory_management')
                    <li class="nav-item">
                        <a class="nav-link {{ $isInventoryMgmtRoute ?? false ? 'active' : '' }}"
                        href="{{ route('admin.inventory-management.index') }}">
                            <i class="bi bi-box-seam me-2"></i> Inventory Management
                        </a>
                    </li>
                    @endcan

                    {{-- Sub-links (shown when on inventory-management pages) --}}
                    @if($isInventoryMgmtRoute ?? false)
                        @can('create_stock_in')
                        <li class="nav-item">
                            <a class="nav-link ps-4 {{ str_contains($currentRoute ?? '', 'stock-in') ? 'active' : '' }}"
                            href="{{ route('admin.inventory-management.stock-in') }}">
                                <i class="bi bi-box-arrow-in-down me-2 text-success"></i> Stock In
                            </a>
                        </li>
                        @endcan
                        @can('create_stock_out')
                        <li class="nav-item">
                            <a class="nav-link ps-4 {{ str_contains($currentRoute ?? '', 'stock-out') ? 'active' : '' }}"
                            href="{{ route('admin.inventory-management.stock-out') }}">
                                <i class="bi bi-box-arrow-up me-2 text-danger"></i> Stock Out
                            </a>
                        </li>
                        @endcan
                        @can('create_replacements')
                        <li class="nav-item">
                            <a class="nav-link ps-4 {{ str_contains($currentRoute ?? '', 'replacement') ? 'active' : '' }}"
                            href="{{ route('admin.inventory-management.replacement') }}">
                                <i class="bi bi-arrow-left-right me-2 text-warning"></i> Replacement
                            </a>
                        </li>
                        @endcan
                        @can('view_accessory_usage')
                        <li class="nav-item">
                            <a class="nav-link ps-4 {{ str_contains($currentRoute ?? '', 'accessories') ? 'active' : '' }}"
                            href="{{ route('admin.inventory-management.accessories') }}">
                                <i class="bi bi-sim me-2 text-secondary"></i> Accessories Usage
                            </a>
                        </li>
                        @endcan
                    @endif
                </ul>
            </div>

            <!-- Quotations Section -->
            @php
                $isQuotationRoute = str_contains($currentRoute ?? '', 'admin.quotations');
            @endphp
            @can('view_quotations')
            <div class="nav-section mb-3">
                <small class="text-muted text-uppercase fw-bold px-3">Quotations</small>
                <ul class="nav flex-column mt-2">
                    <li class="nav-item">
                        <a class="nav-link {{ $isQuotationRoute && !str_contains($currentRoute, 'create') ? 'active' : '' }}"
                           href="{{ route('admin.quotations.index') }}">
                            <i class="bi bi-file-earmark-text me-2"></i> All Quotations
                        </a>
                    </li>
                    @can('create_quotations')
                    <li class="nav-item">
                        <a class="nav-link {{ str_contains($currentRoute ?? '', 'admin.quotations.create') ? 'active' : '' }}"
                           href="{{ route('admin.quotations.create') }}">
                            <i class="bi bi-plus-circle me-2"></i> Create Quotation
                        </a>
                    </li>
                    @endcan
                </ul>
            </div>
            @endcan


            <!-- Financial Section -->
            {{-- <div class="nav-section mb-3">
                <small class="text-muted text-uppercase fw-bold px-3">Financial</small>
                <ul class="nav flex-column mt-2">
                    <li class="nav-item">
                        <a class="nav-link" href="#">
                            <i class="bi bi-receipt me-2"></i> Invoices
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#">
                            <i class="bi bi-cash-coin me-2"></i> Payments
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#">
                            <i class="bi bi-pie-chart me-2"></i> Aging Analysis
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#">
                            <i class="bi bi-wallet2 me-2"></i> Payouts
                        </a>
                    </li>
                </ul>
            </div> --}}

            <!-- Reports Section -->
            {{-- <div class="nav-section mb-3">
                <small class="text-muted text-uppercase fw-bold px-3">Reports</small>
                <ul class="nav flex-column mt-2">
                    <li class="nav-item">
                        <a class="nav-link" href="#">
                            <i class="bi bi-file-earmark-bar-graph me-2"></i> All Reports
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#">
                            <i class="bi bi-activity me-2"></i> Activity Logs
                        </a>
                    </li>
                </ul>
            </div> --}}

            <!-- Old Stock Reports Section removed -->

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

            <!-- Master Data Section (View Only for Supervisor) -->
            @php
                $isPartnerRoute = str_contains($currentRoute ?? '', 'supervisor.partners');
                $isClientRoute = str_contains($currentRoute ?? '', 'supervisor.clients');
                $isSiteRoute = str_contains($currentRoute ?? '', 'supervisor.sites');
                $isDepotRoute = str_contains($currentRoute ?? '', 'supervisor.depots');
                $isChargeCatelogRoute = str_contains($currentRoute ?? '', 'supervisor.charge-catalog');
                $isTerminalRoute = str_contains($currentRoute ?? '', 'supervisor.terminal-models');
                $isRateCardRoute = str_contains($currentRoute ?? '', 'supervisor.rate-cards');
                $isCategoryRoute = str_contains($currentRoute ?? '', 'supervisor.terminal-categories');
                $isVendorRoute = str_contains($currentRoute ?? '', 'supervisor.vendors');
            @endphp
            @can('partners.view')
            <div class="nav-section mb-3">
                <small class="text-muted text-uppercase fw-bold px-3">Master Data</small>
                <ul class="nav flex-column mt-2">
                    {{-- <li class="nav-item">
                        <a class="nav-link {{ $isPartnerRoute ? 'active' : '' }}"
                           href="{{ route('supervisor.partners.index') }}">
                            <i class="bi bi-building-fill me-2"></i> Partners
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ $isClientRoute ? 'active' : '' }}"
                           href="#">
                            <i class="bi bi-shop me-2"></i> Clients
                        </a>
                    </li> --}}
                    @can('view_vendors')
                    <li class="nav-item">
                        <a class="nav-link {{ $isVendorRoute ? 'active' : '' }}"
                            href="{{ route('supervisor.vendors.index') }}">
                            <i class="bi bi-truck me-2"></i> Vendors
                        </a>
                    </li>
                    @endcan
                    {{-- <li class="nav-item">
                        <a class="nav-link {{ $isSiteRoute ? 'active' : '' }}"
                           href="{{ route('supervisor.sites.index') }}">
                            <i class="bi bi-geo-alt me-2"></i> Sites
                        </a>
                    </li> --}}
                    {{-- @can('view_rate_cards')
                    <li class="nav-item">
                        <a href="{{ route('supervisor.rate-cards.index') }}"
                        class="nav-link {{ $isRateCardRoute ? 'active' : '' }}">
                            <i class="bi bi-credit-card-2-front"></i> Rate Cards
                        </a>
                    </li>
                    @endcan --}}
                    {{-- @can('view_depots')
                    <li class="nav-item">
                        <a class="nav-link {{ $isDepotRoute ? 'active' : '' }}"
                        href="{{ route('supervisor.depots.index') }}">
                            <i class="bi bi-building me-2"></i> Depots
                        </a>
                    </li>
                    @endcan --}}
                    <li class="nav-item">
                        <a href="{{ route('supervisor.charge-catalog.index') }}"
                        class="nav-link {{ $isChargeCatelogRoute ? 'active' : '' }}">
                            <i class="bi bi-tag"></i> Job Catalog
                        </a>
                    </li>
                    @can('view_models')
                    <li class="nav-item">
                        <a class="nav-link {{ $isTerminalRoute ? 'active' : '' }}"
                        href="{{ route('supervisor.terminal-models.index') }}">
                            <i class="bi bi-box-seam"></i>
                            <span>Terminal Models</span>
                        </a>
                    </li>
                    @endcan
                    <li class="nav-item">
                        <a class="nav-link {{ $isCategoryRoute ? 'active' : '' }}"
                        href="{{ route('supervisor.terminal-categories.index') }}">
                            <i class="bi bi-grid-3x3-gap me-2"></i> Categories
                        </a>
                    </li>
                </ul>
            </div>
            @endcan

            @php
                $isInventoryMgmtRoute = str_contains($currentRoute ?? '', 'supervisor.inventory-management');
            @endphp
            <!-- Inventory Section -->
            <div class="nav-section mb-3">
                <small class="text-muted text-uppercase fw-bold px-3">Inventory</small>
                <ul class="nav flex-column mt-2">

                    @can('view_inventory_management')
                    <li class="nav-item">
                        <a class="nav-link {{ $isInventoryMgmtRoute ?? false ? 'active' : '' }}"
                        href="{{ route('supervisor.inventory-management.index') }}">
                            <i class="bi bi-box-seam me-2"></i> Inventory Management
                        </a>
                    </li>
                    @endcan

                    @if($isInventoryMgmtRoute ?? false)
                        @can('create_stock_out')
                        <li class="nav-item">
                            <a class="nav-link ps-4 {{ str_contains($currentRoute ?? '', 'stock-out') ? 'active' : '' }}"
                            href="{{ route('supervisor.inventory-management.stock-out') }}">
                                <i class="bi bi-box-arrow-up me-2 text-danger"></i> Stock Out
                            </a>
                        </li>
                        @endcan
                        @can('create_replacements')
                        <li class="nav-item">
                            <a class="nav-link ps-4 {{ str_contains($currentRoute ?? '', 'replacement') ? 'active' : '' }}"
                            href="{{ route('supervisor.inventory-management.replacement') }}">
                                <i class="bi bi-arrow-left-right me-2 text-warning"></i> Replacement
                            </a>
                        </li>
                        @endcan
                        @can('view_accessory_usage')
                        <li class="nav-item">
                            <a class="nav-link ps-4 {{ str_contains($currentRoute ?? '', 'accessories') ? 'active' : '' }}"
                            href="{{ route('supervisor.inventory-management.accessories') }}">
                                <i class="bi bi-sim me-2 text-secondary"></i> Accessories
                            </a>
                        </li>
                        @endcan
                    @endif
                </ul>
            </div>

            <!-- Job Management Section -->
            {{-- <div class="nav-section mb-3">
                <small class="text-muted text-uppercase fw-bold px-3">Job Management</small>
                <ul class="nav flex-column mt-2">
                    <li class="nav-item">
                        <a class="nav-link" href="#">
                            <i class="bi bi-clipboard-check me-2"></i> Team Jobs
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#">
                            <i class="bi bi-exclamation-triangle me-2"></i> SLA Tracking
                        </a>
                    </li>
                </ul>
            </div> --}}

            <!-- Approvals Section -->
            {{-- <div class="nav-section mb-3">
                <small class="text-muted text-uppercase fw-bold px-3">Approvals</small>
                <ul class="nav flex-column mt-2">
                    <li class="nav-item">
                        <a class="nav-link" href="#">
                            <i class="bi bi-file-earmark-check me-2"></i> Pending Claims
                            <span class="badge bg-warning ms-auto">3</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#">
                            <i class="bi bi-receipt me-2"></i> Job Approvals
                        </a>
                    </li>
                </ul>
            </div> --}}

            <!-- Reports Section -->
            {{-- <div class="nav-section mb-3">
                <small class="text-muted text-uppercase fw-bold px-3">Reports</small>
                <ul class="nav flex-column mt-2">
                    <li class="nav-item">
                        <a class="nav-link" href="#">
                            <i class="bi bi-file-earmark-bar-graph me-2"></i> Team Reports
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#">
                            <i class="bi bi-graph-up me-2"></i> Performance
                        </a>
                    </li>
                </ul>
            </div> --}}

            <!-- Old Supervisor Stock Reports Section removed -->

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
                    {{-- Only external supervisors can submit claims --}}
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

            <!-- Quotations Section -->
            @php
                $isQuotationRoute = str_contains($currentRoute ?? '', 'supervisor.quotations');
            @endphp
            @can('view_quotations')
            <div class="nav-section mb-3">
                <small class="text-muted text-uppercase fw-bold px-3">Quotations</small>
                <ul class="nav flex-column mt-2">
                    <li class="nav-item">
                        <a class="nav-link {{ $isQuotationRoute && !str_contains($currentRoute, 'create') ? 'active' : '' }}"
                           href="{{ route('supervisor.quotations.index') }}">
                            <i class="bi bi-file-earmark-text me-2"></i> Team Quotations
                        </a>
                    </li>
                    @can('create_quotations')
                    <li class="nav-item">
                        <a class="nav-link {{ str_contains($currentRoute ?? '', 'supervisor.quotations.create') ? 'active' : '' }}"
                           href="{{ route('supervisor.quotations.create') }}">
                            <i class="bi bi-plus-circle me-2"></i> Create Quotation
                        </a>
                    </li>
                    @endcan
                </ul>
            </div>
            @endcan

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

            @php
                $isPartnerRoute = str_contains($currentRoute ?? '', 'technician.partners');
                $isClientRoute = str_contains($currentRoute ?? '', 'technician.clients');
                $isSiteRoute = str_contains($currentRoute ?? '', 'technician.sites');
                $isDepotRoute = str_contains($currentRoute ?? '', 'technician.depots');
                $isChargeCatelogRoute = str_contains($currentRoute ?? '', 'technician.charge-catalog');
                $isTerminalRoute = str_contains($currentRoute ?? '', 'technician.terminal-models');
                $isCategoryRoute = str_contains($currentRoute ?? '', 'technician.terminal-categories');
                $isRateCardRoute = str_contains($currentRoute ?? '', 'technician.rate-cards');
            @endphp
            <div class="nav-section mb-3">
                <small class="text-muted text-uppercase fw-bold px-3">Reference Data</small>
                <ul class="nav flex-column mt-2">
                    {{-- @can('view_partners')
                    <li class="nav-item">
                        <a class="nav-link {{ $isPartnerRoute ? 'active' : '' }}"
                        href="{{ route('technician.partners.index') }}">
                            <i class="bi bi-building me-2"></i> Partners
                        </a>
                    </li>
                    @endcan
                    @can('view_clients')
                    <li class="nav-item">
                        <a class="nav-link {{ $isClientRoute ? 'active' : '' }}"
                        href="#">
                            <i class="bi bi-shop me-2"></i> Clients
                        </a>
                    </li>
                    @endcan --}}
                    {{-- @can('view_sites')
                    <li class="nav-item">
                        <a class="nav-link {{ $isSiteRoute ? 'active' : '' }}"
                        href="{{ route('technician.sites.index') }}">
                            <i class="bi bi-geo-alt me-2"></i> Sites
                        </a>
                    </li>
                    @endcan --}}
                    @can('view_models')
                    <li class="nav-item">
                        <a class="nav-link {{ $isTerminalRoute ? 'active' : '' }}"
                        href="{{ route('technician.terminal-models.index') }}">
                            <i class="bi bi-box-seam"></i>
                            <span>Terminal Models</span>
                        </a>
                    </li>
                    @endcan
                    {{-- @can('view_depots')
                    <li class="nav-item">
                        <a class="nav-link {{ $isDepotRoute ? 'active' : '' }}"
                        href="{{ route('technician.depots.index') }}">
                            <i class="bi bi-building me-2"></i> Depots
                        </a>
                    </li>
                    @endcan --}}
                    <li class="nav-item">
                        <a href="{{ route('technician.charge-catalog.index') }}"
                        class="nav-link {{ $isChargeCatelogRoute ? 'active' : '' }}">
                            <i class="bi bi-tag"></i> Job Catalog
                        </a>
                    </li>
                    {{-- @can('view_rate_cards')
                    <li class="nav-item">
                        <a href="{{ route('technician.rate-cards.index') }}"
                        class="nav-link {{ $isRateCardRoute ? 'active' : '' }}">
                            <i class="bi bi-credit-card-2-front"></i> My Commission Rates
                        </a>
                    </li>
                    @endcan --}}
                    @can('view_categories')
                    <li class="nav-item">
                        <a class="nav-link {{ $isCategoryRoute ? 'active' : '' }}"
                        href="{{ route('technician.terminal-categories.index') }}">
                            <i class="bi bi-grid-3x3-gap me-2"></i> Categories
                        </a>
                    </li>
                    @endcan
                </ul>
            </div>

            <!-- My Work Section -->
            {{-- <div class="nav-section mb-3">
                <small class="text-muted text-uppercase fw-bold px-3">My Work</small>
                <ul class="nav flex-column mt-2">
                    <li class="nav-item">
                        <a class="nav-link" href="#">
                            <i class="bi bi-clipboard-check me-2"></i> My Jobs
                            <span class="badge bg-primary ms-auto">5</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#">
                            <i class="bi bi-clock-history me-2"></i> Job History
                        </a>
                    </li>
                </ul>
            </div> --}}

            <!-- Old Technician Stock Reports Section removed -->

            <!-- Reference Data Section (Limited View for Technician) -->
            @php
                $isPartnerRoute = str_contains($currentRoute ?? '', 'technician.partners');
                $isClientRoute = str_contains($currentRoute ?? '', 'technician.clients');
                $isSiteRoute = str_contains($currentRoute ?? '', 'technician.sites');
            @endphp
            @can('partners.view')
            <div class="nav-section mb-3">
                <small class="text-muted text-uppercase fw-bold px-3">Reference Data</small>
                <ul class="nav flex-column mt-2">
                    <li class="nav-item">
                        <a class="nav-link {{ $isPartnerRoute ? 'active' : '' }}"
                           href="{{ route('technician.partners.index') }}">
                            <i class="bi bi-building me-2"></i> Partners
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ $isClientRoute ? 'active' : '' }}"
                           href="#">
                            <i class="bi bi-shop me-2"></i> Clients
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ $isSiteRoute ? 'active' : '' }}"
                           href="{{ route('technician.sites.index') }}">
                            <i class="bi bi-geo-alt me-2"></i> Sites
                        </a>
                    </li>
                </ul>
            </div>
            @endcan

            @php
                $isInventoryMgmtRoute = str_contains($currentRoute ?? '', 'technician.inventory-management');
            @endphp

            <!-- Inventory Section -->
            <div class="nav-section mb-3">
                <small class="text-muted text-uppercase fw-bold px-3">Inventory</small>
                <ul class="nav flex-column mt-2">

                    @can('view_inventory_management')
                    <li class="nav-item">
                        <a class="nav-link {{ $isInventoryMgmtRoute ?? false ? 'active' : '' }}"
                        href="{{ route('technician.inventory-management.index') }}">
                            <i class="bi bi-box-seam me-2"></i> Inventory Management
                        </a>
                    </li>
                    @endcan

                    @if($isInventoryMgmtRoute ?? false)
                        @can('create_replacements')
                        <li class="nav-item">
                            <a class="nav-link ps-4 {{ str_contains($currentRoute ?? '', 'replacement') ? 'active' : '' }}"
                            href="{{ route('technician.inventory-management.replacement') }}">
                                <i class="bi bi-arrow-left-right me-2 text-warning"></i> Replacement
                            </a>
                        </li>
                        @endcan
                        @can('view_accessory_usage')
                        <li class="nav-item">
                            <a class="nav-link ps-4 {{ str_contains($currentRoute ?? '', 'my-accessories') ? 'active' : '' }}"
                            href="{{ route('technician.inventory-management.my-accessories') }}">
                                <i class="bi bi-sim me-2 text-secondary"></i> My Accessories
                            </a>
                        </li>
                        @endcan
                    @endif
                </ul>
            </div>

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
                    {{-- <li class="nav-item">
                        <a class="nav-link" href="#">
                            <i class="bi bi-cash-coin me-2"></i> Commission
                        </a>
                    </li> --}}
                </ul>
            </div>

            <!-- Quotations Section -->
            @php
                $isQuotationRoute = str_contains($currentRoute ?? '', 'technician.quotations');
            @endphp
            @can('view_quotations')
            <div class="nav-section mb-3">
                <small class="text-muted text-uppercase fw-bold px-3">Quotations</small>
                <ul class="nav flex-column mt-2">
                    <li class="nav-item">
                        <a class="nav-link {{ $isQuotationRoute ? 'active' : '' }}"
                           href="{{ route('technician.quotations.index') }}">
                            <i class="bi bi-file-earmark-text me-2"></i> My Quotations
                        </a>
                    </li>
                </ul>
            </div>
            @endcan

            <!-- Claims & Payouts Section -->
            {{-- <div class="nav-section mb-3">
                <small class="text-muted text-uppercase fw-bold px-3">Claims & Payouts</small>
                <ul class="nav flex-column mt-2">
                    <li class="nav-item">
                        <a class="nav-link" href="#">
                            <i class="bi bi-file-earmark-text me-2"></i> My Claims
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#">
                            <i class="bi bi-cash-coin me-2"></i> Commission
                        </a>
                    </li>
                </ul>
            </div> --}}

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
