<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── Update tickets table with new fields ──
        Schema::table('tickets', function (Blueprint $table) {
            // New fields from document
            if (!Schema::hasColumn('tickets', 'vendor_ticket_ref_no')) {
                $table->string('vendor_ticket_ref_no')->nullable()->after('ticket_no');
            }
            if (!Schema::hasColumn('tickets', 'tid')) {
                $table->string('tid')->nullable()->after('city_id')->comment('Terminal ID');
            }
            if (!Schema::hasColumn('tickets', 'merchant_name')) {
                $table->string('merchant_name')->nullable()->after('tid');
            }
            if (!Schema::hasColumn('tickets', 'merchant_address')) {
                $table->text('merchant_address')->nullable()->after('merchant_name');
            }
            if (!Schema::hasColumn('tickets', 'contact_number')) {
                $table->string('contact_number', 50)->nullable()->after('merchant_address');
            }
            if (!Schema::hasColumn('tickets', 'charge_id')) {
                $table->foreignId('charge_id')->nullable()->after('job_type_id')
                      ->constrained('charge_catalog')->nullOnDelete();
            }

            // Claim fields inside ticket
            if (!Schema::hasColumn('tickets', 'mileage')) {
                $table->decimal('mileage', 10, 2)->nullable()->default(0)->after('description');
            }
            if (!Schema::hasColumn('tickets', 'mileage_remarks')) {
                $table->string('mileage_remarks', 500)->nullable()->after('mileage');
            }
            if (!Schema::hasColumn('tickets', 'mileage_rate')) {
                $table->decimal('mileage_rate', 8, 2)->nullable()->default(0)->after('mileage_remarks');
            }
            if (!Schema::hasColumn('tickets', 'mileage_amount')) {
                $table->decimal('mileage_amount', 10, 2)->nullable()->default(0)->after('mileage_rate');
            }
            if (!Schema::hasColumn('tickets', 'toll')) {
                $table->decimal('toll', 10, 2)->nullable()->default(0)->after('mileage_amount');
            }
            if (!Schema::hasColumn('tickets', 'standby_meal')) {
                $table->decimal('standby_meal', 10, 2)->nullable()->default(0)->after('toll');
            }
            if (!Schema::hasColumn('tickets', 'total_claim_amount')) {
                $table->decimal('total_claim_amount', 10, 2)->nullable()->default(0)->after('standby_meal');
            }
        });

        // ── Create ticket_proofs table ──
        if (!Schema::hasTable('ticket_proofs')) {
            Schema::create('ticket_proofs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('ticket_id')->constrained('tickets')->cascadeOnDelete();
                $table->foreignId('ticket_status_history_id')->nullable()
                      ->constrained('ticket_status_histories')->nullOnDelete();
                $table->string('proof_type')->comment('whatsapp_screenshot, call_log_screenshot, test_slip, service_form, other');
                $table->string('file_name');
                $table->string('file_path');
                $table->unsignedInteger('file_size')->default(0);
                $table->string('mime_type', 100)->nullable();
                $table->string('caption', 500)->nullable();
                $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->index(['ticket_id', 'proof_type']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('ticket_proofs');

        Schema::table('tickets', function (Blueprint $table) {
            $columns = [
                'vendor_ticket_ref_no', 'tid', 'merchant_name', 'merchant_address',
                'contact_number', 'charge_id', 'mileage', 'mileage_remarks',
                'mileage_rate', 'mileage_amount', 'toll', 'standby_meal', 'total_claim_amount',
            ];
            foreach ($columns as $col) {
                if (Schema::hasColumn('tickets', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
