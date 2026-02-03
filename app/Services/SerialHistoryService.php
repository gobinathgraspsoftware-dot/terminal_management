<?php

namespace App\Services;

use App\Models\InventorySerial;
use App\Models\StockLedger;
use App\Models\User;
use App\Models\Depot;
use App\Models\Site;
use App\Models\Vendor;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use Carbon\Carbon;

class SerialHistoryService
{
    // =========================================================================
    // MOVEMENT LOGGING
    // =========================================================================

    /**
     * Log a serial movement to the stock_ledger.
     * This is the main entry point for all serial movements.
     */
    public function logMovement(array $data): StockLedger
    {
        return DB::transaction(function () use ($data) {
            $entry = StockLedger::create([
                'transaction_date'  => $data['transaction_date'] ?? now()->toDateString(),
                'transaction_no'    => $data['transaction_no'] ?? $this->generateTransactionNo($data['transaction_type']),
                'transaction_type'  => $data['transaction_type'],
                'reference_type'    => $data['reference_type'] ?? null,
                'reference_id'      => $data['reference_id'] ?? null,
                'serial_id'         => $data['serial_id'] ?? null,
                'serial_no'         => $data['serial_no'] ?? null,
                'model_id'          => $data['model_id'],
                'quantity'          => $data['quantity'] ?? 1,
                'from_location_type' => $data['from_location_type'] ?? null,
                'from_location_id'   => $data['from_location_id'] ?? null,
                'to_location_type'   => $data['to_location_type'] ?? null,
                'to_location_id'     => $data['to_location_id'] ?? null,
                'unit_cost'         => $data['unit_cost'] ?? null,
                'total_cost'        => $data['total_cost'] ?? null,
                'remarks'           => $data['remarks'] ?? null,
                'created_by'        => $data['created_by'] ?? auth()->id(),
            ]);

            Log::info("Serial movement logged: [{$entry->transaction_type}] TXN#{$entry->transaction_no}", [
                'ledger_id'  => $entry->id,
                'serial_id'  => $entry->serial_id,
                'serial_no'  => $entry->serial_no,
                'type'       => $entry->transaction_type,
                'from'       => "{$entry->from_location_type}#{$entry->from_location_id}",
                'to'         => "{$entry->to_location_type}#{$entry->to_location_id}",
                'user_id'    => $entry->created_by,
            ]);

            return $entry;
        });
    }

    /**
     * Log GRN receipt into inventory.
     */
    public function logGrnIn(array $data): StockLedger
    {
        return $this->logMovement(array_merge($data, [
            'transaction_type'  => StockLedger::TYPE_GRN_IN,
            'reference_type'    => 'grn',
            'quantity'          => abs($data['quantity'] ?? 1),
        ]));
    }

    /**
     * Log issue to technician.
     */
    public function logIssueToTech(array $data): StockLedger
    {
        return $this->logMovement(array_merge($data, [
            'transaction_type'  => StockLedger::TYPE_ISSUE_TO_TECH,
            'reference_type'    => $data['reference_type'] ?? 'stock_issue',
            'quantity'          => -abs($data['quantity'] ?? 1),
        ]));
    }

    /**
     * Log return from technician.
     */
    public function logReturnFromTech(array $data): StockLedger
    {
        return $this->logMovement(array_merge($data, [
            'transaction_type'  => StockLedger::TYPE_RETURN_FROM_TECH,
            'reference_type'    => $data['reference_type'] ?? 'stock_issue',
            'quantity'          => abs($data['quantity'] ?? 1),
        ]));
    }

    /**
     * Log transfer between depots.
     */
    public function logTransfer(array $data): StockLedger
    {
        return $this->logMovement(array_merge($data, [
            'transaction_type'  => StockLedger::TYPE_TRANSFER,
            'reference_type'    => 'stock_transfer',
            'quantity'          => $data['quantity'] ?? 1,
        ]));
    }

    /**
     * Log installation to site.
     */
    public function logInstall(array $data): StockLedger
    {
        return $this->logMovement(array_merge($data, [
            'transaction_type'  => StockLedger::TYPE_INSTALL,
            'reference_type'    => $data['reference_type'] ?? 'delivery_order',
            'quantity'          => -abs($data['quantity'] ?? 1),
        ]));
    }

