@php
    /** @var \App\Models\MatchRecord $record */
    $record = $getRecord();
    $side = (int) ($side ?? 1);
    $ranking = $side === 1 ? $record->side1Ranking : $record->side2Ranking;
    $users = collect([$ranking?->user, $ranking?->player2])->filter();
    $label = $ranking?->display_name ?? '—';
@endphp

<div class="fi-ta-text flex items-center gap-2 min-w-0">
    @if($users->isNotEmpty())
        <span class="inline-flex items-center -space-x-1.5 shrink-0">
            @foreach($users as $user)
                @if($url = $user->avatarPublicUrl())
                    <img src="{{ $url }}" alt="" title="{{ $user->publicName() }}" class="h-7 w-7 rounded-full object-cover ring-2 ring-white dark:ring-gray-900">
                @else
                    <span title="{{ $user->publicName() }}" class="flex h-7 w-7 items-center justify-center rounded-full bg-gray-100 text-[10px] font-semibold text-gray-600 ring-2 ring-white dark:bg-white/10 dark:text-gray-300 dark:ring-gray-900">
                        {{ $user->initials() }}
                    </span>
                @endif
            @endforeach
        </span>
    @endif
    <span class="fi-ta-text-item-label text-sm leading-6 text-gray-950 dark:text-white truncate">
        {{ $label }}
    </span>
</div>
