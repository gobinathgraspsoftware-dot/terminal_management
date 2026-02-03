<?php

namespace App\Services;

use App\Models\InventorySerial;
use App\Models\StockLedger;
use App\Models\SiteAsset;
use App\Models\AssetEvent;
use App\Models\Depot;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class InventorySerialService
{
    // =========================================================================
    // DATATABLE
    // =========================================================================

    /**
     * Get DataTables data for inventory serials.
     */
    public function getDataTable(Request $request, string $role = 'admin', ?int $userId = null)
    {
        $query = InventorySerial::query()
            ->with(['terminalModel.category', 'grn', 'createdBy']);

        // Role-based scoping
        if ($role === 'supervisor' && $userId) {
            $teamIds = User::where('supervisor_id', $userId)->pluck('id')->toArray();
            $teamIds[] = $userId;
            $query->where(function ($q) use ($teamIds) {
                $q->where(function ($sub) use ($teamIds) {
                    $sub->where('current_location_type', InventorySerial::LOCATION_TYPE_TECHNICIAN)
                        ->whereIn('current_location_id', $teamIds);
                });
            });
        } elseif ($role === 'technician' && $userId) {
            $query->withTechnician($userId);
        }

        // Apply filters from request
        if ($request->filled('status')) {
            $query->byStatus($request->status);
        }
        if ($request->filled('model_id')) {
            $query->byModel($request->model_id);
        }
        if ($request->filled('location_type')) {
            $query->where('current_location_type', $request->location_type);
        }
        if ($request->filled('depot_id')) {
            $query->atDepot($request->depot_id);
        }
        if ($request->filled('warranty_status')) {
            if ($request->warranty_status === 'active') {
                $query->underWarranty();
            } elseif ($request->warranty_status === 'expired') {
                $query->warrantyExpired();
            }
        }

        $readOnly = in_array($role, ['supervisor', 'technician']);

        return DataTables::of($query)
            ->addIndexColumn()
            ->addColumn('serial_info', function ($serial) {
                $html = '<strong>' . e($serial->serial_no) . '</strong>';
                if ($serial->terminalModel) {
                    $html .= '<br><small class="text-muted">' . e($serial->terminalModel->model_name ?? '') . '</small>';
                }
                return $html;
            })
            ->addColumn('model_name', function ($serial) {
                if ($serial->terminalModel) {
                    return e($serial->terminalModel->model_name ?? $serial->terminalModel->model_code ?? '-');
                }
                return '-';
            })
            ->addColumn('category_name', function ($serial) {
                return $serial->terminalModel?->category?->category_name ?? '-';
            })
            ->addColumn('status_badge', function ($serial) {
                return $serial->status_badge;
            })
            ->addColumn('location_info', function ($serial) {
                return $serial->location_type_badge . '<br><small>' . e($serial->location_name) . '</small>';
            })
            ->addColumn('warranty', function ($serial) {
                return $serial->warranty_status;
            })
            ->addColumn('grn_info', function ($serial) {
                if ($serial->grn) {
                    return e($serial->grn->grn_no) . '<br><small class="text-muted">' .
                           ($serial->grn_date ? $serial->grn_date->format('d/m/Y') : '-') . '</small>';
                }
                return '-';
            })
            ->addColumn('action', function ($serial) use ($readOnly, $role) {
                $prefix = $role;
                $btn = '<div class="btn-group btn-group-sm" role="group">';

                // View button
                $btn .= '<a href="' . route("{$prefix}.inventory-serials.show", $serial->id) . '"
                            class="btn btn-info" title="View Details">
                            <i class="bi bi-eye"></i>
                        </a>';

                if (!$readOnly) {
                    // Edit button (admin only)
                    $btn .= '<a href="' . route('admin.inventory-serials.edit', $serial->id) . '"
                                class="btn btn-primary" title="Edit">
                                <i class="bi bi-pencil"></i>
                            </a>';

                    // Delete button (only for non-tracked)
                    if (!in_array($serial->current_status, InventorySerial::TRACKED_STATUSES)) {
                        $btn .= '<button type="button"
                                    class="btn btn-danger btn-delete"
                                    data-id="' . $serial->id . '"
                                    data-serial="' . e($serial->serial_no) . '"
                                    title="Delete">
                                    <i class="bi bi-trash"></i>
                                </button>';
                    }
                }

                $btn .= '</div>';
                return $btn;
            })
            ->rawColumns(['serial_info', 'status_badge', 'location_info', 'warranty', 'grn_info', 'action'])
            ->make(true);
    }

    // =========================================================================
    // CRUD
    // =========================================================================

    /**
     * Create a new inventory serial.
     */
    public function create(array $data): InventorySerial
    {
        return DB::transaction(function () use ($data) {
            return InventorySerial::create($data);
        });
    }

    /**
     * Update an inventory serial.
     */
    public function update(InventorySerial $serial, array $data): bool
    {
        return DB::transaction(function () use ($serial, $data) {
            return $serial->update($data);
        });
    }

    /**
     * Delete an inventory serial (soft).
     */
    public function delete(InventorySerial $serial): bool
    {
        return DB::transaction(function () use ($serial) {
            return $serial->delete();
        });
    }

    // =========================================================================
    // SERIAL LOOKUP & SEARCH
    // =========================================================================

    /**
     * Quick search by serial number (autocomplete).
     */
    public function autocomplete(string $term, int $limit = 10)
    {
        return InventorySerial::where('serial_no', 'LIKE', "%{$term}%")
            ->with('terminalModel:id,model_name,model_code')
            ->select('id', 'serial_no', 'model_id', 'current_status', 'current_location_type', 'current_location_id')
            ->limit($limit)
            ->get()
            ->map(function ($serial) {
                return [
                    'id'        => $serial->id,
                    'serial_no' => $serial->serial_no,
                    'model'     => $serial->terminalModel?->model_name ?? '-',
                    'status'    => $serial->current_status,
                    'status_label' => InventorySerial::STATUS_OPTIONS[$serial->current_status] ?? $serial->current_status,
                    'location'  => $serial->location_name,
                    'available' => $serial->is_available,
                ];
            });
    }

    /**
     * Validate serial number(s).
     */
    public function validateSerial(string $serialNo): array
    {
        $serial = InventorySerial::where('serial_no', $serialNo)
            ->with('terminalModel')
            ->first();

        if (!$serial) {
            return [
                'exists'    => false,
                'available' => false,
                'message'   => 'Serial number not found.',
            ];
        }

        return [
            'exists'    => true,
            'available' => $serial->is_available,
            'serial'    => [
                'id'             => $serial->id,
                'serial_no'      => $serial->serial_no,
                'model'          => $serial->terminalModel?->model_name ?? '-',
                'status'         => $serial->current_status,
                'status_label'   => InventorySerial::STATUS_OPTIONS[$serial->current_status] ?? $serial->current_status,
                'location_type'  => $serial->current_location_type,
                'location_name'  => $serial->location_name,
            ],
            'message' => $serial->is_available ? 'Available' : 'Not available (Status: ' . ($serial->current_status) . ')',
        ];
    }

    /**
     * Batch serial lookup.
     */
    public function batchLookup(array $serialNumbers): array
    {
        $results = [];
        $serials = InventorySerial::whereIn('serial_no', $serialNumbers)
            ->with('terminalModel')
            ->get()
            ->keyBy('serial_no');

        foreach ($serialNumbers as $serialNo) {
            $serialNo = trim($serialNo);
            if (empty($serialNo)) continue;

            if (isset($serials[$serialNo])) {
                $serial = $serials[$serialNo];
                $results[] = [
                    'serial_no'    => $serialNo,
                    'found'        => true,
                    'available'    => $serial->is_available,
                    'status'       => $serial->current_status,
                    'status_label' => InventorySerial::STATUS_OPTIONS[$serial->current_status] ?? $serial->current_status,
                    'model'        => $serial->terminalModel?->model_name ?? '-',
                    'location'     => $serial->location_name,
                ];
            } else {
                $results[] = [
                    'serial_no' => $serialNo,
                    'found'     => false,
                    'available' => false,
                    'status'    => null,
                    'model'     => null,
                    'location'  => null,
                ];
            }
        }

        return $results;
    }

    // =========================================================================
    // HISTORY & DETAILS
    // =========================================================================

    /**
     * Get complete serial detail with all history.
     */
    public function getSerialDetail(int $serialId): array
    {
        $serial = InventorySerial::with([
            'terminalModel.category',
            'grn.vendor',
            'grn.receivingDepot',
            'purchaseOrder',
            'createdBy',
            'updatedBy',
        ])->findOrFail($serialId);

        return [
            'serial'               => $serial,
            'movement_history'     => $this->getMovementHistory($serial),
            'installation_history' => $this->getInstallationHistory($serial),
            'service_history'      => $this->getServiceHistory($serial),
            'audit_trail'          => $this->getAuditTrail($serial),
        ];
    }

    /**
     * Get movement history from stock ledger.
     */
    public function getMovementHistory(InventorySerial $serial)
    {
        return StockLedger::where('serial_id', $serial->id)
            ->orWhere('serial_no', $serial->serial_no)
            ->with(['model', 'createdBy'])
            ->orderBy('transaction_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get installation history.
     */
    public function getInstallationHistory(InventorySerial $serial)
    {
        return SiteAsset::where('serial_id', $serial->id)
            ->orWhere('serial_no', $serial->serial_no)
            ->with(['site', 'installedBy', 'deliveryOrder', 'installationJob'])
            ->orderBy('installed_date', 'desc')
            ->get();
    }

    /**
     * Get service/event history.
     */
    public function getServiceHistory(InventorySerial $serial)
    {
        return AssetEvent::whereHas('siteAsset', function ($q) use ($serial) {
            $q->where('serial_id', $serial->id)
              ->orWhere('serial_no', $serial->serial_no);
        })
        ->with(['siteAsset.site', 'jobOrder', 'performedBy'])
        ->orderBy('event_date', 'desc')
        ->get();
    }

    /**
     * Get audit trail for serial.
     */
    public function getAuditTrail(InventorySerial $serial)
    {
        return $serial->auditTrails()
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get();
    }

    // =========================================================================
    // STATISTICS
    // =========================================================================

    /**
     * Get serial statistics summary.
     */
    public function getStatistics(?int $depotId = null): array
    {
        $query = InventorySerial::query();

        if ($depotId) {
            $query->atDepot($depotId);
        }

        $statusCounts = (clone $query)->select('current_status', DB::raw('COUNT(*) as count'))
            ->groupBy('current_status')
            ->pluck('count', 'current_status')
            ->toArray();

        $total = array_sum($statusCounts);

        return [
            'total'              => $total,
            'in_stock'           => $statusCounts[InventorySerial::STATUS_IN_STOCK] ?? 0,
            'issued_to_tech'     => $statusCounts[InventorySerial::STATUS_ISSUED_TO_TECH] ?? 0,
            'installed'          => $statusCounts[InventorySerial::STATUS_INSTALLED] ?? 0,
            'under_service'      => $statusCounts[InventorySerial::STATUS_UNDER_SERVICE] ?? 0,
            'returned_to_vendor' => $statusCounts[InventorySerial::STATUS_RETURNED_TO_VENDOR] ?? 0,
            'wasted'             => $statusCounts[InventorySerial::STATUS_WASTED] ?? 0,
            'reserved'           => $statusCounts[InventorySerial::STATUS_RESERVED] ?? 0,
            'warranty_active'    => InventorySerial::underWarranty()->count(),
            'warranty_expiring'  => InventorySerial::where('warranty_end', '>=', now())
                                        ->where('warranty_end', '<=', now()->addDays(30))
                                        ->count(),
        ];
    }

    /**
     * Get filter dropdown options.
     */
    public function getFilterOptions(): array
    {
        return [
            'statuses'       => InventorySerial::getStatusOptions(),
            'location_types' => InventorySerial::getLocationTypeOptions(),
            'depots'         => Depot::active()->orderBy('depot_name')->get(['id', 'depot_code', 'depot_name']),
        ];
    }
}
