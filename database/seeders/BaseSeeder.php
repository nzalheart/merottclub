<?php

namespace Database\Seeders;

use App\Models\ThemeSetting;
use Illuminate\Database\Seeder;

class BaseSeeder extends Seeder
{
    /**
     * Seed the records the app needs to run: theme defaults and the admin account.
     */
    public function run(): void
    {
        ThemeSetting::current();

        $this->call(AdminSeeder::class);
    }
}
