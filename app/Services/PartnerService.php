<?php

namespace App\Services;

use App\Models\Partner;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class PartnerService
{
    /**
     * Create a new partner
     */
    public function createPartner(array $data): Partner
    {
        // Add created_by
        $data['created_by'] = Auth::id();
        $data['updated_by'] = Auth::id();

        // Generate API key if job intake method is API
        if (isset($data['job_intake_method']) && $data['job_intake_method'] === Partner::JOB_INTAKE_API) {
            if (empty($data['api_key'])) {
                $data['api_key'] = $this->generateApiKey();
            }
        }

        // Process SLA rules
        if (isset($data['sla_rules']) && is_array($data['sla_rules'])) {
            $data['sla_rules'] = $this->processSlaRules($data['sla_rules']);
        }

        return Partner::create($data);
    }

    /**
     * Update an existing partner
     */
    public function updatePartner(Partner $partner, array $data): Partner
    {
        // Add updated_by
        $data['updated_by'] = Auth::id();

        // Generate API key if job intake method is API and no key exists
        if (isset($data['job_intake_method']) && $data['job_intake_method'] === Partner::JOB_INTAKE_API) {
            if (empty($data['api_key']) && empty($partner->api_key)) {
                $data['api_key'] = $this->generateApiKey();
            }
        }

        // Clear API key if not using API method
        if (isset($data['job_intake_method']) && $data['job_intake_method'] !== Partner::JOB_INTAKE_API) {
            $data['api_key'] = null;
        }

        // Process SLA rules
        if (isset($data['sla_rules']) && is_array($data['sla_rules'])) {
            $data['sla_rules'] = $this->processSlaRules($data['sla_rules']);
        }

        // Don't update partner_code
        unset($data['partner_code']);

        $partner->update($data);
        
        return $partner->fresh();
    }

    /**
     * Process and validate SLA rules
     */
    private function processSlaRules(array $rules): array
    {
        $processedRules = [];

        foreach ($rules as $rule) {
            // Skip empty rules
            if (empty($rule['sla_type'])) {
                continue;
            }

            $processedRules[] = [
                'sla_type' => $rule['sla_type'] ?? '',
                'priority' => $rule['priority'] ?? 'medium',
                'response_hours' => (int) ($rule['response_hours'] ?? 24),
                'resolution_hours' => (int) ($rule['resolution_hours'] ?? 48),
                'escalation_enabled' => filter_var($rule['escalation_enabled'] ?? false, FILTER_VALIDATE_BOOLEAN),
                'escalation_hours' => isset($rule['escalation_hours']) ? (int) $rule['escalation_hours'] : null,
            ];
        }

        return $processedRules;
    }

    /**
     * Generate a unique API key
     */
    private function generateApiKey(): string
    {
        do {
            $key = 'ptn_' . Str::random(32);
        } while (Partner::where('api_key', $key)->exists());

        return $key;
    }

    /**
     * Get overall partner statistics
     */
    public function getStatistics(): array
    {
        return [
            'total' => Partner::count(),
            'active' => Partner::active()->count(),
            'inactive' => Partner::inactive()->count(),
            'deleted' => Partner::onlyTrashed()->count(),
            'by_intake_method' => [
                'manual' => Partner::where('job_intake_method', Partner::JOB_INTAKE_MANUAL)->count(),
                'import' => Partner::where('job_intake_method', Partner::JOB_INTAKE_IMPORT)->count(),
                'api' => Partner::where('job_intake_method', Partner::JOB_INTAKE_API)->count(),
            ],
            'with_clients' => Partner::has('clients')->count(),
            'with_jobs' => Partner::has('jobOrders')->count(),
        ];
    }

    /**
     * Get statistics for a specific partner
     */
    public function getPartnerStatistics(Partner $partner): array
    {
        return [
            'total_clients' => $partner->clients()->count(),
            'active_clients' => $partner->clients()->where('status', 'active')->count(),
            'total_jobs' => $partner->jobOrders()->count(),
            'pending_jobs' => $partner->jobOrders()->whereIn('status', ['pending', 'assigned', 'in_progress'])->count(),
            'completed_jobs' => $partner->jobOrders()->where('status', 'completed')->count(),
            'cancelled_jobs' => $partner->jobOrders()->where('status', 'cancelled')->count(),
            'total_invoices' => $partner->invoices()->count(),
            'pending_invoices' => $partner->invoices()->where('status', 'pending')->count(),
            'total_revenue' => $partner->invoices()->where('status', 'paid')->sum('total_amount'),
            'outstanding_amount' => $partner->invoices()->where('status', 'pending')->sum('total_amount'),
            'sla_rules_count' => count($partner->sla_rules ?? []),
        ];
    }

    /**
     * Get SLA rule by type for a partner
     */
    public function getSlaRule(Partner $partner, string $slaType, string $priority = 'medium'): ?array
    {
        $rules = $partner->sla_rules ?? [];

        foreach ($rules as $rule) {
            if ($rule['sla_type'] === $slaType && $rule['priority'] === $priority) {
                return $rule;
            }
        }

        // Return default SLA if no specific rule found
        return [
            'sla_type' => $slaType,
            'priority' => $priority,
            'response_hours' => 24,
            'resolution_hours' => 48,
            'escalation_enabled' => false,
            'escalation_hours' => null,
        ];
    }

    /**
     * Check if a partner's SLA is breached
     */
    public function isSlaBreached(Partner $partner, string $slaType, string $priority, int $elapsedHours): bool
    {
        $rule = $this->getSlaRule($partner, $slaType, $priority);
        
        return $elapsedHours > $rule['resolution_hours'];
    }

    /**
     * Check if escalation is required
     */
    public function isEscalationRequired(Partner $partner, string $slaType, string $priority, int $elapsedHours): bool
    {
        $rule = $this->getSlaRule($partner, $slaType, $priority);
        
        if (!$rule['escalation_enabled'] || empty($rule['escalation_hours'])) {
            return false;
        }

        return $elapsedHours >= $rule['escalation_hours'];
    }

    /**
     * Regenerate API key for a partner
     */
    public function regenerateApiKey(Partner $partner): string
    {
        $newKey = $this->generateApiKey();
        
        $partner->update([
            'api_key' => $newKey,
            'updated_by' => Auth::id()
        ]);

        return $newKey;
    }

    /**
     * Validate API key
     */
    public function validateApiKey(string $apiKey): ?Partner
    {
        return Partner::where('api_key', $apiKey)
            ->where('status', Partner::STATUS_ACTIVE)
            ->where('job_intake_method', Partner::JOB_INTAKE_API)
            ->first();
    }

    /**
     * Get partners for dropdown
     */
    public function getPartnersForDropdown(bool $activeOnly = true): array
    {
        $query = Partner::select('id', 'partner_code', 'partner_name')
            ->orderBy('partner_name');

        if ($activeOnly) {
            $query->active();
        }

        return $query->get()
            ->map(function ($partner) {
                return [
                    'id' => $partner->id,
                    'text' => "[{$partner->partner_code}] {$partner->partner_name}"
                ];
            })
            ->toArray();
    }

    /**
     * Search partners
     */
    public function searchPartners(string $term, int $limit = 50): array
    {
        return Partner::active()
            ->where(function ($query) use ($term) {
                $query->where('partner_code', 'like', "%{$term}%")
                    ->orWhere('partner_name', 'like', "%{$term}%")
                    ->orWhere('pic_name', 'like', "%{$term}%");
            })
            ->select('id', 'partner_code', 'partner_name', 'pic_name')
            ->orderBy('partner_name')
            ->limit($limit)
            ->get()
            ->toArray();
    }
}
