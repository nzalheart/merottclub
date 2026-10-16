<?php

namespace Tests\Feature;

use App\Filament\Resources\ApplicationResource\Pages\ListApplications;
use App\Filament\Resources\ApplicationResource\Pages\ViewApplication;
use App\Filament\Resources\UserResource\Pages\ListUsers;
use App\Mail\ApplicationAccepted;
use App\Mail\ApplicationReceived;
use App\Models\User;
use App\Support\Brand;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\TestCase;

class MembershipApplicationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_see_apply_and_can_submit_a_pending_account(): void
    {
        Mail::fake();
        config(['app.logo' => 'https://example.com/club-logo.png']);

        $this->get('/')
            ->assertOk()
            ->assertSee('Apply')
            ->assertSee('Portal');

        $this->post(route('apply.store'), [
            'name' => 'New Player',
            'nickname' => 'new_player',
            'email' => 'new.player@example.com',
            'phone' => '+977 9800000000',
            'address' => 'Kathmandu',
            'date_of_birth' => '2000-01-02',
            'gender' => 'female',
            'password' => 'password1',
            'password_confirmation' => 'password1',
        ])->assertRedirect(route('apply'));

        $this->get(route('apply'))
            ->assertOk()
            ->assertSee('Application received');

        $applicant = User::where('email', 'new.player@example.com')->first();

        $this->assertNotNull($applicant);
        $this->assertSame('member', $applicant->role);
        $this->assertSame('pending', $applicant->status);
        $this->assertSame('female', $applicant->gender);

        $this->actingAs($applicant)
            ->get('/admin')
            ->assertForbidden();

        Mail::assertSent(ApplicationReceived::class, function (ApplicationReceived $mail) use ($applicant) {
            $html = $mail->render();

            return $mail->hasTo($applicant->email)
                && str_contains($html, Brand::name())
                && str_contains($html, 'https://example.com/club-logo.png');
        });
        Mail::assertNotSent(ApplicationAccepted::class);
    }

    public function test_approving_a_pending_account_sends_an_acceptance_email(): void
    {
        Mail::fake();
        config(['app.logo' => 'https://example.com/club-logo.png']);

        $applicant = User::factory()->member()->create([
            'status' => 'pending',
            'email' => 'waiting@example.com',
        ]);

        $applicant->update(['status' => 'active']);

        $this->actingAs($applicant->fresh())
            ->followingRedirects()
            ->get('/admin')
            ->assertOk();

        Mail::assertSent(ApplicationAccepted::class, function (ApplicationAccepted $mail) use ($applicant) {
            $html = $mail->render();

            return $mail->hasTo($applicant->email)
                && str_contains($html, Brand::name())
                && str_contains($html, 'https://example.com/club-logo.png')
                && str_contains($html, '/admin/login');
        });
    }

    public function test_manager_approves_or_rejects_from_the_applications_menu(): void
    {
        Mail::fake();

        $manager = User::factory()->manager()->create();
        $applicant = User::factory()->member()->create([
            'status' => 'pending',
            'name' => 'Waiting Player',
        ]);
        $member = User::factory()->member()->create([
            'name' => 'Current Player',
        ]);
        $rejected = User::factory()->member()->create([
            'status' => 'pending',
            'name' => 'Denied Player',
        ]);

        $this->actingAs($manager);

        Livewire::test(ListApplications::class)
            ->assertCanSeeTableRecords([$applicant, $rejected])
            ->assertCanNotSeeTableRecords([$member])
            ->callTableAction('approve', $applicant)
            ->callTableAction('reject', $rejected);

        $this->assertSame('active', $applicant->fresh()->status);
        $this->assertSame('rejected', $rejected->fresh()->status);
        Mail::assertSent(ApplicationAccepted::class);

        Livewire::test(ListUsers::class)
            ->assertCanSeeTableRecords([$member, $applicant->fresh()])
            ->assertCanNotSeeTableRecords([$rejected->fresh()]);
    }

    public function test_manager_can_view_the_full_application_without_the_password(): void
    {
        $manager = User::factory()->manager()->create();
        $applicant = User::factory()->member()->create([
            'status' => 'pending',
            'name' => 'Waiting Player',
            'nickname' => 'waiting_player',
            'email' => 'waiting.player@example.com',
            'phone' => '+977980009999',
            'address' => 'Lalitpur, Nepal',
            'date_of_birth' => '1999-04-04',
            'gender' => 'male',
            'password' => 'secret-pass',
        ]);

        $this->actingAs($manager);

        Livewire::test(ViewApplication::class, ['record' => $applicant->getRouteKey()])
            ->assertOk()
            ->assertSee('Waiting Player')
            ->assertSee('waiting_player')
            ->assertSee('waiting.player@example.com')
            ->assertSee('+977980009999')
            ->assertSee('Lalitpur, Nepal')
            ->assertSee('Apr 4, 1999')
            ->assertSee('Male')
            ->assertDontSee('secret-pass')
            ->assertDontSee('Password');
    }

    public function test_signed_in_users_do_not_see_apply(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get('/')
            ->assertOk()
            ->assertDontSee(route('apply'), false);
    }
}
