<?php

namespace Tests\Feature;

use App\Filament\Auth\Login;
use App\Models\User;
use Database\Seeders\BaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
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

    public function test_active_members_stay_on_the_homepage_and_cannot_open_admin(): void
    {
        $member = User::factory()->member()->create([
            'email' => 'member@example.com',
            'password' => 'password',
            'status' => 'active',
        ]);

        Livewire::test(Login::class)
            ->fillForm([
                'email' => 'member@example.com',
                'password' => 'password',
            ])
            ->call('authenticate')
            ->assertRedirect(route('leaderboard'));

        $this->actingAs($member)
            ->get('/')
            ->assertOk()
            ->assertSee('Profile')
            ->assertDontSee('Portal')
            ->assertDontSee('/admin', false);

        $this->actingAs($member)
            ->get('/admin')
            ->assertForbidden();

        $this->actingAs($member)
            ->get('/admin/match-records/create')
            ->assertForbidden();

        $this->actingAs($member)
            ->get(route('profile.edit'))
            ->assertOk();
    }

    public function test_managers_still_see_admin_links(): void
    {
        $manager = User::factory()->manager()->create(['status' => 'active']);

        $this->actingAs($manager)
            ->get('/')
            ->assertOk()
            ->assertSee('Portal')
            ->assertSee('/admin/login', false)
            ->assertSee('/admin/match-records/create', false);

        $this->actingAs($manager)
            ->followingRedirects()
            ->get('/admin')
            ->assertOk();
    }
}
