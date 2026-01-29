<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreClientRequest;
use App\Http\Requests\Admin\UpdateClientRequest;
use App\Http\Requests\Admin\StoreClientContactRequest;
use App\Http\Requests\Admin\UpdateClientContactRequest;
use App\Models\Client;
use App\Models\ClientContact;
use App\Models\Partner;
use App\Models\Site;
use App\Models\JobOrder;
use App\Models\Invoice;
use App\Services\ClientService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
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
     */
    public function index(): View
    {
        $statistics = $this->clientService->getStatistics();
        $partners = Partner::active()->orderBy('partner_name')->get();

        return view('admin.clients.index', compact('statistics', 'partners'));
    }

    /**
     * DataTables server-side processing
     */
    public function datatable(Request $request): JsonResponse
    {
        $query = Client::with(['partner', 'createdBy', 'updatedBy'])
            ->withCount(['sites', 'jobOrders', 'contacts'])
            ->select('clients.*');

        // Include trashed if requested
        if ($request->get('show_trashed') === 'true') {
            $query->withTrashed();
        }

        return DataTables::of($query)
            ->addColumn('partner_name', function ($client) {
                return $client->partner ? $client->partner->partner_name : '-';
            })
            ->addColumn('status_badge', function ($client) {
                if ($client->trashed()) {
                    return '<span class="badge bg-danger">Deleted</span>';
                }

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
            ->addColumn('created_info', function ($client) {
                $html = $client->created_at ? $client->created_at->format('Y-m-d H:i') : '-';
                if ($client->createdBy) {
                    $html .= '<br><small class="text-muted">by ' . e($client->createdBy->name) . '</small>';
                }
                return $html;
            })
            ->addColumn('actions', function ($client) {
                $actions = '<div class="btn-group" role="group">';

                // View button
                $actions .= '<a href="' . route('admin.clients.show', $client->id) . '"
                    class="btn btn-sm btn-info" title="View">
                    <i class="bi bi-eye"></i>
                </a>';

                if (!$client->trashed()) {
                    // Edit button
                    if (Auth::user()->can('edit_clients')) {
                        $actions .= '<a href="' . route('admin.clients.edit', $client->id) . '"
                            class="btn btn-sm btn-primary" title="Edit">
                            <i class="bi bi-pencil"></i>
                        </a>';
                    }

                    // Status toggle button
                    if (Auth::user()->can('edit_clients')) {
                        $statusIcon = $client->status === Client::STATUS_ACTIVE ? 'bi-pause-circle' : 'bi-play-circle';
                        $statusTitle = $client->status === Client::STATUS_ACTIVE ? 'Deactivate' : 'Activate';
                        $actions .= '<button type="button" class="btn btn-sm btn-outline-secondary toggle-status"
                            data-id="' . $client->id . '" title="' . $statusTitle . '">
                            <i class="bi ' . $statusIcon . '"></i>
                        </button>';
                    }

                    // Delete button
                    if (Auth::user()->can('delete_clients')) {
                        $actions .= '<button type="button" class="btn btn-sm btn-danger delete-client"
                            data-id="' . $client->id . '" title="Delete">
                            <i class="bi bi-trash"></i>
                        </button>';
                    }
                } else {
                    // Restore button
                    if (Auth::user()->can('delete_clients')) {
                        $actions .= '<button type="button" class="btn btn-sm btn-success restore-client"
                            data-id="' . $client->id . '" title="Restore">
                            <i class="bi bi-arrow-counterclockwise"></i>
                        </button>';
                    }
                }

                $actions .= '</div>';
                return $actions;
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
            ->rawColumns(['status_badge', 'pic_info', 'address_info', 'financial_info', 'stats', 'created_info', 'actions'])
            ->make(true);
    }

    /**
     * Show the form for creating a new client
     */
    public function create(): View
    {
        $nextCode = Client::generateClientCode();
        $partners = Partner::active()->orderBy('partner_name')->get();
        $states = $this->getMalaysianStates();

        return view('admin.clients.create', compact('nextCode', 'partners', 'states'));
    }

    /**
     * Store a newly created client
     */
    public function store(StoreClientRequest $request): JsonResponse
    {
        try {
            DB::beginTransaction();

            $client = $this->clientService->createClient($request->validated());

            DB::commit();

            // Log activity
            activity()
                ->causedBy(Auth::user())
                ->performedOn($client)
                ->withProperties($request->validated())
                ->log('Client created');

            return response()->json([
                'success' => true,
                'message' => 'Client created successfully',
                'client' => $client,
                'redirect' => route('admin.clients.index')
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to create client: ' . $e->getMessage()
            ], 500);
        }
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
            },
            'createdBy',
            'updatedBy'
        ]);

        // Get statistics
        $statistics = $this->clientService->getClientStatistics($client);

        // Get aging summary
        $agingSummary = $this->clientService->getAgingSummary($client);

        return view('admin.clients.show', compact('client', 'statistics', 'agingSummary'));
    }

    /**
     * Show the form for editing the specified client
     */
    public function edit(Client $client): View
    {
        $partners = Partner::active()->orderBy('partner_name')->get();
        $states = $this->getMalaysianStates();

        return view('admin.clients.edit', compact('client', 'partners', 'states'));
    }

    /**
     * Update the specified client
     */
    public function update(UpdateClientRequest $request, Client $client): JsonResponse
    {
        try {
            DB::beginTransaction();

            $client = $this->clientService->updateClient($client, $request->validated());

            DB::commit();

            // Log activity
            activity()
                ->causedBy(Auth::user())
                ->performedOn($client)
                ->withProperties($request->validated())
                ->log('Client updated');

            return response()->json([
                'success' => true,
                'message' => 'Client updated successfully',
                'client' => $client,
                'redirect' => route('admin.clients.index')
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to update client: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified client (soft delete)
     */
    public function destroy(Client $client): JsonResponse
    {
        try {
            // Check if client has active sites or jobs
            if ($client->sites()->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete client with existing sites. Please reassign or delete sites first.'
                ], 422);
            }

            if ($client->jobOrders()->whereNotIn('status', ['completed', 'cancelled'])->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete client with active job orders.'
                ], 422);
            }

            if ($client->invoices()->where('status', '!=', 'paid')->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete client with unpaid invoices.'
                ], 422);
            }

            $client->delete();

            // Log activity
            activity()
                ->causedBy(Auth::user())
                ->performedOn($client)
                ->log('Client deleted');

            return response()->json([
                'success' => true,
                'message' => 'Client deleted successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete client: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Restore a soft-deleted client
     */
    public function restore(int $id): JsonResponse
    {
        try {
            $client = Client::withTrashed()->findOrFail($id);
            $client->restore();

            // Log activity
            activity()
                ->causedBy(Auth::user())
                ->performedOn($client)
                ->log('Client restored');

            return response()->json([
                'success' => true,
                'message' => 'Client restored successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to restore client: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Toggle client status
     */
    public function toggleStatus(Client $client): JsonResponse
    {
        try {
            $newStatus = $client->status === Client::STATUS_ACTIVE
                ? Client::STATUS_INACTIVE
                : Client::STATUS_ACTIVE;

            $client->update([
                'status' => $newStatus,
                'updated_by' => Auth::id()
            ]);

            // Log activity
            activity()
                ->causedBy(Auth::user())
                ->performedOn($client)
                ->withProperties(['old_status' => $client->status, 'new_status' => $newStatus])
                ->log('Client status changed');

            return response()->json([
                'success' => true,
                'message' => 'Client status updated successfully',
                'status' => $newStatus
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update client status: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Add a new contact to client
     */
    public function addContact(StoreClientContactRequest $request, Client $client): JsonResponse
    {
        try {
            DB::beginTransaction();

            $contact = $this->clientService->addContact($client, $request->validated());

            DB::commit();

            // Log activity
            activity()
                ->causedBy(Auth::user())
                ->performedOn($client)
                ->withProperties($request->validated())
                ->log('Client contact added');

            return response()->json([
                'success' => true,
                'message' => 'Contact added successfully',
                'contact' => $contact
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to add contact: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update a client contact
     */
    public function updateContact(UpdateClientContactRequest $request, Client $client, ClientContact $contact): JsonResponse
    {
        try {
            // Verify contact belongs to client
            if ($contact->client_id !== $client->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Contact does not belong to this client'
                ], 403);
            }

            DB::beginTransaction();

            $contact = $this->clientService->updateContact($contact, $request->validated());

            DB::commit();

            // Log activity
            activity()
                ->causedBy(Auth::user())
                ->performedOn($client)
                ->withProperties($request->validated())
                ->log('Client contact updated');

            return response()->json([
                'success' => true,
                'message' => 'Contact updated successfully',
                'contact' => $contact
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to update contact: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove a contact from client
     */
    public function removeContact(Client $client, ClientContact $contact): JsonResponse
    {
        try {
            // Verify contact belongs to client
            if ($contact->client_id !== $client->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Contact does not belong to this client'
                ], 403);
            }

            // Cannot delete primary contact if there are other contacts
            if ($contact->is_primary && $client->contacts()->count() > 1) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete primary contact. Please set another contact as primary first.'
                ], 422);
            }

            $contact->delete();

            // Log activity
            activity()
                ->causedBy(Auth::user())
                ->performedOn($client)
                ->withProperties(['contact_id' => $contact->id, 'contact_name' => $contact->contact_name])
                ->log('Client contact removed');

            return response()->json([
                'success' => true,
                'message' => 'Contact removed successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to remove contact: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Set a contact as primary
     */
    public function setPrimaryContact(Client $client, ClientContact $contact): JsonResponse
    {
        try {
            // Verify contact belongs to client
            if ($contact->client_id !== $client->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Contact does not belong to this client'
                ], 403);
            }

            DB::beginTransaction();

            $this->clientService->setPrimaryContact($client, $contact);

            DB::commit();

            // Log activity
            activity()
                ->causedBy(Auth::user())
                ->performedOn($client)
                ->withProperties(['contact_id' => $contact->id, 'contact_name' => $contact->contact_name])
                ->log('Primary contact changed');

            return response()->json([
                'success' => true,
                'message' => 'Primary contact set successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to set primary contact: ' . $e->getMessage()
            ], 500);
        }
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

    /**
     * Get Malaysian states
     */
    protected function getMalaysianStates(): array
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
