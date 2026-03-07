<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class StateSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();

        DB::table('states')->insert([
            ['id' => 1, 'name' => 'Johor', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 2, 'name' => 'Kedah', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 3, 'name' => 'Kelantan', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 4, 'name' => 'Melaka', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 5, 'name' => 'Negeri Sembilan', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 6, 'name' => 'Pahang', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 7, 'name' => 'Penang', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 8, 'name' => 'Perak', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 9, 'name' => 'Perlis', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 10, 'name' => 'Sabah', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 11, 'name' => 'Sarawak', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 12, 'name' => 'Selangor', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 13, 'name' => 'Terengganu', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 14, 'name' => 'Kuala Lumpur', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 15, 'name' => 'Labuan', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 16, 'name' => 'Putrajaya', 'created_at' => $now, 'updated_at' => $now],
        ]);
    }
}
