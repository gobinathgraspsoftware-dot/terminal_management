<?php

namespace App\Services;

use App\Models\Quotation;
use App\Models\QuotationLine;
// PurchaseOrder/PurchaseOrderLine imports REMOVED (Procurement tabs removed)
use App\Models\JobOrder;
use App\Models\NumberSeries;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class QuotationService
{
    /**
     * Create a new quotation.
     */
    public function createQuotation(array $data): Quotation
    {
        return DB::transaction(function () use ($data) {
            // Generate quotation number
            $quotationNo = NumberSeries::getNextNumber('quotation');

            // Create quotation header
            $quotation = Quotation::create([
                'quotation_no' => $quotationNo,
                'quotation_date' => $data['quotation_date'] ?? now(),
                'quotation_type' => $data['quotation_type'],
                'client_id' => $data['client_id'] ?? null,
                'vendor_id' => $data['vendor_id'] ?? null,
                'reference' => $data['reference'] ?? null,
                'valid_until' => $data['valid_until'] ?? now()->addDays(30),
                'currency' => $data['currency'] ?? 'MYR',
                'terms_conditions' => $data['terms_conditions'] ?? null,
                'notes' => $data['notes'] ?? null,
                'discount_amount' => $data['discount_amount'] ?? 0,
                'status' => Quotation::STATUS_DRAFT,
                'created_by' => Auth::id(),
            ]);

            // Create quotation lines
            if (isset($data['lines']) && is_array($data['lines'])) {
                foreach ($data['lines'] as $index => $lineData) {
                    $this->createQuotationLine($quotation, $lineData, $index + 1);
                }
            }

            // Calculate and update totals
            $this->calculateAndUpdateTotals($quotation);

            return $quotation->fresh(['lines', 'client', 'vendor']);
        });
    }

    /**
     * Update an existing quotation.
     */
    public function updateQuotation(Quotation $quotation, array $data): Quotation
    {
        return DB::transaction(function () use ($quotation, $data) {
            // Update quotation header
            $quotation->update([
                'quotation_date' => $data['quotation_date'] ?? $quotation->quotation_date,
                'quotation_type' => $data['quotation_type'] ?? $quotation->quotation_type,
                'client_id' => $data['client_id'] ?? $quotation->client_id,
                'vendor_id' => $data['vendor_id'] ?? $quotation->vendor_id,
                'reference' => $data['reference'] ?? $quotation->reference,
                'valid_until' => $data['valid_until'] ?? $quotation->valid_until,
                'currency' => $data['currency'] ?? $quotation->currency,
                'terms_conditions' => $data['terms_conditions'] ?? $quotation->terms_conditions,
                'notes' => $data['notes'] ?? $quotation->notes,
                'discount_amount' => $data['discount_amount'] ?? $quotation->discount_amount,
                'updated_by' => Auth::id(),
            ]);

            // Delete existing lines and recreate
            $quotation->lines()->delete();

            if (isset($data['lines']) && is_array($data['lines'])) {
                foreach ($data['lines'] as $index => $lineData) {
                    $this->createQuotationLine($quotation, $lineData, $index + 1);
                }
            }

            // Recalculate totals
            $this->calculateAndUpdateTotals($quotation);

            return $quotation->fresh(['lines', 'client', 'vendor']);
        });
    }

    /**
     * Create a quotation line.
     */
    protected function createQuotationLine(Quotation $quotation, array $data, int $lineNo): QuotationLine
    {
        return QuotationLine::create([
            'quotation_id' => $quotation->id,
            'line_no' => $lineNo,
            'item_type' => $data['item_type'],
            'model_id' => $data['model_id'] ?? null,
            'charge_id' => $data['charge_id'] ?? null,
            'description' => $data['description'] ?? '',
            'quantity' => $data['quantity'] ?? 1,
            'unit' => $data['unit'] ?? 'pcs',
            'unit_price' => $data['unit_price'] ?? 0,
            'discount_percent' => $data['discount_percent'] ?? 0,
            'discount_amount' => $data['discount_amount'] ?? 0,
            'tax_rate' => $data['tax_rate'] ?? 0,
            'remarks' => $data['remarks'] ?? null,
        ]);
    }

    /**
     * Calculate and update quotation totals.
     */
    public function calculateAndUpdateTotals(Quotation $quotation): void
    {
        $quotation->calculateTotals();
        $quotation->save();
    }

    /**
     * Submit quotation for approval.
     */
    public function submitForApproval(Quotation $quotation): Quotation
    {
        if (!$quotation->isDraft()) {
            throw new \Exception('Only draft quotations can be submitted for approval.');
        }

        $quotation->update([
            'status' => Quotation::STATUS_PENDING_APPROVAL,
            'updated_by' => Auth::id(),
        ]);

        return $quotation->fresh();
    }

    /**
     * Approve quotation.
     */
    public function approveQuotation(Quotation $quotation): Quotation
    {
        if (!$quotation->canBeApproved()) {
            throw new \Exception('This quotation cannot be approved.');
        }

        $quotation->update([
            'status' => Quotation::STATUS_APPROVED,
            'approved_at' => now(),
            'approved_by' => Auth::id(),
        ]);

        return $quotation->fresh();
    }

    /**
     * Reject quotation.
     */
    public function rejectQuotation(Quotation $quotation, ?string $reason = null): Quotation
    {
        if (!$quotation->canBeApproved()) {
            throw new \Exception('This quotation cannot be rejected.');
        }

        $quotation->update([
            'status' => Quotation::STATUS_REJECTED,
            'notes' => ($quotation->notes ? $quotation->notes . "\n\n" : '') . 
                      "Rejected: " . ($reason ?? 'No reason provided'),
            'updated_by' => Auth::id(),
        ]);

        return $quotation->fresh();
    }

    /**
     * Send quotation to client/vendor.
     */
    public function sendQuotation(Quotation $quotation): Quotation
    {
        if (!$quotation->canBeSent()) {
            throw new \Exception('This quotation cannot be sent. It must be approved first.');
        }

        $quotation->update([
            'status' => Quotation::STATUS_SENT,
            'sent_at' => now(),
            'updated_by' => Auth::id(),
        ]);

        // TODO: Send email notification to client/vendor

        return $quotation->fresh();
    }

    /**
     * Accept quotation (for customer quotations).
     */
    public function acceptQuotation(Quotation $quotation): Quotation
    {
        if (!$quotation->isSent()) {
            throw new \Exception('Only sent quotations can be accepted.');
        }

        $quotation->update([
            'status' => Quotation::STATUS_ACCEPTED,
            'updated_by' => Auth::id(),
        ]);

        return $quotation->fresh();
    }

    /**
     * Cancel quotation.
     */
    public function cancelQuotation(Quotation $quotation): Quotation
    {
        if ($quotation->isConvertedToPO()) {
            throw new \Exception('Cannot cancel quotation that has been converted to Purchase Order.');
        }

        $quotation->update([
            'status' => Quotation::STATUS_CANCELLED,
            'updated_by' => Auth::id(),
        ]);

        return $quotation->fresh();
    }

    // convertToPurchaseOrder() method REMOVED (Procurement tabs removed)

    /**
     * Create quotation from completed jobs.
     */
    public function createFromJobs(array $jobIds, array $additionalData = []): Quotation
    {
        return DB::transaction(function () use ($jobIds, $additionalData) {
            $jobs = JobOrder::with(['client', 'site', 'devices'])
                ->whereIn('id', $jobIds)
                ->where('job_status', 'completed')
                ->get();

            if ($jobs->isEmpty()) {
                throw new \Exception('No completed jobs found.');
            }

            // Get client from first job (assuming all jobs are for same client)
            $client = $jobs->first()->client;

            // Generate quotation number
            $quotationNo = NumberSeries::getNextNumber('quotation');

            // Create quotation
            $quotation = Quotation::create([
                'quotation_no' => $quotationNo,
                'quotation_date' => now(),
                'quotation_type' => Quotation::TYPE_CUSTOMER,
                'client_id' => $client->id,
                'reference' => 'Generated from Jobs: ' . implode(', ', $jobIds),
                'valid_until' => now()->addDays(30),
                'currency' => $additionalData['currency'] ?? 'MYR',
                'terms_conditions' => $additionalData['terms_conditions'] ?? null,
                'notes' => $additionalData['notes'] ?? 'Auto-generated from completed jobs',
                'status' => Quotation::STATUS_DRAFT,
                'created_by' => Auth::id(),
            ]);

            // Create lines from jobs
            $lineNo = 1;
            foreach ($jobs as $job) {
                // Add job service charge
                QuotationLine::create([
                    'quotation_id' => $quotation->id,
                    'line_no' => $lineNo++,
                    'item_type' => QuotationLine::ITEM_TYPE_CUSTOM,
                    'description' => "Service: {$job->job_type} - {$job->site->site_name}",
                    'quantity' => 1,
                    'unit' => 'service',
                    'unit_price' => $job->estimated_amount ?? 0,
                    'discount_percent' => 0,
                    'tax_rate' => 6, // Default SST rate
                ]);

                // Add devices if any
                foreach ($job->devices as $device) {
                    if ($device->model_id) {
                        QuotationLine::create([
                            'quotation_id' => $quotation->id,
                            'line_no' => $lineNo++,
                            'item_type' => QuotationLine::ITEM_TYPE_MODEL,
                            'model_id' => $device->model_id,
                            'description' => "Device: " . ($device->model->model_name ?? ''),
                            'quantity' => 1,
                            'unit' => 'pcs',
                            'unit_price' => $device->model->selling_price ?? 0,
                            'discount_percent' => 0,
                            'tax_rate' => 6,
                        ]);
                    }
                }
            }

            // Calculate totals
            $this->calculateAndUpdateTotals($quotation);

            return $quotation->fresh(['lines', 'client']);
        });
    }

    /**
     * Duplicate quotation.
     */
    public function duplicateQuotation(Quotation $quotation): Quotation
    {
        return DB::transaction(function () use ($quotation) {
            // Generate new quotation number
            $quotationNo = NumberSeries::getNextNumber('quotation');

            // Create new quotation
            $newQuotation = Quotation::create([
                'quotation_no' => $quotationNo,
                'quotation_date' => now(),
                'quotation_type' => $quotation->quotation_type,
                'client_id' => $quotation->client_id,
                'vendor_id' => $quotation->vendor_id,
                'reference' => 'Copy of ' . $quotation->quotation_no,
                'valid_until' => now()->addDays(30),
                'currency' => $quotation->currency,
                'terms_conditions' => $quotation->terms_conditions,
                'notes' => $quotation->notes,
                'discount_amount' => $quotation->discount_amount,
                'status' => Quotation::STATUS_DRAFT,
                'created_by' => Auth::id(),
            ]);

            // Copy lines
            foreach ($quotation->lines as $line) {
                QuotationLine::create([
                    'quotation_id' => $newQuotation->id,
                    'line_no' => $line->line_no,
                    'item_type' => $line->item_type,
                    'model_id' => $line->model_id,
                    'charge_id' => $line->charge_id,
                    'description' => $line->description,
                    'quantity' => $line->quantity,
                    'unit' => $line->unit,
                    'unit_price' => $line->unit_price,
                    'discount_percent' => $line->discount_percent,
                    'discount_amount' => $line->discount_amount,
                    'tax_rate' => $line->tax_rate,
                    'remarks' => $line->remarks,
                ]);
            }

            // Calculate totals
            $this->calculateAndUpdateTotals($newQuotation);

            return $newQuotation->fresh(['lines', 'client', 'vendor']);
        });
    }

    /**
     * Check and expire old quotations.
     */
    public function checkAndExpireQuotations(): int
    {
        $quotations = Quotation::where('valid_until', '<', now())
            ->whereIn('status', [
                Quotation::STATUS_SENT,
                Quotation::STATUS_APPROVED
            ])
            ->get();

        $count = 0;
        foreach ($quotations as $quotation) {
            $quotation->update(['status' => Quotation::STATUS_EXPIRED]);
            $count++;
        }

        return $count;
    }
}
