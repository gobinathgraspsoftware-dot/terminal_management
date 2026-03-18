<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get roles
        $adminRole = Role::where('name', 'admin')->first();
        $supervisorRole = Role::where('name', 'supervisor')->first();
        $technicianRole = Role::where('name', 'technician')->first();

        if (!$adminRole || !$supervisorRole || !$technicianRole) {
            $this->command->error('Roles not found! Please run RoleSeeder first.');
            return;
        }

        // ==============================================
        // ADMIN USERS
        // ==============================================
        $admin = User::create([
            'employee_id' => 'EMP001',
            'name' => 'Admin User',
            'email' => 'admin@tms.com',
            'password' => Hash::make('password123'),
            'phone' => '+60123456789',
            'status' => 'active',
            'supervisor_id' => null,
            'coverage_states' => null,
            'skill_tags' => null,
        ]);
        $admin->assignRole($adminRole);
        $this->command->info('Admin user created: admin@tms.com');

        // Additional admin user
        $admin2 = User::create([
            'employee_id' => 'EMP002',
            'name' => 'Super Admin',
            'email' => 'superadmin@tms.com',
            'password' => Hash::make('password123'),
            'phone' => '+60123456790',
            'status' => 'active',
            'supervisor_id' => null,
        ]);
        $admin2->assignRole($adminRole);
        $this->command->info('Super Admin user created: superadmin@tms.com');

        // ==============================================
        // SUPERVISOR USERS (with supervisor_type)
        // ==============================================

        // Supervisor 1 - Kuala Lumpur Team (Internal)
        $supervisor1 = User::create([
            'employee_id' => 'SUP001',
            'name' => 'Ahmad Supervisor',
            'email' => 'supervisor.kl@tms.com',
            'password' => Hash::make('password123'),
            'phone' => '+60123456791',
            'status' => 'active',
            'supervisor_id' => null,
            'supervisor_type' => 'internal',
            'coverage_states' => json_encode(['Kuala Lumpur', 'Selangor', 'Putrajaya']),
            'skill_tags' => json_encode(['Installation', 'Service', 'Repair']),
        ]);
        $supervisor1->assignRole($supervisorRole);
        $this->command->info('Supervisor created: supervisor.kl@tms.com (KL Team - Internal)');

        // Supervisor 2 - Penang Team (Internal)
        $supervisor2 = User::create([
            'employee_id' => 'SUP002',
            'name' => 'Lee Supervisor',
            'email' => 'supervisor.penang@tms.com',
            'password' => Hash::make('password123'),
            'phone' => '+60123456792',
            'status' => 'active',
            'supervisor_id' => null,
            'supervisor_type' => 'internal',
            'coverage_states' => json_encode(['Penang', 'Kedah', 'Perlis']),
            'skill_tags' => json_encode(['Installation', 'Service', 'Repair']),
        ]);
        $supervisor2->assignRole($supervisorRole);
        $this->command->info('Supervisor created: supervisor.penang@tms.com (Penang Team - Internal)');

        // Supervisor 3 - Johor Team (Internal)
        $supervisor3 = User::create([
            'employee_id' => 'SUP003',
            'name' => 'Kumar Supervisor',
            'email' => 'supervisor.johor@tms.com',
            'password' => Hash::make('password123'),
            'phone' => '+60123456793',
            'status' => 'active',
            'supervisor_id' => null,
            'supervisor_type' => 'internal',
            'coverage_states' => json_encode(['Johor', 'Melaka']),
            'skill_tags' => json_encode(['Installation', 'Service', 'Repair']),
        ]);
        $supervisor3->assignRole($supervisorRole);
        $this->command->info('Supervisor created: supervisor.johor@tms.com (Johor Team - Internal)');

        // Supervisor 4 - External (No Team)
        $supervisor4 = User::create([
            'employee_id' => 'SUP004',
            'name' => 'External Vendor Supervisor',
            'email' => 'supervisor.external@tms.com',
            'password' => Hash::make('password123'),
            'phone' => '+60123456799',
            'status' => 'active',
            'supervisor_id' => null,
            'supervisor_type' => 'external',
            'coverage_states' => json_encode(['Sabah', 'Sarawak']),
            'skill_tags' => json_encode(['Installation', 'Service']),
        ]);
        $supervisor4->assignRole($supervisorRole);
        $this->command->info('Supervisor created: supervisor.external@tms.com (External - No Team)');

        // ==============================================
        // TECHNICIAN USERS - KL Team
        // ==============================================

        $technician1 = User::create([
            'employee_id' => 'TECH001',
            'name' => 'Ali Technician',
            'email' => 'tech1.kl@tms.com',
            'password' => Hash::make('password123'),
            'phone' => '+60123456801',
            'status' => 'active',
            'supervisor_id' => $supervisor1->id,
            'coverage_states' => json_encode(['Kuala Lumpur', 'Selangor']),
            'skill_tags' => json_encode(['Installation', 'Verifone', 'Ingenico']),
            'bank_name' => 'Maybank',
            'bank_account_no' => '1234567890',
            'bank_account_name' => 'Ali Technician',
        ]);
        $technician1->assignRole($technicianRole);
        $this->command->info('Technician created: tech1.kl@tms.com (KL Team)');

        $technician2 = User::create([
            'employee_id' => 'TECH002',
            'name' => 'Siti Technician',
            'email' => 'tech2.kl@tms.com',
            'password' => Hash::make('password123'),
            'phone' => '+60123456802',
            'status' => 'active',
            'supervisor_id' => $supervisor1->id,
            'coverage_states' => json_encode(['Kuala Lumpur', 'Putrajaya']),
            'skill_tags' => json_encode(['Service', 'Repair', 'PAX']),
            'bank_name' => 'CIMB Bank',
            'bank_account_no' => '2345678901',
            'bank_account_name' => 'Siti Technician',
        ]);
        $technician2->assignRole($technicianRole);
        $this->command->info('Technician created: tech2.kl@tms.com (KL Team)');

        // ==============================================
        // TECHNICIAN USERS - Penang Team
        // ==============================================

        $technician3 = User::create([
            'employee_id' => 'TECH003',
            'name' => 'Chen Technician',
            'email' => 'tech1.penang@tms.com',
            'password' => Hash::make('password123'),
            'phone' => '+60123456803',
            'status' => 'active',
            'supervisor_id' => $supervisor2->id,
            'coverage_states' => json_encode(['Penang', 'Kedah']),
            'skill_tags' => json_encode(['Installation', 'Service', 'All Models']),
            'bank_name' => 'Public Bank',
            'bank_account_no' => '3456789012',
            'bank_account_name' => 'Chen Technician',
        ]);
        $technician3->assignRole($technicianRole);
        $this->command->info('Technician created: tech1.penang@tms.com (Penang Team)');

        $technician4 = User::create([
            'employee_id' => 'TECH004',
            'name' => 'Raj Technician',
            'email' => 'tech2.penang@tms.com',
            'password' => Hash::make('password123'),
            'phone' => '+60123456804',
            'status' => 'active',
            'supervisor_id' => $supervisor2->id,
            'coverage_states' => json_encode(['Penang', 'Perlis']),
            'skill_tags' => json_encode(['Repair', 'Troubleshooting', 'Verifone']),
            'bank_name' => 'Hong Leong Bank',
            'bank_account_no' => '4567890123',
            'bank_account_name' => 'Raj Technician',
        ]);
        $technician4->assignRole($technicianRole);
        $this->command->info('Technician created: tech2.penang@tms.com (Penang Team)');

        // ==============================================
        // TECHNICIAN USERS - Johor Team
        // ==============================================

        $technician5 = User::create([
            'employee_id' => 'TECH005',
            'name' => 'Farid Technician',
            'email' => 'tech1.johor@tms.com',
            'password' => Hash::make('password123'),
            'phone' => '+60123456805',
            'status' => 'active',
            'supervisor_id' => $supervisor3->id,
            'coverage_states' => json_encode(['Johor']),
            'skill_tags' => json_encode(['Installation', 'Ingenico', 'PAX']),
            'bank_name' => 'RHB Bank',
            'bank_account_no' => '5678901234',
            'bank_account_name' => 'Farid Technician',
        ]);
        $technician5->assignRole($technicianRole);
        $this->command->info('Technician created: tech1.johor@tms.com (Johor Team)');

        $technician6 = User::create([
            'employee_id' => 'TECH006',
            'name' => 'Mary Technician',
            'email' => 'tech2.johor@tms.com',
            'password' => Hash::make('password123'),
            'phone' => '+60123456806',
            'status' => 'active',
            'supervisor_id' => $supervisor3->id,
            'coverage_states' => json_encode(['Johor', 'Melaka']),
            'skill_tags' => json_encode(['Service', 'Repair', 'All Models']),
            'bank_name' => 'AmBank',
            'bank_account_no' => '6789012345',
            'bank_account_name' => 'Mary Technician',
        ]);
        $technician6->assignRole($technicianRole);
        $this->command->info('Technician created: tech2.johor@tms.com (Johor Team)');

        // ==============================================
        // INDEPENDENT TECHNICIAN (No Supervisor)
        // ==============================================

        $independentTech = User::create([
            'employee_id' => 'TECH999',
            'name' => 'Independent Technician',
            'email' => 'tech.independent@tms.com',
            'password' => Hash::make('password123'),
            'phone' => '+60123456899',
            'status' => 'active',
            'supervisor_id' => null,
            'coverage_states' => json_encode(['Nationwide']),
            'skill_tags' => json_encode(['Expert', 'All Models', 'Senior Technician']),
            'bank_name' => 'Maybank',
            'bank_account_no' => '9876543210',
            'bank_account_name' => 'Independent Technician',
        ]);
        $independentTech->assignRole($technicianRole);
        $this->command->info('Independent Technician created: tech.independent@tms.com (No Supervisor)');

        // ==============================================
        // SUMMARY
        // ==============================================
        $this->command->info('');
        $this->command->info('========================================');
        $this->command->info('USER SEEDING COMPLETED SUCCESSFULLY!');
        $this->command->info('========================================');
        $this->command->info('');
        $this->command->info('USERS CREATED:');
        $this->command->info('  Admin Users: 2');
        $this->command->info('  Supervisors: 4 (3 Internal + 1 External)');
        $this->command->info('  Technicians: 7 (6 with supervisors + 1 independent)');
        $this->command->info('  Total Users: 13');
        $this->command->info('');
        $this->command->info('DEFAULT PASSWORD FOR ALL USERS: password123');
        $this->command->info('');
        $this->command->info('TEST LOGIN CREDENTIALS:');
        $this->command->info('');
        $this->command->info('  ADMIN:');
        $this->command->info('  - admin@tms.com / password123');
        $this->command->info('  - superadmin@tms.com / password123');
        $this->command->info('');
        $this->command->info('  SUPERVISORS:');
        $this->command->info('  - supervisor.kl@tms.com / password123 (KL Team - Internal)');
        $this->command->info('  - supervisor.penang@tms.com / password123 (Penang Team - Internal)');
        $this->command->info('  - supervisor.johor@tms.com / password123 (Johor Team - Internal)');
        $this->command->info('  - supervisor.external@tms.com / password123 (External - No Team)');
        $this->command->info('');
        $this->command->info('  TECHNICIANS:');
        $this->command->info('  - tech1.kl@tms.com / password123 (KL Team)');
        $this->command->info('  - tech2.kl@tms.com / password123 (KL Team)');
        $this->command->info('  - tech1.penang@tms.com / password123 (Penang Team)');
        $this->command->info('  - tech2.penang@tms.com / password123 (Penang Team)');
        $this->command->info('  - tech1.johor@tms.com / password123 (Johor Team)');
        $this->command->info('  - tech2.johor@tms.com / password123 (Johor Team)');
        $this->command->info('  - tech.independent@tms.com / password123 (Independent)');
        $this->command->info('');
        $this->command->info('========================================');
    }
}
