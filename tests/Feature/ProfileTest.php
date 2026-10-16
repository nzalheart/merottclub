<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\AvatarResizer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_do_not_see_the_account_menu(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertDontSee('Sign out')
            ->assertDontSee(route('profile.edit'), false);

        $this->get(route('profile.edit'))->assertRedirect('/admin/login');
    }

    public function test_signed_in_user_can_update_details_and_password(): void
    {
        $user = User::factory()->create([
            'password' => 'password',
            'address' => 'Old street',
            'status' => 'active',
        ]);

        $this->actingAs($user)
            ->get('/')
            ->assertOk()
            ->assertSee('Profile')
            ->assertSee('Sign out')
            ->assertDontSee('>Apply<', false)
            ->assertDontSee('Portal')
            ->assertDontSee('/admin', false);

        $this->actingAs($user)
            ->put(route('profile.update'), [
                'phone' => '9800000000',
                'address' => 'New street',
                'current_password' => 'password',
                'password' => 'new-password',
                'password_confirmation' => 'new-password',
            ])
            ->assertRedirect(route('profile.edit'));

        $user->refresh();
        $this->assertSame('9800000000', $user->phone);
        $this->assertSame('New street', $user->address);
        $this->assertTrue(Hash::check('new-password', $user->password));

        $this->actingAs($user)
            ->put(route('profile.update'), [
                'phone' => '9800000000',
                'address' => 'Later street',
                'current_password' => 'browser-autofill',
            ])
            ->assertRedirect(route('profile.edit'));

        $this->assertSame('Later street', $user->refresh()->address);
        $this->assertTrue(Hash::check('new-password', $user->password));
    }

    public function test_uploaded_photo_is_resized_and_the_original_is_not_kept(): void
    {
        Storage::fake('public');

        $user = User::factory()->create([
            'status' => 'active',
        ]);

        $source = imagecreatetruecolor(1600, 1200);
        for ($x = 0; $x < 1600; $x += 3) {
            $color = imagecolorallocate($source, random_int(0, 255), random_int(0, 255), random_int(0, 255));
            imageline($source, $x, 0, $x, 1199, $color);
        }
        $original = tempnam(sys_get_temp_dir(), 'avatar');
        imagejpeg($source, $original, 95);
        imagedestroy($source);

        $originalBytes = filesize($original);
        $this->assertGreaterThan(AvatarResizer::MAX_BYTES, $originalBytes);

        $this->actingAs($user)
            ->put(route('profile.update'), [
                'address' => 'Photo street',
                'avatar' => new UploadedFile($original, 'portrait.jpg', 'image/jpeg', null, true),
            ])
            ->assertRedirect(route('profile.edit'));

        $user->refresh();
        $this->assertSame('avatars/'.$user->id.'.jpg', $user->avatar_url);

        $stored = Storage::disk('public')->path($user->avatar_url);
        $this->assertLessThanOrEqual(AvatarResizer::MAX_BYTES, filesize($stored));
        [$width, $height] = getimagesize($stored);
        $this->assertLessThanOrEqual(AvatarResizer::MAX_EDGE, $width);
        $this->assertLessThanOrEqual(AvatarResizer::MAX_EDGE, $height);
        $this->assertLessThan($originalBytes, filesize($stored));

        Storage::disk('public')->assertMissing('portrait.jpg');
        $this->assertCount(1, Storage::disk('public')->allFiles('avatars'));

        @unlink($original);
    }

    public function test_sign_out_ends_the_session(): void
    {
        $user = User::factory()->create(['status' => 'active']);

        $this->actingAs($user)
            ->post(route('logout'))
            ->assertRedirect(route('leaderboard'));

        $this->get(route('profile.edit'))->assertRedirect('/admin/login');
    }
}
