<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    /**
     * Seed the club admin account only.
     */
    public function run(): void
    {
        $admin = User::firstOrCreate(
            ['email' => 'admin@ttclub.com'],
            [
                'name' => 'Club Admin',
                'nickname' => 'admin_boss',
                'password' => Hash::make('password'),
                'role' => 'admin',
                'phone' => '+977 9801234567',
                'address' => 'Kathmandu, Nepal',
                'status' => 'active',
            ]
        );

        $admin->forceFill([
            'role' => 'admin',
            'status' => 'active',
        ])->save();

        $this->command?->info(sprintf(
            'Seeded admin: %s (password: password).',
            $admin->email,
        ));
    }
}
