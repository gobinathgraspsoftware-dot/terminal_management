<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * TeamPermissionsSeeder - Seeds team management permissions.
 */
class TeamPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Seeding Team Permissions...');

        $permissions = [
            'teams.view', 'teams.assign', 'teams.bulk-assign', 'teams.remove',
            'teams.manage', 'teams.export', 'teams.view-own', 'teams.view-members',
        ];

        foreach ($permissions as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
            $this->command->info("  Created: {$name}");
        }

        // Admin gets all
        $admin = Role::findByName('admin', 'web');
        if ($admin) {
            $admin->givePermissionTo($permissions);
            $this->command->info('  Assigned all to Admin');
        }

        // Supervisor gets limited
        $supervisor = Role::findByName('supervisor', 'web');
        if ($supervisor) {
            $supervisor->givePermissionTo(['teams.view-own', 'teams.view-members', 'teams.export']);
            $this->command->info('  Assigned limited to Supervisor');
        }

        $this->command->info('Done!');
    }
}
