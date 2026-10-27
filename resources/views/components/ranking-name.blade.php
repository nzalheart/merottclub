@props([
    'ranking' => null,
    'users' => null,
    'label' => null,
    'size' => 'sm',
    'link' => true,
    'truncate' => true,
    'align' => 'left',
])

@php
    /** @var \App\Models\PlayerRanking|null $ranking */
    $people = collect($users ?? [])
        ->filter(fn ($user) => $user instanceof \App\Models\User)
        ->values();

    if ($people->isEmpty() && $ranking) {
        if ($ranking->user) {
            $people->push($ranking->user);
        }
        if ($ranking->player2) {
            $people->push($ranking->player2);
        }
    }

    $text = $label ?? $ranking?->display_name ?? 'Unknown Player';
    $textClass = ($truncate ? 'truncate ' : '').'leading-tight';
    $isEnd = $align === 'right';
    $wrapperClass = 'inline-flex items-center gap-2 min-w-0 max-w-full'.($isEnd ? ' flex-row-reverse text-right' : '');
@endphp

<span {{ $attributes->merge(['class' => $wrapperClass]) }}>
    @if($people->isNotEmpty())
        <span class="inline-flex items-center shrink-0 {{ $people->count() > 1 ? ($isEnd ? '-space-x-reverse -space-x-1.5' : '-space-x-1.5') : '' }}">
            @foreach($people as $person)
                @if($link)
                    <a href="{{ route('player.profile', $person) }}" class="relative hover:z-10" title="{{ $person->publicName() }}">
                        <x-avatar :user="$person" :size="$size" class="ring-2 ring-white" />
                    </a>
                @else
                    <span class="relative" title="{{ $person->publicName() }}">
                        <x-avatar :user="$person" :size="$size" class="{{ $people->count() > 1 ? 'ring-2 ring-white' : '' }}" />
                    </span>
                @endif
            @endforeach
        </span>
    @else
        <x-avatar :name="$text" :size="$size" />
    @endif
    <span class="{{ $textClass }}">{{ $text }}</span>
</span>
