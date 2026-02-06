<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('stock_issue_lines', function (Blueprint $table) {
            $table->enum('condition', ['good', 'damaged', 'defective'])
                  ->default('good')
                  ->after('quantity')
                  ->comment('Condition of returned item');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stock_issue_lines', function (Blueprint $table) {
            $table->dropColumn('condition');
        });
    }
};
