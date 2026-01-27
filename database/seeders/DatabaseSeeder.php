<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->command->info('');
        $this->command->info('════════════════════════════════════════════════════');
        $this->command->info('🚀 STARTING TMS DATABASE SEEDING');
        $this->command->info('════════════════════════════════════════════════════');
        $this->command->info('');

        // Step 1: Seed Permissions
        $this->command->info('📝 Step 1: Seeding Permissions...');
        $this->call(PermissionSeeder::class);
        $this->command->info('');

        // Note: RoleSeeder should already be run (roles already exist)
        $this->command->info('ℹ️  Step 2: Roles (Skipped - Already seeded via SQL)');
        $this->command->info('   → Admin, Supervisor, Technician roles exist');
        $this->command->info('');

        // Step 3: Assign Permissions to Roles
        $this->command->info('🔐 Step 3: Assigning Permissions to Roles...');
        $this->call(RolePermissionSeeder::class);
        $this->command->info('');

        // Step 4: Create Test Users
        $this->command->info('👥 Step 4: Creating Test Users...');
        $this->call(UserSeeder::class);
        $this->command->info('');

        // Summary
        $this->command->info('════════════════════════════════════════════════════');
        $this->command->info('✅ TMS DATABASE SEEDING COMPLETED!');
        $this->command->info('════════════════════════════════════════════════════');
        $this->command->info('');
        $this->command->info('📊 WHAT WAS SEEDED:');
        $this->command->info('   ✅ Permissions: ~200+ permissions created');
        $this->command->info('   ✅ Role-Permissions: Assigned to 3 roles');
        $this->command->info('   ✅ Users: 12 test users created');
        $this->command->info('');
        $this->command->info('🎯 NEXT STEPS:');
        $this->command->info('   1. Start server: php artisan serve');
        $this->command->info('   2. Visit: http://localhost:8000/login');
        $this->command->info('   3. Login with: admin@tms.com / password123');
        $this->command->info('');
        $this->command->info('⚠️  IMPORTANT: Change default passwords in production!');
        $this->command->info('');
        $this->command->info('════════════════════════════════════════════════════');
    }
}