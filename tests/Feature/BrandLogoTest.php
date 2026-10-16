<?php

namespace Tests\Feature;

use App\Models\ThemeSetting;
use App\Models\User;
use App\Support\Brand;
use Database\Seeders\BaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BrandLogoTest extends TestCase
{
    use RefreshDatabase;

    public function test_uploaded_logo_appears_in_the_public_header(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('brand/logo.png', 'logo');

        $this->seed(BaseSeeder::class);

        ThemeSetting::current()->saveBrand(
            ThemeSetting::DEFAULT_PRIMARY,
            ThemeSetting::DEFAULT_SECONDARY,
            'brand/logo.png',
        );

        $this->get('/')
            ->assertOk()
            ->assertSee('storage/brand/logo.png', false);
    }

    public function test_removing_the_logo_falls_back_to_the_tt_mark(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('brand/logo.png', 'logo');

        $this->seed(BaseSeeder::class);

        $theme = ThemeSetting::current();
        $theme->saveBrand(
            ThemeSetting::DEFAULT_PRIMARY,
            ThemeSetting::DEFAULT_SECONDARY,
            'brand/logo.png',
        );

        $theme->fresh()->saveBrand(
            ThemeSetting::DEFAULT_PRIMARY,
            ThemeSetting::DEFAULT_SECONDARY,
            null,
        );

        Storage::disk('public')->assertMissing('brand/logo.png');
        $this->assertNull(Brand::logoUrl());

        $this->get('/')
            ->assertOk()
            ->assertDontSee('storage/brand/logo.png', false)
            ->assertSee('TT');
    }

    public function test_admin_can_open_settings(): void
    {
        $this->seed(BaseSeeder::class);
        $admin = User::where('email', 'admin@ttclub.com')->firstOrFail();

        $this->actingAs($admin)
            ->get('/admin/settings')
            ->assertOk()
            ->assertSee('Settings')
            ->assertSee('Club logo');
    }
}
