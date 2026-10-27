@props([
    'user' => null,
    'label' => null,
    'size' => 'sm',
    'link' => null,
    'truncate' => true,
])

@php
    /** @var \App\Models\User|null $user */
    $text = $label ?? ($user?->publicName() ?? 'Player');
    $shouldLink = $link ?? ($user !== null);
    $profileUrl = ($shouldLink && $user) ? route('player.profile', $user) : null;
    $wrapperClass = 'inline-flex items-center gap-2 min-w-0 max-w-full';
    $textClass = ($truncate ? 'truncate ' : '').'leading-tight';
@endphp

@if($profileUrl)
    <a href="{{ $profileUrl }}" {{ $attributes->merge(['class' => $wrapperClass.' hover:text-secondary transition']) }}>
        <x-avatar :user="$user" :size="$size" />
        <span class="{{ $textClass }}">{{ $text }}</span>
    </a>
@else
    <span {{ $attributes->merge(['class' => $wrapperClass]) }}>
        <x-avatar :user="$user" :name="$text" :size="$size" />
        <span class="{{ $textClass }}">{{ $text }}</span>
    </span>
@endif
