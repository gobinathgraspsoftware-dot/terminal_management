<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

/**
 * TeamTestDataSeeder - Creates test supervisors and technicians.
 */
class TeamTestDataSeeder extends Seeder
{
    protected array $regions = [
        'Northern' => ['Penang', 'Kedah', 'Perlis', 'Perak'],
        'Central' => ['Selangor', 'Kuala Lumpur', 'Putrajaya', 'Negeri Sembilan'],
        'Southern' => ['Johor', 'Malacca'],
        'East Coast' => ['Pahang', 'Terengganu', 'Kelantan'],
        'East Malaysia' => ['Sabah', 'Sarawak', 'Labuan'],
    ];

    protected array $skills = ['Installation', 'Repair', 'Troubleshooting', 'Maintenance', 'Network Setup', 'POS Config', 'Training'];

    public function run(): void
    {
        $this->command->info('Seeding Team Test Data...');

        foreach (['admin', 'supervisor', 'technician'] as $role) {
            Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
        }

        $supervisors = [];
        $supCount = 1;

        foreach ($this->regions as $region => $states) {
            $sup = User::firstOrCreate(
                ['email' => 'supervisor.' . strtolower(str_replace(' ', '', $region)) . '@tms.test'],
                [
                    'employee_id' => 'SUP' . str_pad($supCount, 4, '0', STR_PAD_LEFT),
                    'name' => $region . ' Supervisor',
                    'password' => Hash::make('password123'),
                    'phone' => '01' . rand(10000000, 99999999),
                    'coverage_states' => $states,
                    'skill_tags' => ['Management', 'Coordination'],
                    'status' => 'active',
                ]
            );
            $sup->assignRole('supervisor');
            $supervisors[$region] = $sup;
            $supCount++;
            $this->command->info("  Supervisor: {$sup->name}");
        }

        $techCount = 1;
        foreach ($this->regions as $region => $states) {
            $sup = $supervisors[$region];
            for ($i = 1; $i <= rand(3, 6); $i++) {
                $tech = User::firstOrCreate(
                    ['email' => "tech.{$region}.{$i}@tms.test"],
                    [
                        'employee_id' => 'TECH' . str_pad($techCount, 4, '0', STR_PAD_LEFT),
                        'name' => $this->randomName(),
                        'password' => Hash::make('password123'),
                        'phone' => '01' . rand(10000000, 99999999),
                        'supervisor_id' => $sup->id,
                        'coverage_states' => array_slice($states, 0, rand(1, count($states))),
                        'skill_tags' => array_slice($this->skills, 0, rand(2, 4)),
                        'status' => rand(1, 5) == 1 ? 'inactive' : 'active',
                    ]
                );
                $tech->assignRole('technician');
                $techCount++;
            }
            $this->command->info("  Created technicians for {$region}");
        }

        // Independent technicians
        for ($i = 1; $i <= 5; $i++) {
            $tech = User::firstOrCreate(
                ['email' => "tech.independent.{$i}@tms.test"],
                [
                    'employee_id' => 'IND' . str_pad($i, 4, '0', STR_PAD_LEFT),
                    'name' => $this->randomName(),
                    'password' => Hash::make('password123'),
                    'phone' => '01' . rand(10000000, 99999999),
                    'supervisor_id' => null,
                    'coverage_states' => array_rand(array_flip(['Selangor', 'Kuala Lumpur', 'Johor', 'Penang']), 2),
                    'skill_tags' => array_slice($this->skills, 0, rand(2, 3)),
                    'status' => 'active',
                ]
            );
            $tech->assignRole('technician');
        }
        $this->command->info("  Created 5 independent technicians");

        $this->command->info('Done!');
    }

    protected function randomName(): string
    {
        $first = ['Ahmad', 'Ali', 'Hassan', 'Siti', 'Nur', 'Wei Ming', 'Kumar', 'Rajan', 'Fatimah', 'Kamal'];
        $last = ['Abdullah', 'Hassan', 'Ibrahim', 'Tan', 'Lee', 'Wong', 'Raman', 'Krishnan'];
        return $first[array_rand($first)] . ' ' . $last[array_rand($last)];
    }
}
