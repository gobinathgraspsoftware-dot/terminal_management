<?php

namespace App\Services;

use App\Models\Claim;
use App\Models\ClaimAttachment;
use App\Models\NumberSeries;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ClaimManagementService
{
    // ══════════════════════════════════════
    // Ticket Claims
    // ══════════════════════════════════════

    /**
     * Create a claim from a completed ticket
     *
     * @param Ticket $ticket
     * @param array  $data  Optional overrides (from admin form)
     * @return Claim
     */
    public function createTicketClaim(Ticket $ticket, array $data = []): Claim
    {
        return DB::transaction(function () use ($ticket, $data) {
            $claimNo = NumberSeries::getNextNumber('ticket_claim');

            $claim = Claim::create([
                'claim_no'              => $claimNo,
                'claim_category'        => Claim::CATEGORY_TICKET,
                'ticket_id'             => $ticket->id,
                'claim_date'            => now()->toDateString(),
                'technician_id'         => $ticket->technician_id,
                'description'           => "Ticket claim for #{$ticket->ticket_no} - {$ticket->merchant_name}",
                'total_mileage_km'      => $data['mileage'] ?? $ticket->mileage ?? 0,
                'total_mileage_amount'  => $data['mileage_amount'] ?? $ticket->mileage_amount ?? 0,
                'total_allowance_amount'=> ($data['toll'] ?? $ticket->toll ?? 0) + ($data['standby_meal'] ?? $ticket->standby_meal ?? 0),
                'total_amount'          => $data['total_claim_amount'] ?? $ticket->total_claim_amount ?? 0,
                'original_amount'       => $data['total_claim_amount'] ?? $ticket->total_claim_amount ?? 0,
                'remarks'               => $data['remarks'] ?? $ticket->mileage_remarks,
                'status'                => Claim::STATUS_SUBMITTED,
                'submitted_at'          => now(),
                'submitted_by'          => Auth::id(),
                'created_by'            => Auth::id(),
            ]);

            return $claim;
        });
    }

    // ══════════════════════════════════════
    // Other Claims
    // ══════════════════════════════════════

    /**
     * Create an other/manual claim
     */
    public function createOtherClaim(array $data, array $files = []): Claim
    {
        return DB::transaction(function () use ($data, $files) {
            $claimNo = NumberSeries::getNextNumber('other_claim');

            $claim = Claim::create([
                'claim_no'              => $claimNo,
                'claim_category'        => Claim::CATEGORY_OTHER,
                'ticket_id'             => $data['ticket_id'] ?? null,
                'claim_date'            => now()->toDateString(),
                'technician_id'         => $data['technician_id'] ?? Auth::id(),
                'description'           => $data['description'],
                'claim_type_label'      => $data['claim_type_label'] ?? 'Other',
                'total_amount'          => $data['claim_amount'],
                'original_amount'       => $data['claim_amount'],
                'remarks'               => $data['remarks'] ?? null,
                'status'                => Claim::STATUS_SUBMITTED,
                'submitted_at'          => now(),
                'submitted_by'          => Auth::id(),
                'created_by'            => Auth::id(),
            ]);

            // Handle file attachments
            if (!empty($files)) {
                $this->storeAttachments($claim, $files);
            }

            return $claim;
        });
    }

    /**
     * Store claim attachments using cPanel-compatible file upload
     */
    protected function storeAttachments(Claim $claim, array $files): void
    {
        $uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/storage/claim-proofs/' . $claim->id;
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        foreach ($files as $file) {
            if (!$file || !$file->isValid()) continue;

            // Capture metadata BEFORE move (temp file is deleted after move)
            $originalName = $file->getClientOriginalName();
            $fileSize     = $file->getSize();
            $mimeType     = $file->getClientMimeType();

            $fileName = time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '', $originalName);
            $file->move($uploadDir, $fileName);

            ClaimAttachment::create([
                'claim_id'    => $claim->id,
                'file_name'   => $originalName,
                'file_path'   => 'claim-proofs/' . $claim->id . '/' . $fileName,
                'file_size'   => $fileSize ?? 0,
                'mime_type'   => $mimeType ?? 'application/octet-stream',
                'description' => 'Claim proof',
                'uploaded_by' => Auth::id(),
            ]);
        }
    }

    // ══════════════════════════════════════
    // Admin Verification
    // ══════════════════════════════════════

    /**
     * Verify a claim (admin action)
     */
    public function verifyClaim(Claim $claim, array $data): Claim
    {
        return DB::transaction(function () use ($claim, $data) {
            $updateData = [
                'status'        => Claim::STATUS_VERIFIED,
                'admin_remarks' => $data['admin_remarks'] ?? null,
                'verified_at'   => now(),
                'verified_by'   => Auth::id(),
                'updated_by'    => Auth::id(),
            ];

            // If admin edited the amount
            if (isset($data['total_amount']) && $data['total_amount'] != $claim->total_amount) {
                if (is_null($claim->original_amount)) {
                    $updateData['original_amount'] = $claim->total_amount;
                }
                $updateData['total_amount'] = $data['total_amount'];
            }

            $claim->update($updateData);
            return $claim->fresh();
        });
    }

    /**
     * Mark a claim as non-claimable (admin action)
     */
    public function markNonClaimable(Claim $claim, array $data): Claim
    {
        return DB::transaction(function () use ($claim, $data) {
            $claim->update([
                'status'        => Claim::STATUS_NON_CLAIMABLE,
                'admin_remarks' => $data['admin_remarks'] ?? null,
                'verified_at'   => now(),
                'verified_by'   => Auth::id(),
                'updated_by'    => Auth::id(),
            ]);
            return $claim->fresh();
        });
    }

    /**
     * Update claim amount (admin edit before verification)
     */
    public function updateClaimAmount(Claim $claim, float $newAmount, ?string $adminRemarks = null): Claim
    {
        if (is_null($claim->original_amount)) {
            $claim->original_amount = $claim->total_amount;
        }
        $claim->total_amount  = $newAmount;
        $claim->admin_remarks = $adminRemarks;
        $claim->updated_by    = Auth::id();
        $claim->save();

        return $claim;
    }

    // ══════════════════════════════════════
    // Bulk Payment
    // ══════════════════════════════════════

    /**
     * Process bulk payment for verified claims
     */
    public function processBulkPayment(array $claimIds): array
    {
        return DB::transaction(function () use ($claimIds) {
            $claims = Claim::whereIn('id', $claimIds)
                ->where('status', Claim::STATUS_VERIFIED)
                ->get();

            if ($claims->isEmpty()) {
                throw new \Exception('No verified claims found for payment processing.');
            }

            $processed = 0;
            $totalAmount = 0;

            foreach ($claims as $claim) {
                $claim->update([
                    'status'     => Claim::STATUS_PENDING_PAYMENT,
                    'updated_by' => Auth::id(),
                ]);
                $processed++;
                $totalAmount += (float) $claim->total_amount;
            }

            return [
                'processed'    => $processed,
                'total_amount' => $totalAmount,
            ];
        });
    }

    /**
     * Mark claims as paid
     */
    public function markAsPaid(array $claimIds): int
    {
        return DB::transaction(function () use ($claimIds) {
            return Claim::whereIn('id', $claimIds)
                ->where('status', Claim::STATUS_PENDING_PAYMENT)
                ->update([
                    'status'   => Claim::STATUS_PAID,
                    'paid_at'  => now(),
                    'paid_by'  => Auth::id(),
                    'updated_by' => Auth::id(),
                ]);
        });
    }

    // ══════════════════════════════════════
    // DataTable Helpers (manual server-side)
    // ══════════════════════════════════════

    /**
     * Build DataTable response for claims
     */
    public function getClaimsDataTable(
        $request,
        string $category,
        ?User $user = null,
        ?string $scopeType = null
    ): array {
        $draw            = (int) $request->input('draw', 1);
        $start           = (int) $request->input('start', 0);
        $length          = (int) $request->input('length', 10);
        $searchValue     = $request->input('search.value', '');
        $orderColumnIdx  = (int) $request->input('order.0.column', 0);
        $orderDir        = $request->input('order.0.dir', 'desc') === 'asc' ? 'asc' : 'desc';

        $query = Claim::query()->where('claim_category', $category);

        // Scope by role
        if ($user && $scopeType === 'team') {
            $teamIds = User::where('supervisor_id', $user->id)->pluck('id')->toArray();
            $teamIds[] = $user->id;
            $query->where(function ($q) use ($teamIds, $user) {
                $q->whereIn('technician_id', $teamIds)
                  ->orWhere('submitted_by', $user->id);
            });
        } elseif ($user && $scopeType === 'own') {
            $query->where(function ($q) use ($user) {
                $q->where('technician_id', $user->id)
                  ->orWhere('submitted_by', $user->id);
            });
        }

        // Status filter
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        // Eager-load
        if ($category === Claim::CATEGORY_TICKET) {
            $query->with(['ticket.vendor', 'ticket.supervisor', 'technician']);
        } else {
            $query->with(['submitter', 'technician', 'attachments']);
        }

        $recordsTotal = $query->count();

        // Search
        if ($searchValue) {
            $query->where(function ($q) use ($searchValue, $category) {
                $q->where('claim_no', 'like', "%{$searchValue}%")
                  ->orWhere('description', 'like', "%{$searchValue}%")
                  ->orWhere('total_amount', 'like', "%{$searchValue}%");

                if ($category === Claim::CATEGORY_TICKET) {
                    $q->orWhereHas('ticket', function ($tq) use ($searchValue) {
                        $tq->where('ticket_no', 'like', "%{$searchValue}%")
                           ->orWhere('merchant_name', 'like', "%{$searchValue}%");
                    });
                }

                $q->orWhereHas('technician', function ($tq) use ($searchValue) {
                    $tq->where('name', 'like', "%{$searchValue}%");
                });
            });
        }

        $recordsFiltered = $query->count();

        // Ordering
        $columns = $category === Claim::CATEGORY_TICKET
            ? ['claim_no', 'ticket_id', 'technician_id', 'total_amount', 'status', 'submitted_at']
            : ['claim_no', 'submitted_by', 'claim_type_label', 'total_amount', 'status', 'submitted_at'];
        $orderColumn = $columns[$orderColumnIdx] ?? 'submitted_at';
        $query->orderBy($orderColumn, $orderDir);

        $data = $query->skip($start)->take($length)->get();

        return [
            'draw'            => $draw,
            'recordsTotal'    => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data'            => $data,
        ];
    }
}
