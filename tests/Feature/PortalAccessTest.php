<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\BaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PortalAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_base_seeder_creates_an_admin_who_can_open_the_portal(): void
    {
        $this->seed(BaseSeeder::class);

        $admin = User::where('email', 'admin@ttclub.com')->first();

        $this->assertNotNull($admin);
        $this->assertSame('admin', $admin->role);
        $this->assertSame('active', $admin->status);
        $this->assertSame(1, User::count());

        $this->actingAs($admin)
            ->followingRedirects()
            ->get('/admin')
            ->assertOk();
    }

    public function test_pending_members_cannot_open_the_portal(): void
    {
        $member = User::factory()->member()->create(['status' => 'pending']);

        $this->actingAs($member)
            ->get('/admin')
            ->assertForbidden();
    }

    public function test_active_members_can_open_the_portal(): void
    {
        $member = User::factory()->member()->create(['status' => 'active']);

        $this->actingAs($member)
            ->followingRedirects()
            ->get('/admin')
            ->assertOk();
    }
}
