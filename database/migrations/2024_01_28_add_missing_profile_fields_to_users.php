<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * This migration adds ONLY the missing profile fields to the existing users table.
     * The following fields already exist and are NOT added:
     * - phone, avatar, address, bank_name, bank_account_no, bank_account_name
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Bank details - Add missing IFSC and branch fields
            // Note: bank_name, bank_account_no, bank_account_name already exist
            $table->string('ifsc_code', 20)->nullable()->after('bank_account_name');
            $table->string('branch_name', 100)->nullable()->after('ifsc_code');
            
            // Personal information
            $table->date('date_of_birth')->nullable()->after('address');
            $table->enum('gender', ['male', 'female', 'other'])->nullable()->after('date_of_birth');
            
            // Emergency contact information
            $table->string('emergency_contact_name', 100)->nullable()->after('gender');
            $table->string('emergency_contact_phone', 20)->nullable()->after('emergency_contact_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'ifsc_code',
                'branch_name',
                'date_of_birth',
                'gender',
                'emergency_contact_name',
                'emergency_contact_phone'
            ]);
        });
    }
};
