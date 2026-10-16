<?php

namespace Tests\Feature;

use App\Filament\Auth\RequestPasswordReset;
use App\Filament\Auth\ResetPassword;
use App\Mail\PasswordResetLink;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_form_links_to_the_reset_request(): void
    {
        $this->get('/admin/login')
            ->assertOk()
            ->assertSee('Forgot password?')
            ->assertSee('/admin/password-reset/request');
    }

    public function test_active_user_can_reset_a_password_from_the_emailed_link(): void
    {
        Mail::fake();

        $user = User::factory()->create([
            'email' => 'reset.me@example.com',
            'password' => 'old-password',
            'status' => 'active',
        ]);

        Livewire::test(RequestPasswordReset::class)
            ->fillForm(['email' => $user->email])
            ->call('request');

        $resetUrl = null;

        Mail::assertSent(PasswordResetLink::class, function (PasswordResetLink $mail) use ($user, &$resetUrl) {
            $resetUrl = $mail->resetUrl;

            return $mail->hasTo($user->email)
                && str_contains($mail->resetUrl, '/admin/password-reset/reset');
        });

        $this->get($resetUrl)->assertOk();

        parse_str((string) parse_url($resetUrl, PHP_URL_QUERY), $query);

        Livewire::test(ResetPassword::class, [
            'email' => $query['email'],
            'token' => $query['token'],
        ])
            ->fillForm([
                'password' => 'new-password',
                'passwordConfirmation' => 'new-password',
            ])
            ->call('resetPassword')
            ->assertHasNoFormErrors();

        $this->assertTrue(Hash::check('new-password', $user->fresh()->password));
    }

    public function test_inactive_accounts_do_not_receive_a_reset_email(): void
    {
        Mail::fake();

        $user = User::factory()->create([
            'email' => 'pending.reset@example.com',
            'status' => 'pending',
        ]);

        Livewire::test(RequestPasswordReset::class)
            ->fillForm(['email' => $user->email])
            ->call('request');

        Mail::assertNotSent(PasswordResetLink::class);
    }
}
