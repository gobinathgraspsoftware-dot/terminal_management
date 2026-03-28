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
     * Create a claim from a completed ticket.
     * Called automatically by TicketService when ticket transitions to done_success / done_fail.
     */
    public function createTicketClaim(Ticket $ticket, array $data = []): Claim
    {
        return DB::transaction(function () use ($ticket, $data) {
            $claimNo     = NumberSeries::getNextNumber('ticket_claim');
            $triggeredBy = Auth::id() ?? $ticket->technician_id;

            $claim = Claim::create([
                'claim_no'              => $claimNo,
                'claim_category'        => Claim::CATEGORY_TICKET,
                'ticket_id'             => $ticket->id,
                'claim_date'            => now()->toDateString(),
                'technician_id'         => $ticket->technician_id,
                'description'           => "Ticket claim for #{$ticket->ticket_no} - {$ticket->merchant_name}",
                'total_mileage_km'      => $data['mileage']           ?? $ticket->mileage           ?? 0,
                'total_mileage_amount'  => $data['mileage_amount']     ?? $ticket->mileage_amount     ?? 0,
                'total_allowance_amount'=> ($data['toll']              ?? $ticket->toll              ?? 0)
                                        + ($data['standby_meal']       ?? $ticket->standby_meal       ?? 0),
                'total_amount'          => $data['total_claim_amount'] ?? $ticket->total_claim_amount ?? 0,
                'original_amount'       => $data['total_claim_amount'] ?? $ticket->total_claim_amount ?? 0,
                'remarks'               => $data['remarks']            ?? $ticket->mileage_remarks,
                'status'                => Claim::STATUS_SUBMITTED,
                'submitted_at'          => now(),
                'submitted_by'          => $triggeredBy,
                'created_by'            => $triggeredBy,
            ]);

            return $claim;
        });
    }

    // ══════════════════════════════════════
    // Other Claims
    // ══════════════════════════════════════

    public function createOtherClaim(array $data, array $files = []): Claim
    {
        return DB::transaction(function () use ($data, $files) {
            $claimNo = NumberSeries::getNextNumber('other_claim');

            $claim = Claim::create([
                'claim_no'         => $claimNo,
                'claim_category'   => Claim::CATEGORY_OTHER,
                'ticket_id'        => $data['ticket_id']        ?? null,
                'claim_date'       => now()->toDateString(),
                'technician_id'    => $data['technician_id']    ?? Auth::id(),
                'description'      => $data['description'],
                'claim_type_label' => $data['claim_type_label'] ?? 'Other',
                'total_amount'     => $data['claim_amount'],
                'original_amount'  => $data['claim_amount'],
                'remarks'          => $data['remarks']          ?? null,
                'status'           => Claim::STATUS_SUBMITTED,
                'submitted_at'     => now(),
                'submitted_by'     => Auth::id(),
                'created_by'       => Auth::id(),
            ]);

            if (!empty($files)) {
                $this->storeAttachments($claim, $files);
            }

            return $claim;
        });
    }

    public function updateOtherClaim(Claim $claim, array $data, array $files = []): Claim
    {
        return DB::transaction(function () use ($claim, $data, $files) {
            $updateData = [
                'claim_type_label' => $data['claim_type_label'] ?? $claim->claim_type_label,
                'description'      => $data['description']      ?? $claim->description,
                'total_amount'     => $data['claim_amount']     ?? $claim->total_amount,
                'original_amount'  => $data['claim_amount']     ?? $claim->total_amount,
                'ticket_id'        => $data['ticket_id']        ?? $claim->ticket_id,
                'remarks'          => $data['remarks']          ?? $claim->remarks,
                'updated_by'       => Auth::id(),
            ];

            if (Auth::user()->hasRole('admin') && isset($data['technician_id'])) {
                $updateData['technician_id'] = $data['technician_id'];
            }

            $claim->update($updateData);

            if (!empty($files)) {
                $this->storeAttachments($claim, $files);
            }

            return $claim->fresh();
        });
    }

    public function deleteAttachment(ClaimAttachment $attachment): bool
    {
        $filePath = $_SERVER['DOCUMENT_ROOT'] . '/storage/' . $attachment->file_path;
        if (file_exists($filePath)) {
            @unlink($filePath);
        }
        return $attachment->delete();
    }

    protected function storeAttachments(Claim $claim, array $files): void
    {
        $uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/storage/claim-proofs/' . $claim->id;
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        foreach ($files as $file) {
            if (!$file || !$file->isValid()) continue;

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

    public function processBulkPayment(array $claimIds): array
    {
        return DB::transaction(function () use ($claimIds) {
            $claims = Claim::whereIn('id', $claimIds)
                ->where('status', Claim::STATUS_VERIFIED)
                ->get();

            if ($claims->isEmpty()) {
                throw new \Exception('No verified claims found for payment processing.');
            }

            $processed   = 0;
            $totalAmount = 0;

            foreach ($claims as $claim) {
                $claim->update([
                    'status'     => Claim::STATUS_PENDING_PAYMENT,
                    'updated_by' => Auth::id(),
                ]);
                $processed++;
                $totalAmount += (float) $claim->total_amount;
            }

            return ['processed' => $processed, 'total_amount' => $totalAmount];
        });
    }

    public function markAsPaid(array $claimIds): int
    {
        return DB::transaction(function () use ($claimIds) {
            return Claim::whereIn('id', $claimIds)
                ->where('status', Claim::STATUS_PENDING_PAYMENT)
                ->update([
                    'status'     => Claim::STATUS_PAID,
                    'paid_at'    => now(),
                    'paid_by'    => Auth::id(),
                    'updated_by' => Auth::id(),
                ]);
        });
    }

    // ══════════════════════════════════════
    // DataTable Helpers
    // ══════════════════════════════════════

    /**
     * Build DataTable response for claims listing.
     *
     * BUG FIX: Eager-load ticket with withTrashed() so soft-deleted tickets
     * still return their data (ticket_no, vendor, merchant, supervisor).
     * Without this, soft-deleted tickets return null and all columns show "-".
     */
    public function getClaimsDataTable(
        $request,
        string $category,
        ?User $user = null,
        ?string $scopeType = null
    ): array {
        $draw           = (int) $request->input('draw', 1);
        $start          = (int) $request->input('start', 0);
        $length         = (int) $request->input('length', 10);
        $searchValue    = $request->input('search.value', '');
        $orderColumnIdx = (int) $request->input('order.0.column', 0);
        $orderDir       = $request->input('order.0.dir', 'desc') === 'asc' ? 'asc' : 'desc';

        $query = Claim::query()->where('claim_category', $category);

        // Role scoping
        if ($user && $scopeType === 'team') {
            $teamIds   = User::where('supervisor_id', $user->id)->pluck('id')->toArray();
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

        // BUG FIX: Use closure-based eager loading with withTrashed() for ticket
        // so soft-deleted tickets still return their data.
        if ($category === Claim::CATEGORY_TICKET) {
            $query->with([
                'ticket'             => fn($q) => $q->withTrashed(),
                'ticket.vendor'      => fn($q) => $q->withTrashed(),
                'ticket.supervisor',
                'ticket.jobCategory',
                'ticket.jobType',
                'technician',
            ]);
        } else {
            $query->with([
                'submitter',
                'technician',
                'ticket'        => fn($q) => $q->withTrashed(),
                'ticket.vendor' => fn($q) => $q->withTrashed(),
                'attachments',
            ]);
        }

        $recordsTotal = $query->count();

        // Search
        if ($searchValue) {
            $query->where(function ($q) use ($searchValue, $category) {
                $q->where('claim_no', 'like', "%{$searchValue}%")
                  ->orWhere('description', 'like', "%{$searchValue}%")
                  ->orWhere('total_amount', 'like', "%{$searchValue}%");

                if ($category === Claim::CATEGORY_TICKET) {
                    // Search in tickets including soft-deleted
                    $q->orWhereHas('ticket', function ($tq) use ($searchValue) {
                        $tq->withTrashed()
                           ->where('ticket_no', 'like', "%{$searchValue}%")
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
        $columns     = $category === Claim::CATEGORY_TICKET
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

    /**
     * Build DataTable response for bulk payment (verified + pending_payment claims).
     */
    public function getBulkPaymentDataTable($request, string $category = 'all'): array
    {
        $draw           = (int) $request->input('draw', 1);
        $start          = (int) $request->input('start', 0);
        $length         = (int) $request->input('length', 25);
        $searchValue    = $request->input('search.value', '');
        $orderColumnIdx = (int) $request->input('order.0.column', 0);
        $orderDir       = $request->input('order.0.dir', 'asc') === 'desc' ? 'desc' : 'asc';

        $query = Claim::whereIn('status', [Claim::STATUS_VERIFIED, Claim::STATUS_PENDING_PAYMENT])
            ->with([
                'technician', 'submitter',
                'ticket'        => fn($q) => $q->withTrashed(),
                'ticket.vendor' => fn($q) => $q->withTrashed(),
            ]);

        if ($category === 'ticket') {
            $query->ticketClaims();
        } elseif ($category === 'other') {
            $query->otherClaims();
        }

        if ($request->filled('status_filter')) {
            $query->where('status', $request->input('status_filter'));
        }

        $recordsTotal = $query->count();

        if ($searchValue) {
            $query->where(function ($q) use ($searchValue) {
                $q->where('claim_no', 'like', "%{$searchValue}%")
                  ->orWhereHas('technician', fn($tq) => $tq->where('name', 'like', "%{$searchValue}%"))
                  ->orWhereHas('submitter',  fn($sq) => $sq->where('name', 'like', "%{$searchValue}%"))
                  ->orWhereHas('ticket',     fn($tq) => $tq->withTrashed()->where('ticket_no', 'like', "%{$searchValue}%"));
            });
        }

        $recordsFiltered = $query->count();

        $columns     = ['claim_no', 'claim_category', 'technician_id', 'total_amount', 'status', 'submitted_at'];
        $orderColumn = $columns[$orderColumnIdx] ?? 'submitted_at';
        $query->orderBy($orderColumn, $orderDir);

        $data = $query->skip($start)->take($length)->get();

        $totals       = Claim::whereIn('status', [Claim::STATUS_VERIFIED, Claim::STATUS_PENDING_PAYMENT]);
        if ($category !== 'all') {
            $totals->where('claim_category', $category);
        }
        $totalVerified = (clone $totals)->where('status', Claim::STATUS_VERIFIED)->sum('total_amount');
        $totalPending  = (clone $totals)->where('status', Claim::STATUS_PENDING_PAYMENT)->sum('total_amount');

        return [
            'draw'            => $draw,
            'recordsTotal'    => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data'            => $data,
            'meta'            => [
                'total_verified' => (float) $totalVerified,
                'total_pending'  => (float) $totalPending,
            ],
        ];
    }

    /**
     * Build DataTable response for payment history (paid claims).
     */
    public function getPaymentHistoryDataTable(
        $request,
        ?User $user = null,
        ?string $scopeType = null
    ): array {
        $draw           = (int) $request->input('draw', 1);
        $start          = (int) $request->input('start', 0);
        $length         = (int) $request->input('length', 25);
        $searchValue    = $request->input('search.value', '');
        $orderColumnIdx = (int) $request->input('order.0.column', 0);
        $orderDir       = $request->input('order.0.dir', 'desc') === 'asc' ? 'asc' : 'desc';

        $query = Claim::where('status', Claim::STATUS_PAID)
            ->with([
                'technician', 'submitter', 'payer',
                'ticket'        => fn($q) => $q->withTrashed(),
                'ticket.vendor' => fn($q) => $q->withTrashed(),
            ]);

        if ($user && $scopeType === 'team') {
            $teamIds   = User::where('supervisor_id', $user->id)->pluck('id')->toArray();
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

        if ($request->filled('category')) {
            $query->where('claim_category', $request->input('category'));
        }
        if ($request->filled('date_from')) {
            $query->whereDate('paid_at', '>=', $request->input('date_from'));
        }
        if ($request->filled('date_to')) {
            $query->whereDate('paid_at', '<=', $request->input('date_to'));
        }

        $recordsTotal = $query->count();

        if ($searchValue) {
            $query->where(function ($q) use ($searchValue) {
                $q->where('claim_no', 'like', "%{$searchValue}%")
                  ->orWhereHas('technician', fn($tq) => $tq->where('name', 'like', "%{$searchValue}%"))
                  ->orWhereHas('submitter',  fn($sq) => $sq->where('name', 'like', "%{$searchValue}%"))
                  ->orWhereHas('ticket',     fn($tq) => $tq->withTrashed()->where('ticket_no', 'like', "%{$searchValue}%"));
            });
        }

        $recordsFiltered = $query->count();

        $columns     = ['claim_no', 'claim_category', 'technician_id', 'total_amount', 'paid_at', 'paid_by'];
        $orderColumn = $columns[$orderColumnIdx] ?? 'paid_at';
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
