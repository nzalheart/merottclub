@php
    /** @var \App\Models\User|null $user */
    $user = $getRecord();
@endphp

@if($user)
    <div class="fi-ta-text flex items-center gap-2">
        @if($url = $user->avatarPublicUrl())
            <img src="{{ $url }}" alt="" class="h-8 w-8 rounded-full object-cover ring-1 ring-gray-950/10 dark:ring-white/20">
        @else
            <span class="flex h-8 w-8 items-center justify-center rounded-full bg-gray-100 text-xs font-semibold text-gray-600 ring-1 ring-gray-950/10 dark:bg-white/10 dark:text-gray-300 dark:ring-white/20">
                {{ $user->initials() }}
            </span>
        @endif
        <span class="fi-ta-text-item-label text-sm leading-6 text-gray-950 dark:text-white">
            {{ $user->name }}
        </span>
    </div>
@endif
