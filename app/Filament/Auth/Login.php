<?php

namespace App\Filament\Auth;

use App\Models\User;
use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;
use Filament\Facades\Filament;
use Filament\Http\Responses\Auth\Contracts\LoginResponse;
use Filament\Pages\Auth\Login as BaseLogin;
use Illuminate\Validation\ValidationException;

class Login extends BaseLogin
{
    public function authenticate(): ?LoginResponse
    {
        try {
            $this->rateLimit(5);
        } catch (TooManyRequestsException $exception) {
            $this->getRateLimitedNotification($exception)?->send();

            return null;
        }

        $data = $this->form->getState();

        if (! Filament::auth()->attempt($this->getCredentialsFromFormData($data), $data['remember'] ?? false)) {
            $this->throwFailureValidationException();
        }

        $user = Filament::auth()->user();

        if (! $user instanceof User || $user->status !== 'active') {
            $status = $user instanceof User ? $user->status : null;
            Filament::auth()->logout();

            $message = match ($status) {
                'pending' => 'Your application is waiting for admin approval. You can sign in after it is accepted.',
                null => __('filament-panels::pages/auth/login.messages.failed'),
                default => 'This account is not active.',
            };

            throw ValidationException::withMessages([
                'data.email' => $message,
            ]);
        }

        session()->regenerate();

        if (! $user->canEnterAdmin()) {
            return app(HomeLoginResponse::class);
        }

        return app(LoginResponse::class);
    }
}
