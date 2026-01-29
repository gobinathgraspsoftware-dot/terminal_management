<?php

namespace App\Http\Controllers\Technician;

use App\Http\Controllers\Controller;
use App\Models\Client;
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
     * Display a listing of active clients
     * Technicians only see basic client information
     */
    public function index(): View
    {
        $statistics = [
            'total' => Client::active()->count(),
        ];

        return view('technician.clients.index', compact('statistics'));
    }

    /**
     * DataTables server-side processing
     * Limited columns for technicians
     */
    public function datatable(Request $request): JsonResponse
    {
        // Technicians only see active clients
        $query = Client::active()
            ->with('partner:id,partner_name')
            ->select('id', 'client_code', 'client_name', 'pic_name', 'pic_phone', 'billing_city', 'billing_state', 'partner_id');

        return DataTables::of($query)
            ->addColumn('partner_name', function ($client) {
                return $client->partner ? $client->partner->partner_name : '-';
            })
            ->addColumn('pic_info', function ($client) {
                $html = '<strong>' . e($client->pic_name ?? 'N/A') . '</strong>';
                if ($client->pic_phone) {
                    $html .= '<br><small class="text-muted"><i class="bi bi-telephone"></i> ' . e($client->pic_phone) . '</small>';
                }
                return $html;
            })
            ->addColumn('location', function ($client) {
                $parts = array_filter([$client->billing_city, $client->billing_state]);
                return implode(', ', $parts) ?: '-';
            })
            ->addColumn('actions', function ($client) {
                return '<a href="' . route('technician.clients.show', $client->id) . '"
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
                            ->orWhere('pic_name', 'like', "%{$searchValue}%");
                    });
                }
            })
            ->rawColumns(['pic_info', 'actions'])
            ->make(true);
    }

    /**
     * Display the specified client (basic info only)
     */
    public function show(Client $client): View
    {
        // Only load basic information
        $client->load([
            'partner:id,partner_code,partner_name,pic_name,pic_phone',
            'contacts' => function ($query) {
                $query->active()
                      ->orderBy('is_primary', 'desc')
                      ->orderBy('contact_name');
            }
        ]);

        return view('technician.clients.show', compact('client'));
    }

    /**
     * Get clients list for dropdowns (AJAX)
     */
    public function getList(Request $request): JsonResponse
    {
        $search = $request->get('search');

        $query = Client::active()
            ->select('id', 'client_code', 'client_name');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('client_code', 'like', "%{$search}%")
                    ->orWhere('client_name', 'like', "%{$search}%");
            });
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