    /**
     * Log replacement out (old device removed).
     */
    public function logReplacementOut(array $data): StockLedger
    {
        return $this->logMovement(array_merge($data, [
            'transaction_type'  => StockLedger::TYPE_REPLACEMENT_OUT,
            'reference_type'    => $data['reference_type'] ?? 'job',
            'quantity'          => abs($data['quantity'] ?? 1),
        ]));
    }

    /**
     * Log replacement in (new device installed).
     */
    public function logReplacementIn(array $data): StockLedger
    {
        return $this->logMovement(array_merge($data, [
            'transaction_type'  => StockLedger::TYPE_REPLACEMENT_IN,
            'reference_type'    => $data['reference_type'] ?? 'job',
            'quantity'          => -abs($data['quantity'] ?? 1),
        ]));
    }

    /**
     * Log wastage / scrap.
     */
    public function logWastage(array $data): StockLedger
    {
        return $this->logMovement(array_merge($data, [
            'transaction_type'  => StockLedger::TYPE_WASTAGE,
            'reference_type'    => $data['reference_type'] ?? 'adjustment',
            'quantity'          => -abs($data['quantity'] ?? 1),
        ]));
    }

    /**
     * Log return to vendor.
     */
    public function logReturnToVendor(array $data): StockLedger
    {
        return $this->logMovement(array_merge($data, [
            'transaction_type'  => StockLedger::TYPE_RETURN_TO_VENDOR,
            'reference_type'    => $data['reference_type'] ?? 'stock_issue',
            'quantity'          => -abs($data['quantity'] ?? 1),
        ]));
    }

    // =========================================================================
    // REVERSAL
    // =========================================================================

    /**
     * Reverse a stock ledger movement.
     * Creates a new reversal entry and marks the original as reversed.
     */
    public function reverseMovement(int $ledgerId, ?string $remarks = null): StockLedger
    {
        return DB::transaction(function () use ($ledgerId, $remarks) {
            $original = StockLedger::lockForUpdate()->findOrFail($ledgerId);

            // Validate
            if ($original->is_reversed) {
                throw new \Exception("Movement #{$original->transaction_no} has already been reversed.");
            }

            if ($original->reversal_of_id) {
                throw new \Exception("Cannot reverse a reversal entry. Reverse the original movement instead.");
            }

            if (!$original->is_reversible) {
                throw new \Exception("Movement type [{$original->transaction_type}] is not reversible.");
            }

            // Create reversal entry (swap from/to locations, negate quantity)
            $reversalEntry = StockLedger::create([
                'transaction_date'   => now()->toDateString(),
                'transaction_no'     => $this->generateTransactionNo('reversal'),
                'transaction_type'   => $original->transaction_type,
                'reference_type'     => $original->reference_type,
                'reference_id'       => $original->reference_id,
                'serial_id'          => $original->serial_id,
                'serial_no'          => $original->serial_no,
                'model_id'           => $original->model_id,
                'quantity'           => -$original->quantity, // Negate
                'from_location_type' => $original->to_location_type, // Swap
                'from_location_id'   => $original->to_location_id,
                'to_location_type'   => $original->from_location_type, // Swap
                'to_location_id'     => $original->from_location_id,
                'unit_cost'          => $original->unit_cost,
                'total_cost'         => $original->total_cost ? -$original->total_cost : null,
                'remarks'            => $remarks ?? "Reversal of {$original->transaction_no}",
                'created_by'         => auth()->id(),
                'reversal_of_id'     => $original->id,
            ]);

            // Mark original as reversed
            $original->update([
                'is_reversed'         => true,
                'reversed_by_id'      => $reversalEntry->id,
                'reversed_at'         => now(),
                'reversed_by_user_id' => auth()->id(),
            ]);

            Log::info("Movement reversed: TXN#{$original->transaction_no} → Reversal TXN#{$reversalEntry->transaction_no}", [
                'original_id'  => $original->id,
                'reversal_id'  => $reversalEntry->id,
                'serial_no'    => $original->serial_no,
                'user_id'      => auth()->id(),
            ]);

            return $reversalEntry;
        });
    }

    // =========================================================================
    // TIMELINE & HISTORY
    // =========================================================================

