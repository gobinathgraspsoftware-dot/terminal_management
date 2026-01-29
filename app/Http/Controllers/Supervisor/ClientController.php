<?php

namespace App\Http\Controllers\Supervisor;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\ClientContact;
use App\Models\Partner;
use App\Services\ClientService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Yajra\DataTables\Facades\DataTables;

class ClientController extends Controller
{
    protected ClientService $clientService;

    public function __construct(ClientService $clientService)
    {
        $this->clientService = $clientService;
    }

    /**
     * Display a listing of clients
     * Supervisors see all active clients
     */
    public function index(): View
    {
        $statistics = $this->clientService->getStatistics();
        $partners = Partner::active()->orderBy('partner_name')->get();

        return view('supervisor.clients.index', compact('statistics', 'partners'));
    }

    /**
     * DataTables server-side processing
     */
    public function datatable(Request $request): JsonResponse
    {
        $query = Client::with(['partner'])
            ->withCount(['sites', 'jobOrders', 'contacts'])
            ->select('clients.*');

        return DataTables::of($query)
            ->addColumn('partner_name', function ($client) {
                return $client->partner ? $client->partner->partner_name : '-';
            })
            ->addColumn('status_badge', function ($client) {
                $badges = [
                    Client::STATUS_ACTIVE => '<span class="badge bg-success">Active</span>',
                    Client::STATUS_INACTIVE => '<span class="badge bg-secondary">Inactive</span>',
                    Client::STATUS_SUSPENDED => '<span class="badge bg-warning">Suspended</span>',
                ];

                return $badges[$client->status] ?? '<span class="badge bg-secondary">Unknown</span>';
            })
            ->addColumn('pic_info', function ($client) {
                $html = '<strong>' . e($client->pic_name ?? 'N/A') . '</strong>';
                if ($client->pic_email) {
                    $html .= '<br><small class="text-muted"><i class="bi bi-envelope"></i> ' . e($client->pic_email) . '</small>';
                }
                if ($client->pic_phone) {
                    $html .= '<br><small class="text-muted"><i class="bi bi-telephone"></i> ' . e($client->pic_phone) . '</small>';
                }
                return $html;
            })
            ->addColumn('address_info', function ($client) {
                $parts = array_filter([
                    $client->billing_city,
                    $client->billing_state
                ]);
                return implode(', ', $parts) ?: '-';
            })
            ->addColumn('financial_info', function ($client) {
                $html = '<strong>Credit Limit:</strong> ' . ($client->credit_limit ? 'RM ' . number_format($client->credit_limit, 2) : 'N/A');
                $html .= '<br><strong>Payment Terms:</strong> ' . $client->payment_terms . ' days';
                return $html;
            })
            ->addColumn('stats', function ($client) {
                return '<span class="badge bg-info">' . $client->sites_count . ' Sites</span> ' .
                       '<span class="badge bg-primary">' . $client->job_orders_count . ' Jobs</span> ' .
                       '<span class="badge bg-secondary">' . $client->contacts_count . ' Contacts</span>';
            })
            ->addColumn('actions', function ($client) {
                return '<a href="' . route('supervisor.clients.show', $client->id) . '"
                    class="btn btn-sm btn-info" title="View">
                    <i class="bi bi-eye"></i> View
                </a>';
            })
            ->filter(function ($query) use ($request) {
                // Search functionality
                if ($request->has('search') && $request->search['value']) {
                    $searchValue = $request->search['value'];
                    $query->where(function ($q) use ($searchValue) {
                        $q->where('client_code', 'like', "%{$searchValue}%")
                            ->orWhere('client_name', 'like', "%{$searchValue}%")
                            ->orWhere('company_name', 'like', "%{$searchValue}%")
                            ->orWhere('pic_name', 'like', "%{$searchValue}%")
                            ->orWhere('billing_city', 'like', "%{$searchValue}%")
                            ->orWhere('billing_state', 'like', "%{$searchValue}%");
                    });
                }

                // Partner filter
                if ($request->filled('partner_id')) {
                    $query->where('partner_id', $request->partner_id);
                }

                // Status filter
                if ($request->filled('status')) {
                    $query->where('status', $request->status);
                }

                // State filter
                if ($request->filled('state')) {
                    $query->where('billing_state', $request->state);
                }
            })
            ->rawColumns(['status_badge', 'pic_info', 'address_info', 'financial_info', 'stats', 'actions'])
            ->make(true);
    }

    /**
     * Display the specified client
     */
    public function show(Client $client): View
    {
        $client->load([
            'partner',
            'contacts' => function ($query) {
                $query->orderBy('is_primary', 'desc')
                      ->orderBy('contact_name');
            },
            'sites' => function ($query) {
                $query->orderBy('created_at', 'desc')->limit(10);
            },
            'jobOrders' => function ($query) {
                $query->orderBy('created_at', 'desc')->limit(10);
            },
            'invoices' => function ($query) {
                $query->orderBy('created_at', 'desc')->limit(10);
            }
        ]);

        // Get statistics
        $statistics = $this->clientService->getClientStatistics($client);

        // Get aging summary
        $agingSummary = $this->clientService->getAgingSummary($client);

        return view('supervisor.clients.show', compact('client', 'statistics', 'agingSummary'));
    }

    /**
     * Get clients list for dropdowns (AJAX)
     */
    public function getList(Request $request): JsonResponse
    {
        $search = $request->get('search');
        $partnerId = $request->get('partner_id');

        $query = Client::active()
            ->select('id', 'client_code', 'client_name', 'partner_id');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('client_code', 'like', "%{$search}%")
                    ->orWhere('client_name', 'like', "%{$search}%");
            });
        }

        if ($partnerId) {
            $query->where('partner_id', $partnerId);
        }

        $clients = $query->orderBy('client_name')->limit(50)->get();

        return response()->json([
            'success' => true,
            'clients' => $clients->map(function ($client) {
                return [
                    'id' => $client->id,
                    'text' => "[{$client->client_code}] {$client->client_name}"
                ];
            })
        ]);
    }
}
