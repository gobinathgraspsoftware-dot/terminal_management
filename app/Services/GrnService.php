<?php

namespace App\Services;

use App\Models\Grn;
use App\Models\GrnLine;
use App\Models\GrnSerial;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderLine;
use App\Models\InventorySerial;
use App\Models\StockBalance;
use App\Models\StockLedger;
use App\Models\NumberSeries;
use App\Mail\GrnPostedEmail;
use App\Services\GrnPdfService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Exception;

class GrnService
{
    // =========================================================================
    // QUERY METHODS
    // =========================================================================

    /**
     * Get Purchase Orders with outstanding items
     */
    public function getOutstandingPurchaseOrders()
    {
        return PurchaseOrder::whereIn('status', [
            PurchaseOrder::STATUS_APPROVED,
            PurchaseOrder::STATUS_SENT,
            PurchaseOrder::STATUS_OPEN,
            PurchaseOrder::STATUS_PARTIALLY_RECEIVED
        ])
        ->with(['vendor', 'receivingDepot'])
        ->orderBy('po_date', 'desc')
        ->get();
    }

    /**
     * Get Purchase Order details with outstanding lines
     */
    public function getPurchaseOrderDetails($poId)
    {
        $po = PurchaseOrder::with([
            'vendor',
            'receivingDepot',
            'lines' => function ($query) {
                $query->whereRaw('quantity_ordered > (quantity_received + quantity_cancelled)')
                      ->with('model.category');
            }
        ])->findOrFail($poId);

        return $po;
    }

    // =========================================================================
    // CREATE GRN
    // =========================================================================

    /**
     * Create new GRN
     */
    public function createGrn(array $data)
    {
        return DB::transaction(function () use ($data) {
            // Generate GRN number
            $grnNo = $this->generateGrnNumber();

            // Get PO details
            $po = PurchaseOrder::with('vendor')->findOrFail($data['purchase_order_id']);

            // Create GRN header
            $grn = Grn::create([
                'grn_no'             => $grnNo,
                'grn_date'           => $data['grn_date'],
                'purchase_order_id'  => $data['purchase_order_id'],
                'vendor_id'          => $po->vendor_id,
                'receiving_depot_id' => $data['receiving_depot_id'] ?? $po->receiving_depot_id,
                'delivery_note_no'   => $data['delivery_note_no'] ?? null,
                'remarks'            => $data['remarks'] ?? null,
                'status'             => Grn::STATUS_DRAFT,
                'total_items'        => 0,
                'created_by'         => Auth::id(),
            ]);

            // Create GRN lines
            $totalItems = 0;
            foreach ($data['lines'] as $index => $lineData) {
                if (empty($lineData['quantity_received']) || $lineData['quantity_received'] <= 0) {
                    continue;
                }

                $poLine = PurchaseOrderLine::findOrFail($lineData['po_line_id']);

                $grnLine = GrnLine::create([
                    'grn_id'            => $grn->id,
                    'line_no'           => $index + 1,
                    'po_line_id'        => $lineData['po_line_id'],
                    'model_id'          => $poLine->model_id,
                    'description'       => $poLine->description,
                    'quantity_received'  => $lineData['quantity_received'],
                    'unit'              => $poLine->unit,
                    'unit_cost'         => $poLine->unit_price,
                    'line_total'        => $lineData['quantity_received'] * $poLine->unit_price,
                    'remarks'           => $lineData['remarks'] ?? null,
                ]);

                $totalItems += $lineData['quantity_received'];

                // Handle serials if provided
                if (!empty($lineData['serials'])) {
                    $this->processSerials($grnLine, $lineData['serials'], $grn->receiving_depot_id);
                }
            }

            // Update total items
            $grn->update(['total_items' => $totalItems]);

            return $grn;
        });
    }

    // =========================================================================
    // UPDATE GRN
    // =========================================================================