    /**
     * Get the complete movement timeline for a serial.
     * Returns movements ordered chronologically (oldest first) for timeline display.
     */
    public function getSerialTimeline(int $serialId): array
    {
        $serial = InventorySerial::with([
            'terminalModel.category',
            'grn',
            'purchaseOrder',
        ])->findOrFail($serialId);

        $movements = StockLedger::where(function ($q) use ($serial) {
                $q->where('serial_id', $serial->id)
                  ->orWhere('serial_no', $serial->serial_no);
            })
            ->with(['createdBy', 'model', 'reversedByEntry', 'reversalOfEntry', 'reversedByUser'])
            ->orderBy('transaction_date', 'asc')
            ->orderBy('created_at', 'asc')
            ->get();

        return [
            'serial'    => $serial,
            'movements' => $movements,
            'summary'   => $this->buildTimelineSummary($movements),
        ];
    }

    /**
     * Build a summary of the timeline.
     */
    protected function buildTimelineSummary($movements): array
    {
        $total   = $movements->count();
        $active  = $movements->where('is_reversed', false)->whereNull('reversal_of_id')->count();
        $reversed = $movements->where('is_reversed', true)->count();
        $reversals = $movements->whereNotNull('reversal_of_id')->count();

        // Count by type
        $byType = $movements->groupBy('transaction_type')->map->count()->toArray();

        return [
            'total_movements'     => $total,
            'active_movements'    => $active,
            'reversed_movements'  => $reversed,
            'reversal_entries'    => $reversals,
            'by_type'             => $byType,
            'first_movement_date' => $movements->first()?->transaction_date?->format('d/m/Y'),
            'last_movement_date'  => $movements->last()?->transaction_date?->format('d/m/Y'),
        ];
    }

    // =========================================================================
    // DATATABLE
    // =========================================================================

