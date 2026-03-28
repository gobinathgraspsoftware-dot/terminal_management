<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\BulkPaymentRequest;
use App\Http\Requests\StoreOtherClaimRequest;
use App\Http\Requests\VerifyClaimRequest;
use App\Models\Claim;
use App\Models\Ticket;
use App\Models\User;
use App\Services\ClaimManagementService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ClaimManagementController extends Controller
{
    use AuthorizesRequests;

    protected ClaimManagementService $service;

    public function __construct(ClaimManagementService $service)
    {
        $this->service = $service;
    }

    // ══════════════════════════════════════════════
    // Landing Page
    // ══════════════════════════════════════════════

    public function index()
    {
        $this->authorize('viewAny', Claim::class);

        $stats = [
            'ticket_submitted'  => Claim::ticketClaims()->submitted()->count(),
            'ticket_verified'   => Claim::ticketClaims()->verified()->count(),
            'other_submitted'   => Claim::otherClaims()->submitted()->count(),
            'other_verified'    => Claim::otherClaims()->verified()->count(),
            'pending_payment'   => Claim::pendingPayment()->count(),
            'paid_this_month'   => Claim::where('status', Claim::STATUS_PAID)
                                        ->whereMonth('paid_at', now()->month)
                                        ->whereYear('paid_at', now()->year)
                                        ->count(),
        ];

        return view('admin.claims.index', compact('stats'));
    }

    // ══════════════════════════════════════════════
    // Ticket Claims
    // ══════════════════════════════════════════════

    public function ticketClaims()
    {
        $this->authorize('viewAny', Claim::class);
        return view('admin.claims.ticket-claims');
    }

    public function ticketClaimsData(Request $request)
    {
        $this->authorize('viewAny', Claim::class);
        $result = $this->service->getClaimsDataTable($request, Claim::CATEGORY_TICKET);

        $result['data'] = $result['data']->map(function ($claim) {
            return [
                'id'            => $claim->id,
                'claim_no'      => $claim->claim_no,
                'ticket_no'     => $claim->ticket->ticket_no ?? '-',
                'vendor'        => $claim->ticket->vendor->company_name ?? '-',
                'merchant_name' => $claim->ticket->merchant_name ?? '-',
                'supervisor'    => $claim->ticket->supervisor->name ?? '-',
                'technician'    => $claim->technician->name ?? '-',
                'total_amount'  => number_format((float) $claim->total_amount, 2),
                'status'        => Claim::getStatusBadge($claim->status),
                'submitted_at'  => $claim->submitted_at ? $claim->submitted_at->format('d/m/Y H:i') : '-',
                'actions'       => $this->getActionButtons($claim),
            ];
        });

        return response()->json($result);
    }

    // ══════════════════════════════════════════════
    // Other Claims
    // ══════════════════════════════════════════════

    public function otherClaims()
    {
        $this->authorize('viewAny', Claim::class);
        return view('admin.claims.other-claims');
    }

    public function otherClaimsData(Request $request)
    {
        $this->authorize('viewAny', Claim::class);
        $result = $this->service->getClaimsDataTable($request, Claim::CATEGORY_OTHER);

        $result['data'] = $result['data']->map(function ($claim) {
            return [
                'id'              => $claim->id,
                'claim_no'        => $claim->claim_no,
                'submitted_by'    => $claim->submitter->name ?? ($claim->technician->name ?? '-'),
                'claim_type'      => $claim->claim_type_label ?? 'Others',
                'description'     => \Illuminate\Support\Str::limit($claim->description, 50),
                'total_amount'    => number_format((float) $claim->total_amount, 2),
                'status'          => Claim::getStatusBadge($claim->status),
                'ticket_no'       => $claim->ticket->ticket_no ?? '-',
                'submitted_at'    => $claim->submitted_at ? $claim->submitted_at->format('d/m/Y H:i') : '-',
                'has_attachments' => $claim->attachments->isNotEmpty()
                    ? '<i class="bi bi-paperclip text-primary" title="Has attachments"></i>' : '',
                'actions'         => $this->getActionButtons($claim),
            ];
        });

        return response()->json($result);
    }

    public function createOtherClaim()
    {
        $this->authorize('create', Claim::class);

        $tickets = Ticket::whereIn('status', [
                Ticket::STATUS_DONE_SUCCESS,
                Ticket::STATUS_DONE_FAIL,
                Ticket::STATUS_CLOSED,
            ])
            ->orderBy('ticket_no', 'desc')
            ->get(['id', 'ticket_no', 'merchant_name']);

        $claimTypes = Claim::getOtherClaimTypes();

        $technicians = User::whereHas('roles', function ($q) {
                $q->where('roles.name', 'technician');
            })
            ->where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'name']);

        $externalSupervisors = User::whereHas('roles', function ($q) {
                $q->where('roles.name', 'supervisor');
            })
            ->where('supervisor_type', User::SUPERVISOR_TYPE_EXTERNAL)
            ->where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'name']);

        $claimableUsers = $technicians->merge($externalSupervisors)->sortBy('name');

        return view('admin.claims.create-other-claim', compact('tickets', 'claimTypes', 'claimableUsers'));
    }

    public function storeOtherClaim(StoreOtherClaimRequest $request)
    {
        $this->authorize('create', Claim::class);

        try {
            $files = $request->file('attachments', []);
            $claim = $this->service->createOtherClaim($request->validated(), is_array($files) ? $files : [$files]);
            return response()->json(['success' => true, 'message' => "Other Claim {$claim->claim_no} created successfully."]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // ══════════════════════════════════════════════
    // Show / Verify / Actions
    // ══════════════════════════════════════════════

    public function show(Claim $claim)
    {
        $this->authorize('view', $claim);

        $claim->load([
            'technician',
            'submitter',
            'verifier',
            'payer',
            'ticket.vendor',
            'ticket.vendorBranch',
            'ticket.supervisor',
            'ticket.jobCategory',
            'ticket.jobType',
            'ticket.state',
            'ticket.city',
            'ticket.proofs',
            'attachments.uploadedBy',
        ]);

        return view('admin.claims.show', compact('claim'));
    }

    public function verify(VerifyClaimRequest $request, Claim $claim)
    {
        $this->authorize('verify', $claim);

        try {
            $this->service->verifyClaim($claim, $request->validated());
            return response()->json(['success' => true, 'message' => 'Claim verified successfully.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function markNonClaimable(Request $request, Claim $claim)
    {
        $this->authorize('verify', $claim);
        $request->validate(['admin_remarks' => 'required|string|max:2000']);

        try {
            $this->service->markNonClaimable($claim, $request->only('admin_remarks'));
            return response()->json(['success' => true, 'message' => 'Claim marked as Non-Claimable.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function updateAmount(Request $request, Claim $claim)
    {
        $this->authorize('verify', $claim);

        $request->validate([
            'total_amount'  => 'required|numeric|min:0',
            'admin_remarks' => 'nullable|string|max:2000',
        ]);

        try {
            $this->service->updateClaimAmount(
                $claim,
                (float) $request->input('total_amount'),
                $request->input('admin_remarks')
            );
            return response()->json(['success' => true, 'message' => 'Claim amount updated.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // ══════════════════════════════════════════════
    // Bulk Payment (redesigned — DataTable based)
    // ══════════════════════════════════════════════

    public function bulkPayment(Request $request)
    {
        $this->authorize('bulkPay', Claim::class);

        $category = $request->input('category', 'all');

        // Summary stats for header cards
        $verifiedCount  = Claim::where('status', Claim::STATUS_VERIFIED)
            ->when($category !== 'all', fn($q) => $q->where('claim_category', $category))
            ->count();
        $pendingCount   = Claim::where('status', Claim::STATUS_PENDING_PAYMENT)
            ->when($category !== 'all', fn($q) => $q->where('claim_category', $category))
            ->count();
        $verifiedAmount = Claim::where('status', Claim::STATUS_VERIFIED)
            ->when($category !== 'all', fn($q) => $q->where('claim_category', $category))
            ->sum('total_amount');
        $pendingAmount  = Claim::where('status', Claim::STATUS_PENDING_PAYMENT)
            ->when($category !== 'all', fn($q) => $q->where('claim_category', $category))
            ->sum('total_amount');

        return view('admin.claims.bulk-payment', compact(
            'category', 'verifiedCount', 'pendingCount', 'verifiedAmount', 'pendingAmount'
        ));
    }

    /**
     * AJAX DataTable for bulk payment listing
     */
    public function bulkPaymentData(Request $request)
    {
        $this->authorize('bulkPay', Claim::class);

        $category = $request->input('category', 'all');
        $result   = $this->service->getBulkPaymentDataTable($request, $category);

        $result['data'] = $result['data']->map(function ($claim) {
            $claimant = $claim->technician->name ?? $claim->submitter->name ?? '-';
            return [
                'id'           => $claim->id,
                'claim_no'     => $claim->claim_no,
                'category'     => $claim->claim_category === Claim::CATEGORY_TICKET
                    ? '<span class="badge bg-info">Ticket</span>'
                    : '<span class="badge bg-secondary">Other</span>',
                'claimant'     => $claimant,
                'ticket_no'    => $claim->ticket->ticket_no ?? '-',
                'vendor'       => $claim->ticket->vendor->company_name ?? '-',
                'total_amount' => number_format((float) $claim->total_amount, 2),
                'status'       => Claim::getStatusBadge($claim->status),
                'submitted_at' => $claim->submitted_at ? $claim->submitted_at->format('d/m/Y') : '-',
            ];
        });

        return response()->json($result);
    }

    public function processBulkPayment(BulkPaymentRequest $request)
    {
        $this->authorize('bulkPay', Claim::class);

        try {
            $result = $this->service->processBulkPayment($request->input('claim_ids'));
            return response()->json([
                'success' => true,
                'message' => "{$result['processed']} claim(s) moved to Pending Payment. Total: RM " . number_format($result['total_amount'], 2),
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function markPaid(Request $request)
    {
        $this->authorize('bulkPay', Claim::class);

        $request->validate([
            'claim_ids'   => 'required|array|min:1',
            'claim_ids.*' => 'integer|exists:claims,id',
        ]);

        try {
            $count = $this->service->markAsPaid($request->input('claim_ids'));
            return response()->json(['success' => true, 'message' => "{$count} claim(s) marked as Paid."]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // ══════════════════════════════════════════════
    // Payment History
    // ══════════════════════════════════════════════

    public function paymentHistory(Request $request)
    {
        $this->authorize('viewAny', Claim::class);

        $totalPaid       = Claim::where('status', Claim::STATUS_PAID)->sum('total_amount');
        $paidThisMonth   = Claim::where('status', Claim::STATUS_PAID)
            ->whereMonth('paid_at', now()->month)
            ->whereYear('paid_at', now()->year)
            ->sum('total_amount');
        $paidCount       = Claim::where('status', Claim::STATUS_PAID)->count();

        return view('admin.claims.payment-history', compact('totalPaid', 'paidThisMonth', 'paidCount'));
    }

    public function paymentHistoryData(Request $request)
    {
        $this->authorize('viewAny', Claim::class);

        $result = $this->service->getPaymentHistoryDataTable($request);

        $result['data'] = $result['data']->map(function ($claim) {
            return [
                'id'           => $claim->id,
                'claim_no'     => $claim->claim_no,
                'category'     => $claim->claim_category === Claim::CATEGORY_TICKET
                    ? '<span class="badge bg-info">Ticket</span>'
                    : '<span class="badge bg-secondary">Other</span>',
                'claimant'     => $claim->technician->name ?? $claim->submitter->name ?? '-',
                'ticket_no'    => $claim->ticket->ticket_no ?? '-',
                'vendor'       => $claim->ticket->vendor->company_name ?? '-',
                'total_amount' => number_format((float) $claim->total_amount, 2),
                'paid_at'      => $claim->paid_at ? $claim->paid_at->format('d/m/Y H:i') : '-',
                'paid_by'      => $claim->payer->name ?? '-',
                'status'       => Claim::getStatusBadge($claim->status),
                'actions'      => '<a href="' . route('admin.claims.show', $claim->id) . '" class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></a>',
            ];
        });

        return response()->json($result);
    }

    // ══════════════════════════════════════════════
    // Export
    // ══════════════════════════════════════════════

    public function export(Request $request)
    {
        $this->authorize('export', Claim::class);

        $category = $request->input('category', 'all');
        $status   = $request->input('status');

        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\ClaimsManagementExport($category, $status),
            'claims_' . $category . '_' . now()->format('Ymd_His') . '.xlsx'
        );
    }

    // ══════════════════════════════════════════════
    // Helpers
    // ══════════════════════════════════════════════

    protected function getActionButtons(Claim $claim): string
    {
        $showUrl = route('admin.claims.show', $claim->id);
        return '<a href="' . $showUrl . '" class="btn btn-sm btn-outline-primary" title="View"><i class="bi bi-eye"></i></a>';
    }
}