    /**
     * Update GRN (only if status is draft)
     */
    public function updateGrn(Grn $grn, array $data)
    {
        if ($grn->status !== Grn::STATUS_DRAFT) {
            throw new Exception('Only draft GRNs can be updated.');
        }

        return DB::transaction(function () use ($grn, $data) {
            // Update header
            $grn->update([
                'grn_date'           => $data['grn_date'],
                'receiving_depot_id' => $data['receiving_depot_id'],
                'delivery_note_no'   => $data['delivery_note_no'] ?? null,
                'remarks'            => $data['remarks'] ?? null,
                'updated_by'         => Auth::id(),
            ]);

            // Delete existing lines and serials
            foreach ($grn->lines as $line) {
                $line->serials()->delete();
                $line->delete();
            }

            // Recreate lines
            $totalItems = 0;
            foreach ($data['lines'] as $index => $lineData) {
                if (empty($lineData['quantity_received']) || $lineData['quantity_received'] <= 0) {
                    continue;
                }

                $poLine = PurchaseOrderLine::findOrFail($lineData['po_line_id']);

                $grnLine = GrnLine::create([
                    'grn_id'            => $grn->id,
                    'line_no'           => $index + 1,
                    'po_line_id'        => $lineData['po_line_id'],
                    'model_id'          => $poLine->model_id,
                    'description'       => $poLine->description,
                    'quantity_received'  => $lineData['quantity_received'],
                    'unit'              => $poLine->unit,
                    'unit_cost'         => $poLine->unit_price,
                    'line_total'        => $lineData['quantity_received'] * $poLine->unit_price,
                    'remarks'           => $lineData['remarks'] ?? null,
                ]);

                $totalItems += $lineData['quantity_received'];

                if (!empty($lineData['serials'])) {
                    $this->processSerials($grnLine, $lineData['serials'], $grn->receiving_depot_id);
                }
            }

            $grn->update(['total_items' => $totalItems]);

            return $grn->fresh();
        });
    }

    // =========================================================================
    // POST GRN (CORE POSTING PROCESS)
    // =========================================================================

    /**
     * Post GRN – Full posting process with validation and rollback.
     *
     * Steps:
     *  1. Validate posting eligibility (pre-post checks)
     *  2. Create inventory_serials records
     *  3. Create stock_ledger entries
     *  4. Update stock_balances
     *  5. Update PO line received quantities
     *  6. Update PO status (partial / fully received)
     *  7. Mark GRN as posted
     *  8. Send notification
     *
     * Everything runs inside a DB transaction — auto rollback on failure.
     *
     * @param  Grn  $grn
     * @return Grn
     * @throws Exception
     */
    public function postGrn(Grn $grn): Grn
    {
        // ── Step 0: Basic status check ──────────────────────────────────
        if ($grn->status !== Grn::STATUS_DRAFT) {
            throw new Exception('Only draft GRNs can be posted.');
        }

        // ── Step 1: Pre-post validation ─────────────────────────────────
        $this->validateBeforePost($grn);

        // ── Steps 2–7: Inside transaction ───────────────────────────────
        $grn = DB::transaction(function () use ($grn) {

            $grn->load([
                'lines.model',
                'lines.serials',
                'lines.poLine',
                'purchaseOrder.lines',
            ]);

            foreach ($grn->lines as $line) {

                $poLine = $line->poLine;

                // ── 2a. Validate PO line not over-received ──────────
                $outstandingQty = $poLine->quantity_ordered
                                - $poLine->quantity_received
                                - $poLine->quantity_cancelled;

                if ($line->quantity_received > $outstandingQty) {
                    throw new Exception(
                        "Line #{$line->line_no}: Received quantity ({$line->quantity_received}) "
                        . "exceeds outstanding quantity ({$outstandingQty}) on PO line."
                    );
                }

                // ── 2b. Create inventory_serials (only if serials were entered) ──
                if ($line->model && $line->model->is_serial_tracked && $line->serials->count() > 0) {
                    $this->createInventorySerials($grn, $line);
                }

                // ── 3. Create stock_ledger entry ────────────────────
                $this->createStockLedgerEntry(
                    modelId:   $line->model_id,
                    depotId:   $grn->receiving_depot_id,
                    grnId:     $grn->id,
                    grnNo:     $grn->grn_no,
                    quantity:  $line->quantity_received,
                    unitCost:  $line->unit_cost,
                    line:      $line
                );

                // ── 4. Update stock_balances ────────────────────────
                $this->updateStockBalance(
                    $line->model_id,
                    $grn->receiving_depot_id,
                    $line->quantity_received,
                    $line->unit_cost
                );

                // ── 5. Update PO line received quantity ─────────────
                $poLine->increment('quantity_received', $line->quantity_received);
            }

            // ── 6. Update PO status ─────────────────────────────────
            $this->updatePurchaseOrderStatus($grn->purchaseOrder);

            // ── 7. Mark GRN as posted ───────────────────────────────
            $grn->update([
                'status'    => Grn::STATUS_POSTED,
                'posted_at' => now(),
                'posted_by' => Auth::id(),
            ]);

            return $grn->fresh([
                'lines.model',
                'lines.serials',
                'vendor',
                'receivingDepot',
                'purchaseOrder',
                'postedBy',
            ]);
        });

        // ── Step 8: Send notification (outside transaction) ─────────────
        $this->sendPostNotification($grn);

        Log::info("GRN Posted Successfully", [
            'grn_id'    => $grn->id,
            'grn_no'    => $grn->grn_no,
            'posted_by' => Auth::id(),
        ]);

        return $grn;
    }