    /**
     * Get DataTables data for all serial movements.
     */
    public function getDataTable(Request $request, string $role = 'admin', ?int $userId = null)
    {
        $query = StockLedger::query()
            ->with(['serial', 'model', 'createdBy']);

        // Role-based scoping
        if ($role === 'supervisor' && $userId) {
            $teamIds = User::where('supervisor_id', $userId)->pluck('id')->toArray();
            $teamIds[] = $userId;

            $query->where(function ($q) use ($teamIds) {
                $q->where(function ($sub) use ($teamIds) {
                    $sub->where('from_location_type', 'technician')
                        ->whereIn('from_location_id', $teamIds);
                })->orWhere(function ($sub) use ($teamIds) {
                    $sub->where('to_location_type', 'technician')
                        ->whereIn('to_location_id', $teamIds);
                });
            });
        } elseif ($role === 'technician' && $userId) {
            $query->where(function ($q) use ($userId) {
                $q->where(function ($sub) use ($userId) {
                    $sub->where('from_location_type', 'technician')
                        ->where('from_location_id', $userId);
                })->orWhere(function ($sub) use ($userId) {
                    $sub->where('to_location_type', 'technician')
                        ->where('to_location_id', $userId);
                });
            });
        }

        // Apply filters
        if ($request->filled('transaction_type')) {
            $query->where('transaction_type', $request->transaction_type);
        }
        if ($request->filled('serial_no')) {
            $query->where('serial_no', 'LIKE', '%' . $request->serial_no . '%');
        }
        if ($request->filled('date_from')) {
            $query->where('transaction_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->where('transaction_date', '<=', $request->date_to);
        }
        if ($request->filled('show_reversed')) {
            if ($request->show_reversed === 'active_only') {
                $query->where('is_reversed', false)->whereNull('reversal_of_id');
            } elseif ($request->show_reversed === 'reversed_only') {
                $query->where('is_reversed', true);
            }
        }

        $readOnly = in_array($role, ['supervisor', 'technician']);

        return DataTables::of($query->orderBy('transaction_date', 'desc')->orderBy('created_at', 'desc'))
            ->addIndexColumn()
            ->addColumn('date_display', function ($entry) {
                return $entry->transaction_date?->format('d/m/Y');
            })
            ->addColumn('serial_info', function ($entry) {
                $html = '<strong>' . e($entry->serial_no ?? '-') . '</strong>';
                if ($entry->model) {
                    $html .= '<br><small class="text-muted">' . e($entry->model->model_name ?? '') . '</small>';
                }
                return $html;
            })
            ->addColumn('type_badge', function ($entry) {
                $html = $entry->type_badge;
                if ($entry->is_reversed) {
                    $html .= ' ' . $entry->reversal_status_badge;
                } elseif ($entry->reversal_of_id) {
                    $html .= ' ' . $entry->reversal_status_badge;
                }
                return $html;
            })
            ->addColumn('from_to', function ($entry) {
                $from = $entry->from_location_name;
                $to   = $entry->to_location_name;
                return e($from) . ' <i class="bi bi-arrow-right text-muted mx-1"></i> ' . e($to);
            })
            ->addColumn('reference_display', function ($entry) {
                $label = $entry->reference_label;
                $url   = $entry->reference_url;

                if ($url) {
                    return '<a href="' . e($url) . '" class="text-decoration-none">' . e($label) . '</a>';
                }
                return e($label);
            })
            ->addColumn('performed_by', function ($entry) {
                return $entry->createdBy?->name ?? '-';
            })
            ->addColumn('action', function ($entry) use ($readOnly, $role) {
                $btn = '<div class="btn-group btn-group-sm" role="group">';

                // View timeline button
                $btn .= '<a href="' . route("{$role}.serial-movement-history.show", $entry->serial_id ?? 0) . '"
                            class="btn btn-outline-info" title="View Timeline">
                            <i class="bi bi-clock-history"></i>
                         </a>';

                // Reverse button (admin only, if reversible)
                if (!$readOnly && $entry->is_reversible) {
                    $btn .= '<button type="button"
                                class="btn btn-outline-warning btn-reverse"
                                data-id="' . $entry->id . '"
                                data-txn="' . e($entry->transaction_no) . '"
                                title="Reverse Movement">
                                <i class="bi bi-arrow-counterclockwise"></i>
                             </button>';
                }

                $btn .= '</div>';
                return $btn;
            })
            ->rawColumns(['serial_info', 'type_badge', 'from_to', 'reference_display', 'action'])
            ->make(true);
    }

    // =========================================================================
    // STATISTICS
    // =========================================================================

    /**
     * Get movement statistics summary.
     */
    public function getStatistics(?string $dateFrom = null, ?string $dateTo = null): array
    {
        $query = StockLedger::query();

        if ($dateFrom) {
            $query->where('transaction_date', '>=', $dateFrom);
        }
        if ($dateTo) {
            $query->where('transaction_date', '<=', $dateTo);
        }

        $byType = (clone $query)
            ->select('transaction_type', DB::raw('COUNT(*) as count'))
            ->groupBy('transaction_type')
            ->pluck('count', 'transaction_type')
            ->toArray();

        $total = array_sum($byType);
        $reversed = (clone $query)->where('is_reversed', true)->count();
        $todayCount = StockLedger::whereDate('transaction_date', today())->count();

        return [
            'total'           => $total,
            'by_type'         => $byType,
            'reversed'        => $reversed,
            'active'          => $total - $reversed,
            'today'           => $todayCount,
        ];
    }

    /**
     * Get filter options for dropdowns.
     */
    public function getFilterOptions(): array
    {
        return [
            'movement_types' => StockLedger::getTypeOptions(),
        ];
    }

    // =========================================================================
    // HELPERS
    // =========================================================================

    /**
     * Generate a unique transaction number.
     */
    protected function generateTransactionNo(string $type): string
    {
        $prefix = match ($type) {
            StockLedger::TYPE_GRN_IN           => 'GRN',
            StockLedger::TYPE_ISSUE_TO_TECH    => 'ISS',
            StockLedger::TYPE_RETURN_FROM_TECH => 'RTN',
            StockLedger::TYPE_TRANSFER         => 'TRF',
            StockLedger::TYPE_INSTALL          => 'INS',
            StockLedger::TYPE_REPLACEMENT_OUT  => 'RPO',
            StockLedger::TYPE_REPLACEMENT_IN   => 'RPI',
            StockLedger::TYPE_WASTAGE          => 'WST',
            StockLedger::TYPE_RETURN_TO_VENDOR => 'RTV',
            StockLedger::TYPE_ADJUSTMENT       => 'ADJ',
            'reversal'                         => 'REV',
            default                            => 'MVT',
        };

        $date  = now()->format('Ymd');
        $count = StockLedger::where('transaction_no', 'LIKE', "{$prefix}{$date}%")->count() + 1;

        return "{$prefix}{$date}" . str_pad($count, 4, '0', STR_PAD_LEFT);
    }
}
