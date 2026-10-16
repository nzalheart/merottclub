<?php

namespace App\Filament\Auth;

use App\Models\User;
use Filament\Http\Responses\Auth\Contracts\LoginResponse;
use Filament\Pages\Auth\Login as BaseLogin;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class Login extends BaseLogin
{
    public function authenticate(): ?LoginResponse
    {
        try {
            return parent::authenticate();
        } catch (ValidationException $exception) {
            $data = $this->form->getState();
            $email = $data['email'] ?? null;
            $password = $data['password'] ?? null;

            $user = is_string($email)
                ? User::query()->where('email', $email)->first()
                : null;

            if ($user && is_string($password) && Hash::check($password, $user->password) && $user->status !== 'active') {
                $message = $user->status === 'pending'
                    ? 'Your application is waiting for admin approval. You can sign in after it is accepted.'
                    : 'This account is not active.';

                throw ValidationException::withMessages([
                    'data.email' => $message,
                ]);
            }

            throw $exception;
        }
    }
}
