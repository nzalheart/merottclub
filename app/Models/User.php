<?php

namespace App\Models;

use App\Mail\ApplicationAccepted;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasAvatar;
use Filament\Panel;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Mail;

class User extends Authenticatable implements CanResetPasswordContract, FilamentUser, HasAvatar
{
    /** @use HasFactory<UserFactory> */
    use CanResetPassword, HasFactory, Notifiable;

    public const GENDERS = [
        'female' => 'Female',
        'male' => 'Male',
    ];

    protected $fillable = [
        'name',
        'nickname',
        'email',
        'password',
        'role',
        'phone',
        'address',
        'date_of_birth',
        'gender',
        'status',
        'avatar_url',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'date_of_birth' => 'date',
            'password' => 'hashed',
        ];
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isManager(): bool
    {
        return in_array($this->role, ['admin', 'manager']);
    }

    public function canEnterAdmin(): bool
    {
        return $this->status === 'active' && $this->isManager();
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->canEnterAdmin();
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function publicName(): string
    {
        $nickname = trim((string) $this->nickname);

        if ($nickname !== '') {
            return "{$this->name} (@{$nickname})";
        }

        return $this->name;
    }

    public function avatarPublicUrl(): ?string
    {
        $path = $this->avatar_url;

        if (! is_string($path) || $path === '') {
            return null;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        return asset('storage/'.ltrim($path, '/')).'?v='.$this->updated_at?->timestamp;
    }

    public function getFilamentAvatarUrl(): ?string
    {
        return $this->avatarPublicUrl();
    }

    protected static function booted(): void
    {
        static::updated(function (User $user): void {
            if ($user->wasChanged('status') && $user->getOriginal('status') === 'pending' && $user->status === 'active') {
                Mail::to($user)->send(new ApplicationAccepted($user));
            }
        });
    }

    public function rankings(): HasMany
    {
        return $this->hasMany(PlayerRanking::class, 'user_id');
    }
}
