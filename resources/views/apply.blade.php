@extends('layouts.app')

@section('title', 'Apply')

@section('content')
<div class="max-w-xl mx-auto">
    <div class="mb-5">
        <h2 class="text-xl lg:text-2xl font-bold font-heading text-ink">Apply to join</h2>
        <p class="text-xs lg:text-sm text-muted mt-1">Create an account. An admin must approve it before you can sign in to the portal.</p>
    </div>

    @if(session('applied'))
        <div class="surface-card rounded-2xl p-5 lg:p-6">
            <h3 class="font-heading font-bold text-lg text-ink">Application received</h3>
            <p class="text-sm text-muted mt-2">We emailed you a confirmation. You can sign in beside Portal after an admin accepts your account.</p>
        </div>
    @else
        <form action="{{ route('apply.store') }}" method="POST" class="surface-card rounded-2xl p-4 lg:p-6 space-y-4">
            @csrf

            @if($errors->any())
                <div class="rounded-xl border border-primary bg-primary-soft px-3 py-2 text-xs text-primary">
                    Check the highlighted fields and try again.
                </div>
            @endif

            <div>
                <label for="name" class="block text-[10px] uppercase font-bold text-secondary mb-1">Full name</label>
                <input id="name" name="name" value="{{ old('name') }}" required class="w-full bg-white border border-theme rounded-xl px-3 py-2.5 text-sm text-ink focus:outline-none focus:border-secondary">
                @error('name')<p class="text-xs text-primary mt-1">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="nickname" class="block text-[10px] uppercase font-bold text-secondary mb-1">Nickname</label>
                <input id="nickname" name="nickname" value="{{ old('nickname') }}" required placeholder="dragon" class="w-full bg-white border border-theme rounded-xl px-3 py-2.5 text-sm text-ink focus:outline-none focus:border-secondary">
                @error('nickname')<p class="text-xs text-primary mt-1">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="email" class="block text-[10px] uppercase font-bold text-secondary mb-1">Email</label>
                <input id="email" name="email" type="email" value="{{ old('email') }}" required class="w-full bg-white border border-theme rounded-xl px-3 py-2.5 text-sm text-ink focus:outline-none focus:border-secondary">
                @error('email')<p class="text-xs text-primary mt-1">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="phone" class="block text-[10px] uppercase font-bold text-secondary mb-1">Phone</label>
                <input id="phone" name="phone" value="{{ old('phone') }}" required class="w-full bg-white border border-theme rounded-xl px-3 py-2.5 text-sm text-ink focus:outline-none focus:border-secondary">
                @error('phone')<p class="text-xs text-primary mt-1">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="address" class="block text-[10px] uppercase font-bold text-secondary mb-1">Address</label>
                <input id="address" name="address" value="{{ old('address') }}" class="w-full bg-white border border-theme rounded-xl px-3 py-2.5 text-sm text-ink focus:outline-none focus:border-secondary">
                @error('address')<p class="text-xs text-primary mt-1">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="gender" class="block text-[10px] uppercase font-bold text-secondary mb-1">Gender</label>
                <select id="gender" name="gender" required class="w-full bg-white border border-theme rounded-xl px-3 py-2.5 text-sm text-ink focus:outline-none focus:border-secondary">
                    <option value="">Select gender</option>
                    @foreach(\App\Models\User::GENDERS as $value => $label)
                        <option value="{{ $value }}" @selected(old('gender') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                @error('gender')<p class="text-xs text-primary mt-1">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="date_of_birth" class="block text-[10px] uppercase font-bold text-secondary mb-1">Date of birth</label>
                <input id="date_of_birth" name="date_of_birth" type="date" value="{{ old('date_of_birth') }}" class="w-full bg-white border border-theme rounded-xl px-3 py-2.5 text-sm text-ink focus:outline-none focus:border-secondary">
                @error('date_of_birth')<p class="text-xs text-primary mt-1">{{ $message }}</p>@enderror
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="password" class="block text-[10px] uppercase font-bold text-secondary mb-1">Password</label>
                    <input id="password" name="password" type="password" required class="w-full bg-white border border-theme rounded-xl px-3 py-2.5 text-sm text-ink focus:outline-none focus:border-secondary">
                    @error('password')<p class="text-xs text-primary mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="password_confirmation" class="block text-[10px] uppercase font-bold text-secondary mb-1">Confirm password</label>
                    <input id="password_confirmation" name="password_confirmation" type="password" required class="w-full bg-white border border-theme rounded-xl px-3 py-2.5 text-sm text-ink focus:outline-none focus:border-secondary">
                </div>
            </div>

            <button type="submit" class="w-full sm:w-auto px-5 py-2.5 rounded-xl text-sm font-semibold bg-primary text-white hover:opacity-90 transition">
                Submit application
            </button>
        </form>
    @endif
</div>
@endsection
