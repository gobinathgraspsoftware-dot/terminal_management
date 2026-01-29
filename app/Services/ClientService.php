<?php

namespace App\Services;

use App\Models\Client;
use App\Models\ClientContact;
use App\Models\Invoice;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ClientService
{
    /**
     * Create a new client
     */
    public function createClient(array $data): Client
    {
        // Add created_by
        $data['created_by'] = Auth::id();
        $data['updated_by'] = Auth::id();

        return Client::create($data);
    }

    /**
     * Update an existing client
     */
    public function updateClient(Client $client, array $data): Client
    {
        // Add updated_by
        $data['updated_by'] = Auth::id();

        // Don't update client_code
        unset($data['client_code']);

        $client->update($data);
        
        return $client->fresh();
    }

    /**
     * Get overall client statistics
     */
    public function getStatistics(): array
    {
        return [
            'total' => Client::count(),
            'active' => Client::where('status', Client::STATUS_ACTIVE)->count(),
            'inactive' => Client::where('status', Client::STATUS_INACTIVE)->count(),
            'suspended' => Client::where('status', Client::STATUS_SUSPENDED)->count(),
            'with_outstanding' => $this->getClientsWithOutstanding(),
        ];
    }

    /**
     * Get statistics for a specific client
     */
    public function getClientStatistics(Client $client): array
    {
        return [
            'total_sites' => $client->sites()->count(),
            'active_sites' => $client->sites()->where('status', 'active')->count(),
            'total_jobs' => $client->jobOrders()->count(),
            'pending_jobs' => $client->jobOrders()->where('status', 'pending')->count(),
            'completed_jobs' => $client->jobOrders()->where('status', 'completed')->count(),
            'total_invoices' => $client->invoices()->count(),
            'paid_invoices' => $client->invoices()->where('status', 'paid')->count(),
            'unpaid_invoices' => $client->invoices()->where('status', '!=', 'paid')->count(),
            'total_outstanding' => $this->calculateTotalOutstanding($client),
            'total_contacts' => $client->contacts()->count(),
            'active_contacts' => $client->contacts()->where('status', 'active')->count(),
        ];
    }

    /**
     * Calculate total outstanding amount for a client
     */
    public function calculateTotalOutstanding(Client $client): float
    {
        return $client->invoices()
            ->whereIn('status', ['pending', 'partial'])
            ->sum(DB::raw('total_amount - paid_amount'));
    }

    /**
     * Get aging summary for a client
     */
    public function getAgingSummary(Client $client): array
    {
        $today = Carbon::today();
        
        $invoices = $client->invoices()
            ->whereIn('status', ['pending', 'partial'])
            ->select('id', 'invoice_date', 'due_date', 'total_amount', 'paid_amount')
            ->get();

        $aging = [
            'current' => 0,      // Not yet due
            '1-30' => 0,         // 1-30 days overdue
            '31-60' => 0,        // 31-60 days overdue
            '61-90' => 0,        // 61-90 days overdue
            'over_90' => 0,      // Over 90 days overdue
            'total' => 0,
        ];

        foreach ($invoices as $invoice) {
            $outstanding = $invoice->total_amount - $invoice->paid_amount;
            
            if ($outstanding <= 0) {
                continue;
            }

            $aging['total'] += $outstanding;

            if (!$invoice->due_date) {
                $aging['current'] += $outstanding;
                continue;
            }

            $dueDate = Carbon::parse($invoice->due_date);
            $daysOverdue = $today->diffInDays($dueDate, false);

            if ($daysOverdue >= 0) {
                // Not yet due
                $aging['current'] += $outstanding;
            } elseif ($daysOverdue >= -30) {
                // 1-30 days overdue
                $aging['1-30'] += $outstanding;
            } elseif ($daysOverdue >= -60) {
                // 31-60 days overdue
                $aging['31-60'] += $outstanding;
            } elseif ($daysOverdue >= -90) {
                // 61-90 days overdue
                $aging['61-90'] += $outstanding;
            } else {
                // Over 90 days overdue
                $aging['over_90'] += $outstanding;
            }
        }

        return $aging;
    }

    /**
     * Get number of clients with outstanding balances
     */
    protected function getClientsWithOutstanding(): int
    {
        return Client::whereHas('invoices', function ($query) {
            $query->whereIn('status', ['pending', 'partial'])
                  ->whereRaw('total_amount > paid_amount');
        })->count();
    }

    /**
     * Add a new contact to client
     */
    public function addContact(Client $client, array $data): ClientContact
    {
        // If this is the first contact or marked as primary, handle primary status
        if ($data['is_primary'] || $client->contacts()->count() === 0) {
            // Remove primary status from other contacts
            $client->contacts()->update(['is_primary' => false]);
            $data['is_primary'] = true;
        }

        $data['client_id'] = $client->id;

        return ClientContact::create($data);
    }

    /**
     * Update a client contact
     */
    public function updateContact(ClientContact $contact, array $data): ClientContact
    {
        // If marking as primary, remove primary status from other contacts
        if (isset($data['is_primary']) && $data['is_primary']) {
            $contact->client->contacts()
                ->where('id', '!=', $contact->id)
                ->update(['is_primary' => false]);
        }

        $contact->update($data);
        
        return $contact->fresh();
    }

    /**
     * Set a contact as primary
     */
    public function setPrimaryContact(Client $client, ClientContact $contact): void
    {
        // Remove primary status from all contacts
        $client->contacts()->update(['is_primary' => false]);

        // Set the specified contact as primary
        $contact->update(['is_primary' => true]);
    }

    /**
     * Get clients by partner
     */
    public function getClientsByPartner(int $partnerId, bool $activeOnly = true): array
    {
        $query = Client::where('partner_id', $partnerId)
            ->select('id', 'client_code', 'client_name');

        if ($activeOnly) {
            $query->where('status', Client::STATUS_ACTIVE);
        }

        return $query->orderBy('client_name')->get()->toArray();
    }

    /**
     * Check if client has exceeded credit limit
     */
    public function hasCreditLimitExceeded(Client $client): bool
    {
        if (!$client->credit_limit) {
            return false;
        }

        $totalOutstanding = $this->calculateTotalOutstanding($client);

        return $totalOutstanding > $client->credit_limit;
    }

    /**
     * Get credit limit status
     */
    public function getCreditLimitStatus(Client $client): array
    {
        $totalOutstanding = $this->calculateTotalOutstanding($client);
        $creditLimit = $client->credit_limit ?? 0;

        $utilized = $creditLimit > 0 ? ($totalOutstanding / $creditLimit) * 100 : 0;

        return [
            'credit_limit' => $creditLimit,
            'outstanding' => $totalOutstanding,
            'available' => max(0, $creditLimit - $totalOutstanding),
            'utilized_percentage' => round($utilized, 2),
            'is_exceeded' => $totalOutstanding > $creditLimit,
            'status' => $this->determineCreditStatus($utilized),
        ];
    }

    /**
     * Determine credit status based on utilization percentage
     */
    protected function determineCreditStatus(float $utilizedPercentage): string
    {
        if ($utilizedPercentage >= 100) {
            return 'exceeded';
        } elseif ($utilizedPercentage >= 90) {
            return 'critical';
        } elseif ($utilizedPercentage >= 75) {
            return 'warning';
        } else {
            return 'healthy';
        }
    }

    /**
     * Get clients for dropdown (Select2 compatible)
     */
    public function getClientsForDropdown(bool $activeOnly = true, ?int $partnerId = null): array
    {
        $query = Client::select('id', 'client_code', 'client_name', 'partner_id');

        if ($activeOnly) {
            $query->where('status', Client::STATUS_ACTIVE);
        }

        if ($partnerId) {
            $query->where('partner_id', $partnerId);
        }

        return $query->orderBy('client_name')
            ->get()
            ->map(function ($client) {
                return [
                    'id' => $client->id,
                    'text' => "[{$client->client_code}] {$client->client_name}"
                ];
            })
            ->toArray();
    }

    /**
     * Get overdue invoices summary for a client
     */
    public function getOverdueInvoicesSummary(Client $client): array
    {
        $today = Carbon::today();

        $overdueInvoices = $client->invoices()
            ->whereIn('status', ['pending', 'partial'])
            ->whereNotNull('due_date')
            ->where('due_date', '<', $today)
            ->select('id', 'invoice_no', 'invoice_date', 'due_date', 'total_amount', 'paid_amount')
            ->orderBy('due_date')
            ->get();

        $totalOverdue = $overdueInvoices->sum(function ($invoice) {
            return $invoice->total_amount - $invoice->paid_amount;
        });

        return [
            'count' => $overdueInvoices->count(),
            'total_amount' => $totalOverdue,
            'invoices' => $overdueInvoices,
        ];
    }
}