    // =========================================================================
    // PRE-POST VALIDATION
    // =========================================================================

    /**
     * Comprehensive validation before posting.
     *
     * @param  Grn  $grn
     * @throws Exception
     */
    protected function validateBeforePost(Grn $grn): void
    {
        $grn->load([
            'lines.serials',
            'lines.model',
            'lines.poLine',
            'purchaseOrder',
        ]);

        $errors = [];

        // 1. Must have at least one line
        if ($grn->lines->isEmpty()) {
            $errors[] = 'GRN has no line items.';
        }

        // 2. PO must still be in a receivable status
        $po = $grn->purchaseOrder;
        if ($po) {
            $receivableStatuses = [
                PurchaseOrder::STATUS_APPROVED,
                PurchaseOrder::STATUS_SENT,
                PurchaseOrder::STATUS_OPEN,
                PurchaseOrder::STATUS_PARTIALLY_RECEIVED,
            ];

            if (!in_array($po->status, $receivableStatuses)) {
                $errors[] = "Purchase Order {$po->po_no} is not in a receivable status (current: {$po->status}).";
            }
        }

        foreach ($grn->lines as $line) {

            // 3. Quantity must be positive
            if ($line->quantity_received <= 0) {
                $errors[] = "Line #{$line->line_no}: Received quantity must be greater than zero.";
                continue;
            }

            // 4. PO line over-receive check
            $poLine = $line->poLine;
            if ($poLine) {
                $outstandingQty = $poLine->quantity_ordered
                                - $poLine->quantity_received
                                - $poLine->quantity_cancelled;

                if ($line->quantity_received > $outstandingQty) {
                    $errors[] = "Line #{$line->line_no}: Received qty ({$line->quantity_received}) "
                              . "exceeds PO outstanding qty ({$outstandingQty}).";
                }
            }

            // 5. Serial validation (only when serials were actually entered for this line)
            if ($line->model && $line->model->is_serial_tracked && $line->serials->count() > 0) {
                $serialCount  = $line->serials->count();
                $expectedQty  = (int) $line->quantity_received;

                if ($serialCount !== $expectedQty) {
                    $errors[] = "Line #{$line->line_no} ({$line->model->model_name}): "
                              . "Serial count ({$serialCount}) does not match received quantity ({$expectedQty}).";
                }

                // 6. Serial uniqueness check
                foreach ($line->serials as $serial) {
                    $uniqueResult = $this->validateSerialUniqueness(
                        $serial->serial_no,
                        $serial->grn_line_id
                    );

                    if (!$uniqueResult['valid']) {
                        $errors[] = "Line #{$line->line_no}: Serial '{$serial->serial_no}' — {$uniqueResult['message']}.";
                    }
                }

                // 7. Duplicate check within the same line
                $grnSerials = $line->serials->pluck('serial_no')->toArray();
                $duplicates = array_diff_assoc($grnSerials, array_unique($grnSerials));
                if (!empty($duplicates)) {
                    $errors[] = "Line #{$line->line_no}: Duplicate serials within line — "
                              . implode(', ', array_unique($duplicates));
                }
            }
        }

        // 8. Cross-line duplicate check within entire GRN
        $allSerials = [];
        foreach ($grn->lines as $line) {
            foreach ($line->serials as $serial) {
                if (in_array($serial->serial_no, $allSerials)) {
                    $errors[] = "Serial '{$serial->serial_no}' appears in multiple lines of this GRN.";
                }
                $allSerials[] = $serial->serial_no;
            }
        }

        // Throw all errors at once
        if (!empty($errors)) {
            throw new Exception(
                "GRN posting validation failed:\n• " . implode("\n• ", $errors)
            );
        }
    }

