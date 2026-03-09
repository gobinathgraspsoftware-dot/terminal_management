<?php

namespace Database\Seeders;

use App\Models\JobType;
use Illuminate\Database\Seeder;

class JobTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            ['job_title' => 'Installation',   'slug' => 'installation',   'description' => 'New terminal installation at client site'],
            ['job_title' => 'Service',        'slug' => 'service',        'description' => 'General service and maintenance'],
            ['job_title' => 'Repair',         'slug' => 'repair',         'description' => 'Terminal repair work'],
            ['job_title' => 'Replacement',    'slug' => 'replacement',    'description' => 'Terminal replacement (old to new)'],
            ['job_title' => 'Troubleshoot',   'slug' => 'troubleshoot',   'description' => 'Troubleshooting and diagnostics'],
            ['job_title' => 'Collection',     'slug' => 'collection',     'description' => 'Terminal collection from site'],
            ['job_title' => 'Other',          'slug' => 'other',          'description' => 'Other job types'],
        ];

        foreach ($types as $type) {
            JobType::firstOrCreate(
                ['slug' => $type['slug']],
                array_merge($type, ['status' => 'active'])
            );
        }

        $this->command->info('Job types seeded: ' . JobType::count() . ' records');
    }
}
