<?php

namespace App\Http\Controllers\Technician;

use App\Http\Controllers\Controller;
use App\Models\StockIssue;
use App\Services\StockIssueService;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class StockIssueController extends Controller
{
    use AuthorizesRequests;

    protected $stockIssueService;

    public function __construct(StockIssueService $stockIssueService)
    {
        $this->stockIssueService = $stockIssueService;
    }

    /**
     * Display a listing of technician's stock issues (mobile-friendly)
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', StockIssue::class);

        if ($request->ajax()) {
            $query = $this->stockIssueService->getFilteredStockIssues(
                $request,
                'technician',
                auth()->id()
            );

            return datatables()->eloquent($query)
                ->addColumn('issue_type_badge', function ($issue) {
                    $type = $issue->issue_type === StockIssue::TYPE_ISSUE_TO_TECH ? 'Received' : 'Returned';
                    $class = $issue->issue_type === StockIssue::TYPE_ISSUE_TO_TECH ? 'success' : 'primary';
                    return "<span class='badge bg-{$class}'>{$type}</span>";
                })
                ->addColumn('location', function ($issue) {
                    if ($issue->issue_type === StockIssue::TYPE_ISSUE_TO_TECH) {
                        return '<i class="bi bi-building"></i> From: ' . ($issue->fromDepot->name ?? '-');
                    } else {
                        return '<i class="bi bi-building"></i> To: ' . ($issue->toDepot->name ?? '-');
                    }
                })
                ->addColumn('status_badge', function ($issue) {
                    $badges = [
                        'draft' => '<span class="badge bg-secondary">Draft</span>',
                        'posted' => '<span class="badge bg-success">Posted</span>',
                        'cancelled' => '<span class="badge bg-danger">Cancelled</span>',
                    ];
                    return $badges[$issue->status] ?? $issue->status;
                })
                ->addColumn('action', function ($issue) {
                    return '<a href="' . route('technician.stock-issues.show', $issue->id) . '" class="btn btn-sm btn-info w-100">
                                <i class="bi bi-eye"></i> View
                            </a>';
                })
                ->rawColumns(['issue_type_badge', 'location', 'status_badge', 'action'])
                ->make(true);
        }

        return view('technician.stock-issues.index');
    }

    /**
     * Display the specified stock issue (mobile-friendly)
     */
    public function show(StockIssue $stockIssue)
    {
        $this->authorize('view', $stockIssue);

        $stockIssue->load([
            'fromDepot',
            'toDepot',
            'toTechnician',
            'fromTechnician',
            'lines.model',
            'lines.serial'
        ]);

        return view('technician.stock-issues.show', compact('stockIssue'));
    }
}