    // =========================================================================
    // INVENTORY SERIAL CREATION
    // =========================================================================

    /**
     * Create inventory_serials records for a GRN line.
     *
     * @param  Grn      $grn
     * @param  GrnLine  $line
     */
    protected function createInventorySerials(Grn $grn, GrnLine $line): void
    {
        foreach ($line->serials as $grnSerial) {

            // Check if serial already exists (e.g. re-receipt)
            $inventorySerial = InventorySerial::where('serial_no', $grnSerial->serial_no)->first();

            if (!$inventorySerial) {
                $inventorySerial = InventorySerial::create([
                    'serial_no'             => $grnSerial->serial_no,
                    'model_id'              => $line->model_id,
                    'hardware_type'         => $grnSerial->hardware_type,
                    'device_type'           => $grnSerial->device_type,
                    'telco'                 => $grnSerial->telco,
                    'sim_quota'             => $grnSerial->sim_quota,
                    'current_status'        => InventorySerial::STATUS_IN_STOCK,
                    'current_location_type' => InventorySerial::LOCATION_TYPE_DEPOT,
                    'current_location_id'   => $grn->receiving_depot_id,
                    'grn_id'                => $grn->id,
                    'grn_date'              => $grn->grn_date,
                    'po_id'                 => $grn->purchase_order_id,
                    'warranty_start'        => $grnSerial->warranty_start,
                    'warranty_end'          => $grnSerial->warranty_end,
                    'purchase_price'        => $line->unit_cost,
                    'created_by'            => Auth::id(),
                ]);
            } else {
                // Re-receipt: update location back to depot
                $inventorySerial->update([
                    'current_status'        => InventorySerial::STATUS_IN_STOCK,
                    'current_location_type' => InventorySerial::LOCATION_TYPE_DEPOT,
                    'current_location_id'   => $grn->receiving_depot_id,
                    'updated_by'            => Auth::id(),
                ]);
            }

            // Link grn_serial → inventory_serial
            $grnSerial->update(['serial_id' => $inventorySerial->id]);
        }
    }

    // =========================================================================
    // STOCK BALANCE
    // =========================================================================

    /**
     * Update stock balance for a model at a depot.
     */
    protected function updateStockBalance(int $modelId, int $depotId, $quantity, $unitCost): void
    {
        $balance = StockBalance::firstOrCreate(
            [
                'model_id'      => $modelId,
                'location_type' => StockBalance::LOCATION_TYPE_DEPOT,
                'location_id'   => $depotId,
            ],
            [
                'quantity_on_hand'   => 0,
                'quantity_reserved'  => 0,
                'last_movement_date' => now(),
            ]
        );

        $balance->update([
            'quantity_on_hand'   => $balance->quantity_on_hand + $quantity,
            'last_movement_date' => now(),
        ]);
    }

    // =========================================================================
    // STOCK LEDGER
    // =========================================================================

