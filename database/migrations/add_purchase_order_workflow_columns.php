<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            // Add workflow tracking columns
            $table->timestamp('submitted_at')->nullable()->after('status');
            $table->unsignedBigInteger('submitted_by')->nullable()->after('submitted_at');
            $table->unsignedBigInteger('sent_by')->nullable()->after('sent_at');
            $table->timestamp('rejected_at')->nullable()->after('approved_by');
            $table->unsignedBigInteger('rejected_by')->nullable()->after('rejected_at');
            $table->string('rejection_reason', 500)->nullable()->after('rejected_by');
            $table->timestamp('cancelled_at')->nullable()->after('closed_by');
            $table->unsignedBigInteger('cancelled_by')->nullable()->after('cancelled_at');
            $table->string('cancellation_reason', 500)->nullable()->after('cancelled_by');
            $table->string('closure_reason', 500)->nullable()->after('cancellation_reason');
            $table->text('approval_notes')->nullable()->after('closure_reason');
            
            // Add foreign keys
            $table->foreign('submitted_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('sent_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('rejected_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('cancelled_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->dropForeign(['submitted_by']);
            $table->dropForeign(['sent_by']);
            $table->dropForeign(['rejected_by']);
            $table->dropForeign(['cancelled_by']);
            
            $table->dropColumn([
                'submitted_at',
                'submitted_by',
                'sent_by',
                'rejected_at',
                'rejected_by',
                'rejection_reason',
                'cancelled_at',
                'cancelled_by',
                'cancellation_reason',
                'closure_reason',
                'approval_notes',
            ]);
        });
    }
};
