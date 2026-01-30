<?php

namespace App\Services;

use App\Models\Site;
use App\Models\Client;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\Facades\DataTables;

/**
 * SiteService
 *
 * Handles all business logic for Site operations
 */
class SiteService
{
    /**
     * Get DataTable data for sites
     */
    public function getDatatableData($request)
    {
        $query = Site::with(['client:id,client_name'])
            ->select('sites.*');

        // Apply role-based scoping
        $user = Auth::user();
        if ($user->hasRole('supervisor')) {
            // Supervisor sees sites in their coverage states
            $coverageStates = json_decode($user->coverage_states, true) ?? [];
            if (!empty($coverageStates)) {
                $query->whereIn('state', $coverageStates);
            }
        }

        return DataTables::of($query)
            ->addIndexColumn()
            ->addColumn('client_name', function ($site) {
                return $site->client ? $site->client->client_name : '-';
            })
            ->addColumn('full_address', function ($site) {
                return $site->full_address;
            })
            ->addColumn('status_badge', function ($site) {
                if ($site->status === Site::STATUS_ACTIVE) {
                    return '<span class="badge bg-success">Active</span>';
                }
                return '<span class="badge bg-secondary">Inactive</span>';
            })
            ->addColumn('coordinates', function ($site) {
                if ($site->latitude && $site->longitude) {
                    return sprintf(
                        '<a href="https://www.google.com/maps?q=%s,%s" target="_blank" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-geo-alt"></i> View Map
                        </a>',
                        $site->latitude,
                        $site->longitude
                    );
                }
                return '-';
            })
            ->addColumn('assets_count', function ($site) {
                $count = $site->siteAssets()->count();
                return $count > 0 ? '<span class="badge bg-info">' . $count . '</span>' : '0';
            })
            ->addColumn('actions', function ($site) use ($user) {
                $actions = '';
                
                // View button
                if ($user->hasRole('admin')) {
                    $actions .= sprintf(
                        '<a href="%s" class="btn btn-sm btn-info me-1" title="View">
                            <i class="bi bi-eye"></i>
                        </a>',
                        route('admin.sites.show', $site)
                    );
                    
                    // Edit button
                    if ($user->can('edit_sites')) {
                        $actions .= sprintf(
                            '<a href="%s" class="btn btn-sm btn-warning me-1" title="Edit">
                                <i class="bi bi-pencil"></i>
                            </a>',
                            route('admin.sites.edit', $site)
                        );
                    }
                    
                    // Delete button
                    if ($user->can('delete_sites')) {
                        $actions .= sprintf(
                            '<button class="btn btn-sm btn-danger delete-btn" data-id="%s" title="Delete">
                                <i class="bi bi-trash"></i>
                            </button>',
                            $site->id
                        );
                    }
                } elseif ($user->hasRole('supervisor')) {
                    $actions .= sprintf(
                        '<a href="%s" class="btn btn-sm btn-info" title="View">
                            <i class="bi bi-eye"></i>
                        </a>',
                        route('supervisor.sites.show', $site)
                    );
                } elseif ($user->hasRole('technician')) {
                    $actions .= sprintf(
                        '<a href="%s" class="btn btn-sm btn-info" title="View">
                            <i class="bi bi-eye"></i>
                        </a>',
                        route('technician.sites.show', $site)
                    );
                }
                
                return $actions;
            })
            ->filter(function ($query) use ($request) {
                // Client filter
                if ($request->filled('client_id')) {
                    $query->where('client_id', $request->client_id);
                }
                
                // State filter
                if ($request->filled('state')) {
                    $query->where('state', $request->state);
                }
                
                // Status filter
                if ($request->filled('status')) {
                    $query->where('status', $request->status);
                }
                
                // Search filter
                if ($request->filled('search.value')) {
                    $searchValue = $request->input('search.value');
                    $query->where(function ($q) use ($searchValue) {
                        $q->where('site_code', 'like', "%{$searchValue}%")
                          ->orWhere('site_name', 'like', "%{$searchValue}%")
                          ->orWhere('city', 'like', "%{$searchValue}%")
                          ->orWhere('state', 'like', "%{$searchValue}%")
                          ->orWhere('pic_name', 'like', "%{$searchValue}%")
                          ->orWhere('pic_phone', 'like', "%{$searchValue}%");
                    });
                }
            })
            ->rawColumns(['status_badge', 'coordinates', 'assets_count', 'actions'])
            ->make(true);
    }

    /**
     * Get statistics for dashboard
     */
    public function getStatistics()
    {
        $user = Auth::user();
        
        $query = Site::query();
        
        // Apply role-based scoping
        if ($user->hasRole('supervisor')) {
            $coverageStates = json_decode($user->coverage_states, true) ?? [];
            if (!empty($coverageStates)) {
                $query->whereIn('state', $coverageStates);
            }
        }
        
        return [
            'total' => (clone $query)->count(),
            'active' => (clone $query)->where('status', Site::STATUS_ACTIVE)->count(),
            'inactive' => (clone $query)->where('status', Site::STATUS_INACTIVE)->count(),
            'with_assets' => (clone $query)->whereHas('siteAssets')->count(),
        ];
    }

    /**
     * Create a new site
     */
    public function createSite(array $data): Site
    {
        DB::beginTransaction();
        try {
            $data['created_by'] = Auth::id();
            $site = Site::create($data);
            
            DB::commit();
            return $site;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Update existing site
     */
    public function updateSite(Site $site, array $data): Site
    {
        DB::beginTransaction();
        try {
            $data['updated_by'] = Auth::id();
            $site->update($data);
            
            DB::commit();
            return $site;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Delete site (soft delete)
     */
    public function deleteSite(Site $site): bool
    {
        DB::beginTransaction();
        try {
            $site->delete();
            
            DB::commit();
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Restore soft-deleted site
     */
    public function restoreSite($siteId): bool
    {
        DB::beginTransaction();
        try {
            $site = Site::withTrashed()->findOrFail($siteId);
            $site->restore();
            
            DB::commit();
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Get all clients for dropdown
     */
    public function getClientsForDropdown()
    {
        return Client::where('status', 'active')
            ->orderBy('client_name')
            ->get(['id', 'client_code', 'client_name']);
    }

    /**
     * Get distinct states for filter
     */
    public function getStatesForFilter()
    {
        $user = Auth::user();
        
        $query = Site::query();
        
        // Apply role-based scoping
        if ($user->hasRole('supervisor')) {
            $coverageStates = json_decode($user->coverage_states, true) ?? [];
            if (!empty($coverageStates)) {
                $query->whereIn('state', $coverageStates);
            }
        }
        
        return $query->distinct()
            ->whereNotNull('state')
            ->orderBy('state')
            ->pluck('state');
    }

    /**
     * Get site with all relationships
     */
    public function getSiteWithRelations($siteId)
    {
        return Site::with([
            'client',
            'contacts',
            'siteAssets.model',
            'siteAssets.serial',
            'jobOrders' => function ($query) {
                $query->latest()->limit(10);
            }
        ])->findOrFail($siteId);
    }

    /**
     * Get Malaysian states for dropdown
     */
    public function getMalaysianStates(): array
    {
        return [
            'Johor',
            'Kedah',
            'Kelantan',
            'Kuala Lumpur',
            'Labuan',
            'Malacca',
            'Negeri Sembilan',
            'Pahang',
            'Penang',
            'Perak',
            'Perlis',
            'Putrajaya',
            'Sabah',
            'Sarawak',
            'Selangor',
            'Terengganu',
        ];
    }
}