    /**
     * Create stock ledger entry for GRN receipt.
     */
    protected function createStockLedgerEntry(
        int     $modelId,
        int     $depotId,
        int     $grnId,
        string  $grnNo,
        $quantity,
        $unitCost,
        GrnLine $line
    ): void {

        // If serialized, create one ledger entry per serial
        if ($line->model && $line->model->is_serial_tracked && $line->serials->isNotEmpty()) {

            foreach ($line->serials as $grnSerial) {
                StockLedger::create([
                    'transaction_date'   => now()->toDateString(),
                    'transaction_no'     => $grnNo,
                    'transaction_type'   => StockLedger::TYPE_GRN_IN,
                    'reference_type'     => 'grn',
                    'reference_id'       => $grnId,
                    'serial_id'          => $grnSerial->serial_id,
                    'serial_no'          => $grnSerial->serial_no,
                    'model_id'           => $modelId,
                    'quantity'           => 1,
                    'from_location_type' => 'vendor',
                    'from_location_id'   => null,
                    'to_location_type'   => 'depot',
                    'to_location_id'     => $depotId,
                    'unit_cost'          => $unitCost,
                    'total_cost'         => $unitCost,
                    'remarks'            => "GRN Receipt: {$grnNo} | Serial: {$grnSerial->serial_no}",
                    'created_by'         => Auth::id(),
                ]);
            }

        } else {
            // Non-serialized: single aggregate ledger entry
            StockLedger::create([
                'transaction_date'   => now()->toDateString(),
                'transaction_no'     => $grnNo,
                'transaction_type'   => StockLedger::TYPE_GRN_IN,
                'reference_type'     => 'grn',
                'reference_id'       => $grnId,
                'serial_id'          => null,
                'serial_no'          => null,
                'model_id'           => $modelId,
                'quantity'           => $quantity,
                'from_location_type' => 'vendor',
                'from_location_id'   => null,
                'to_location_type'   => 'depot',
                'to_location_id'     => $depotId,
                'unit_cost'          => $unitCost,
                'total_cost'         => $quantity * $unitCost,
                'remarks'            => "GRN Receipt: {$grnNo}",
                'created_by'         => Auth::id(),
            ]);
        }
    }

    // =========================================================================
    // PO STATUS UPDATE
    // =========================================================================

    /**
     * Update Purchase Order status based on received quantities.
     */
    protected function updatePurchaseOrderStatus(PurchaseOrder $po): void
    {
        // Refresh PO lines to get latest received quantities
        $po->load('lines');

        $allFullyReceived = $po->lines->every(function ($line) {
            return $line->quantity_ordered <= ($line->quantity_received + $line->quantity_cancelled);
        });

        $anyReceived = $po->lines->some(function ($line) {
            return $line->quantity_received > 0;
        });

        if ($allFullyReceived) {
            $po->update(['status' => PurchaseOrder::STATUS_FULLY_RECEIVED]);
        } elseif ($anyReceived) {
            $po->update(['status' => PurchaseOrder::STATUS_PARTIALLY_RECEIVED]);
        }
    }

    // =========================================================================
    // POST NOTIFICATION
    // =========================================================================

    /**
     * Send email after GRN is posted.
     *
     * Uses GrnPostedEmail Mailable with PDF attachment
     * (same pattern as PurchaseOrderEmail).
     */
    protected function sendPostNotification(Grn $grn): void
    {
        try {
            // Load relationships needed for email
            $grn->load([
                'vendor',
                'receivingDepot',
                'purchaseOrder',
                'lines.model',
                'lines.serials',
                'createdBy',
                'postedBy',
            ]);

            // Collect recipients
            $recipients = collect();

            // GRN creator (if different from poster)
            if ($grn->createdBy && $grn->created_by !== Auth::id()) {
                $recipients->push($grn->createdBy);
            }

            // Active admins (exclude poster)
            $admins = \App\Models\User::role('admin')
                ->where('id', '!=', Auth::id())
                ->where('status', 'active')
                ->get();

            $recipients = $recipients->merge($admins)->unique('id');

            if ($recipients->isEmpty()) {
                return;
            }

            // Generate PDF content
            $pdfService = app(GrnPdfService::class);
            $pdfContent = $pdfService->getPdfContent($grn);

            // Send email to each recipient
            foreach ($recipients as $user) {
                if (!empty($user->email)) {
                    Mail::to($user->email)->send(
                        new GrnPostedEmail($grn, $pdfContent)
                    );
                }
            }

        } catch (Exception $e) {
            // Don't fail the whole posting if email fails
            Log::warning("GRN Post email failed", [
                'grn_id' => $grn->id,
                'error'  => $e->getMessage(),
            ]);
        }
    }

