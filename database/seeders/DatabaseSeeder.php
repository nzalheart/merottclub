<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed required base data only.
     * Demo members, ranking lists, and matches live in DemoDataSeeder.
     */
    public function run(): void
    {
        $this->call(BaseSeeder::class);
    }
}
