@props([
    'user' => null,
    'url' => null,
    'name' => null,
    'size' => 'sm',
])

@php
    $sizes = [
        'xs' => 'w-5 h-5 text-[9px]',
        'sm' => 'w-6 h-6 text-[10px]',
        'md' => 'w-8 h-8 text-xs',
        'lg' => 'w-10 h-10 text-sm',
    ];
    $sizeClass = $sizes[$size] ?? $sizes['sm'];
    $avatarUrl = $url ?? ($user instanceof \App\Models\User ? $user->avatarPublicUrl() : null);
    $label = $name ?? ($user instanceof \App\Models\User ? $user->name : null) ?? '?';
    $initials = $user instanceof \App\Models\User
        ? $user->initials()
        : mb_strtoupper(mb_substr(trim((string) $label) ?: '?', 0, 1));
@endphp

@if($avatarUrl)
    <img
        src="{{ $avatarUrl }}"
        alt=""
        {{ $attributes->merge(['class' => $sizeClass.' rounded-full object-cover shrink-0 bg-surface border border-theme']) }}
    >
@else
    <span
        {{ $attributes->merge(['class' => $sizeClass.' rounded-full shrink-0 inline-flex items-center justify-center font-heading font-bold bg-secondary-soft text-secondary border border-secondary']) }}
        aria-hidden="true"
    >{{ $initials }}</span>
@endif