    // =========================================================================
    // CANCEL GRN
    // =========================================================================

    /**
     * Cancel GRN
     */
    public function cancelGrn(Grn $grn)
    {
        if ($grn->status !== Grn::STATUS_DRAFT) {
            throw new Exception('Only draft GRNs can be cancelled.');
        }

        return DB::transaction(function () use ($grn) {
            $grn->update([
                'status'     => Grn::STATUS_CANCELLED,
                'updated_by' => Auth::id(),
            ]);
            return $grn;
        });
    }

    // =========================================================================
    // SERIAL PROCESSING (DRAFT STAGE)
    // =========================================================================

    /**
     * Process serials for a GRN line during create/update.
     */
    private function processSerials(GrnLine $grnLine, array $serialsData, $depotId): void
    {
        foreach ($serialsData as $index => $serialData) {
            if (empty($serialData['serial_no'])) {
                continue;
            }

            GrnSerial::create([
                'grn_line_id'    => $grnLine->id,
                'serial_no'      => $serialData['serial_no'],
                'hardware_type'  => $serialData['hardware_type'] ?? null,
                'device_type'    => $serialData['device_type'] ?? null,
                'telco'          => $serialData['telco'] ?? null,
                'sim_quota'      => $serialData['sim_quota'] ?? null,
                'warranty_start' => $serialData['warranty_start'] ?? null,
                'warranty_end'   => $serialData['warranty_end'] ?? null,
                'remarks'        => $serialData['remarks'] ?? null,
            ]);
        }
    }

    // =========================================================================
    // SERIAL VALIDATION
    // =========================================================================

    /**
     * Validate serial uniqueness against inventory and unposted GRNs.
     */
    public function validateSerialUniqueness($serialNo, $excludeGrnLineId = null): array
    {
        // Check in inventory_serials
        $existsInInventory = InventorySerial::where('serial_no', $serialNo)->exists();

        if ($existsInInventory) {
            return [
                'valid'   => false,
                'message' => 'Serial number already exists in inventory',
            ];
        }

        // Check in unposted GRN serials (exclude current line)
        $query = GrnSerial::where('serial_no', $serialNo)
            ->whereHas('grnLine.grn', function ($q) {
                $q->where('status', Grn::STATUS_DRAFT);
            });

        if ($excludeGrnLineId) {
            $query->where('grn_line_id', '!=', $excludeGrnLineId);
        }

        if ($query->exists()) {
            return [
                'valid'   => false,
                'message' => 'Serial number already exists in another draft GRN',
            ];
        }

        return [
            'valid'   => true,
            'message' => 'Serial number is available',
        ];
    }

    /**
     * Parse multiple serials (comma or line separated).
     */
    public function parseMultipleSerials($input): array
    {
        $serials = preg_split('/[,\n\r]+/', $input);
        $serials = array_map('trim', $serials);
        $serials = array_filter($serials);
        $serials = array_unique($serials);

        return array_values($serials);
    }

    // =========================================================================
    // NUMBER SERIES
    // =========================================================================

    /**
     * Generate GRN number from number series.
     */
    private function generateGrnNumber(): string
    {
        return DB::transaction(function () {
            $series = NumberSeries::where('series_type', 'grn')
                ->where('is_active', true)
                ->lockForUpdate()
                ->first();

            if (!$series) {
                throw new Exception('No active number series found for GRN.');
            }

            $number = $series->current_number + 1;
            $grnNo  = $series->prefix . str_pad($number, $series->number_length, '0', STR_PAD_LEFT);

            if ($series->suffix) {
                $grnNo .= $series->suffix;
            }

            $series->update(['current_number' => $number]);

            return $grnNo;
        });
    }
}
