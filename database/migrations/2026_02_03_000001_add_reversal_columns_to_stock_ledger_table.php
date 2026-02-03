<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Adds reversal tracking columns to the stock_ledger table.
     */
    public function up(): void
    {
        Schema::table('stock_ledger', function (Blueprint $table) {
            // Reversal tracking
            $table->boolean('is_reversed')->default(false)->after('remarks')
                  ->comment('Whether this movement has been reversed');
            $table->unsignedBigInteger('reversed_by_id')->nullable()->after('is_reversed')
                  ->comment('ID of the ledger entry that reversed this movement');
            $table->unsignedBigInteger('reversal_of_id')->nullable()->after('reversed_by_id')
                  ->comment('If this is a reversal, the original movement ID');
            $table->timestamp('reversed_at')->nullable()->after('reversal_of_id')
                  ->comment('When this movement was reversed');
            $table->unsignedBigInteger('reversed_by_user_id')->nullable()->after('reversed_at')
                  ->comment('User who performed the reversal');

            // Indexes for performance
            $table->index('is_reversed', 'stock_ledger_is_reversed_index');
            $table->index('reversal_of_id', 'stock_ledger_reversal_of_id_index');

            // Foreign keys (self-referencing)
            $table->foreign('reversed_by_id')
                  ->references('id')->on('stock_ledger')
                  ->onDelete('set null');
            $table->foreign('reversal_of_id')
                  ->references('id')->on('stock_ledger')
                  ->onDelete('set null');
            $table->foreign('reversed_by_user_id')
                  ->references('id')->on('users')
                  ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stock_ledger', function (Blueprint $table) {
            $table->dropForeign(['reversed_by_id']);
            $table->dropForeign(['reversal_of_id']);
            $table->dropForeign(['reversed_by_user_id']);

            $table->dropIndex('stock_ledger_is_reversed_index');
            $table->dropIndex('stock_ledger_reversal_of_id_index');

            $table->dropColumn([
                'is_reversed',
                'reversed_by_id',
                'reversal_of_id',
                'reversed_at',
                'reversed_by_user_id',
            ]);
        });
    }
};
