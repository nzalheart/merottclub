@extends('layouts.app')

@section('title', 'Profile')

@section('content')
<div class="max-w-xl mx-auto">
    <div class="mb-5">
        <h2 class="text-xl lg:text-2xl font-bold font-heading text-ink">Profile</h2>
        <p class="text-xs lg:text-sm text-muted mt-1">{{ $user->name }} · {{ $user->email }}</p>
    </div>

    @if(session('saved'))
        <div class="mb-4 rounded-xl border border-secondary bg-secondary-soft px-3 py-2 text-sm text-secondary">
            Your profile was saved.
        </div>
    @endif

    <form action="{{ route('profile.update') }}" method="POST" enctype="multipart/form-data" class="surface-card rounded-2xl p-4 lg:p-6 space-y-4">
        @csrf
        @method('PUT')

        @if($errors->any())
            <div class="rounded-xl border border-primary bg-primary-soft px-3 py-2 text-xs text-primary">
                Check the highlighted fields and try again.
            </div>
        @endif

        <div>
            <label for="avatar" class="block text-[10px] uppercase font-bold text-secondary mb-1">Photo</label>
            <div class="flex items-center gap-3">
                <div class="w-16 h-16 rounded-full overflow-hidden border border-theme bg-secondary-soft text-secondary flex items-center justify-center font-heading font-bold text-lg shrink-0">
                    @if($avatarUrl = $user->avatarPublicUrl())
                        <img src="{{ $avatarUrl }}" alt="" class="w-full h-full object-cover">
                    @else
                        {{ strtoupper(substr($user->name, 0, 1)) }}
                    @endif
                </div>
                <input id="avatar" name="avatar" type="file" accept="image/jpeg,image/png,image/webp,image/gif" class="block w-full text-sm text-ink file:mr-3 file:rounded-lg file:border-0 file:bg-secondary-soft file:px-3 file:py-2 file:text-xs file:font-semibold file:text-secondary">
            </div>
            <p class="text-xs text-muted mt-1">Photos are resized to 500×500 at most and kept under 200 KB.</p>
            @error('avatar')<p class="text-xs text-primary mt-1">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="phone" class="block text-[10px] uppercase font-bold text-secondary mb-1">Phone</label>
            <input id="phone" name="phone" value="{{ old('phone', $user->phone) }}" class="w-full bg-white border border-theme rounded-xl px-3 py-2.5 text-sm text-ink focus:outline-none focus:border-secondary">
            @error('phone')<p class="text-xs text-primary mt-1">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="address" class="block text-[10px] uppercase font-bold text-secondary mb-1">Address</label>
            <input id="address" name="address" value="{{ old('address', $user->address) }}" class="w-full bg-white border border-theme rounded-xl px-3 py-2.5 text-sm text-ink focus:outline-none focus:border-secondary">
            @error('address')<p class="text-xs text-primary mt-1">{{ $message }}</p>@enderror
        </div>

        <div class="pt-2 border-t border-theme">
            <p class="text-sm font-semibold text-ink">Change password</p>
            <p class="text-xs text-muted mt-0.5">Leave these blank to keep your current password.</p>
        </div>

        <div>
            <label for="current_password" class="block text-[10px] uppercase font-bold text-secondary mb-1">Current password</label>
            <input id="current_password" name="current_password" type="password" autocomplete="current-password" class="w-full bg-white border border-theme rounded-xl px-3 py-2.5 text-sm text-ink focus:outline-none focus:border-secondary">
            @error('current_password')<p class="text-xs text-primary mt-1">{{ $message }}</p>@enderror
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label for="password" class="block text-[10px] uppercase font-bold text-secondary mb-1">New password</label>
                <input id="password" name="password" type="password" autocomplete="new-password" class="w-full bg-white border border-theme rounded-xl px-3 py-2.5 text-sm text-ink focus:outline-none focus:border-secondary">
                @error('password')<p class="text-xs text-primary mt-1">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="password_confirmation" class="block text-[10px] uppercase font-bold text-secondary mb-1">Confirm password</label>
                <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" class="w-full bg-white border border-theme rounded-xl px-3 py-2.5 text-sm text-ink focus:outline-none focus:border-secondary">
            </div>
        </div>

        <button type="submit" class="w-full rounded-xl bg-secondary text-white font-semibold text-sm py-2.5 hover:opacity-90 transition">
            Save profile
        </button>
    </form>
</div>
@endsection
