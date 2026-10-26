@extends('layouts.app')

@section('title', 'ELO Rankings')

@section('content')
<div class="space-y-4 lg:space-y-6">
    
    <!-- Ranking List Filter Pills -->
    <div>
        <div class="flex items-center justify-between mb-2">
            <h2 class="text-xs uppercase tracking-wider text-muted font-semibold">Select Ranking List</h2>
            <span class="text-xs text-secondary font-medium">{{ $rankingLists->count() }} Lists</span>
        </div>
        
        <div class="flex flex-wrap gap-2">
            @foreach($rankingLists as $list)
                @php
                    $listParams = array_filter([
                        'list_id' => $list->id,
                        'view' => $list->type === 'doubles' ? ($viewMode ?? 'individual') : null,
                        'search' => $search !== '' ? $search : null,
                    ]);
                @endphp
                <a href="{{ route('leaderboard', $listParams) }}" 
                   class="px-3 py-1.5 rounded-lg text-xs font-semibold whitespace-nowrap transition border {{ $currentList && $currentList->id === $list->id ? 'pill-active' : 'pill-idle' }}">
                    {{ $list->title }}
                </a>
            @endforeach
        </div>
    </div>

    @if($currentList)
    <!-- Player search -->
    <form method="GET" action="{{ route('leaderboard') }}" class="relative">
        <input type="hidden" name="list_id" value="{{ $currentList->id }}">
        @if($currentList->type === 'doubles')
            <input type="hidden" name="view" value="{{ $viewMode ?? 'individual' }}">
        @endif
        <div class="relative">
            <svg class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M11 18a7 7 0 100-14 7 7 0 000 14z"></path>
            </svg>
            <input
                type="search"
                name="search"
                value="{{ $search }}"
                placeholder="Search by name or nickname…"
                autocomplete="off"
                class="w-full rounded-xl border border-theme bg-surface pl-10 pr-10 py-2.5 text-sm text-ink placeholder:text-muted focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary"
            >
            @if($search !== '')
                <a href="{{ route('leaderboard', array_filter(['list_id' => $currentList->id, 'view' => $currentList->type === 'doubles' ? ($viewMode ?? 'individual') : null])) }}"
                   class="absolute right-3 top-1/2 -translate-y-1/2 text-muted hover:text-ink text-xs font-semibold"
                   aria-label="Clear search">
                    Clear
                </a>
            @endif
        </div>
    </form>

    <div class="lg:grid lg:grid-cols-12 lg:gap-6 lg:items-start">
        <!-- Active Ranking List Info -->
        <div class="surface-card rounded-xl px-3.5 py-3 lg:p-4 lg:col-span-4 lg:sticky lg:top-24 mb-4 lg:mb-0 overflow-hidden relative">
            <div class="absolute inset-y-0 left-0 w-1 bg-primary"></div>
            <div class="pl-1.5">
                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-bold tracking-wide uppercase bg-secondary-soft text-secondary border border-secondary">
                    {{ strtoupper($currentList->type) }} • {{ strtoupper(str_replace('_', ' ', $currentList->format)) }}
                </span>
                <h2 class="text-base lg:text-xl font-bold font-heading text-ink leading-snug mt-1.5">{{ $currentList->title }}</h2>
                <p class="text-xs text-muted mt-0.5 leading-relaxed line-clamp-2">{{ $currentList->description ?? 'Official ELO Leaderboard' }}</p>

                @auth
                    <a href="{{ route('matches.start', ['list_id' => $currentList->id]) }}" class="mt-3 inline-flex w-full items-center justify-center px-3 py-2 rounded-lg text-xs font-semibold bg-primary text-white hover:opacity-90 transition">
                        Start match
                    </a>
                @endauth

                @if($currentList->type === 'doubles')
                    <div class="mt-3 flex flex-wrap gap-1.5">
                        <a href="{{ route('leaderboard', array_filter(['list_id' => $currentList->id, 'view' => 'individual', 'search' => $search !== '' ? $search : null])) }}"
                           class="px-2.5 py-1 rounded-md text-[11px] font-semibold border transition {{ ($viewMode ?? 'individual') === 'individual' ? 'pill-active' : 'pill-idle' }}">
                            Individual
                        </a>
                        <a href="{{ route('leaderboard', array_filter(['list_id' => $currentList->id, 'view' => 'pair', 'search' => $search !== '' ? $search : null])) }}"
                           class="px-2.5 py-1 rounded-md text-[11px] font-semibold border transition {{ ($viewMode ?? 'individual') === 'pair' ? 'pill-active' : 'pill-idle' }}">
                            Pairs
                        </a>
                    </div>
                @endif

                <div class="mt-3 pt-2.5 border-t border-theme hidden lg:flex items-center justify-between text-xs text-muted">
                    <span>{{ $rankings->total() }} {{ ($viewMode ?? 'individual') === 'pair' ? 'pairs' : 'competitors' }}</span>
                    <span class="text-secondary font-medium">{{ $search !== '' ? 'Filtered' : 'Live standings' }}</span>
                </div>
            </div>
        </div>

        <!-- Compact standings list (table-like rows) -->
        <div class="lg:col-span-8">
            <div class="surface-card rounded-xl overflow-hidden">
                <div class="grid grid-cols-[2.25rem_minmax(0,1fr)_auto_auto] sm:grid-cols-[2.5rem_minmax(0,1fr)_5.5rem_4.5rem_3.5rem_4.75rem] gap-x-2 sm:gap-x-3 items-center px-3 py-2 bg-surface border-b border-theme text-[10px] font-semibold uppercase tracking-wider text-muted">
                    <span class="text-center">#</span>
                    <span>{{ ($viewMode ?? 'individual') === 'pair' ? 'Pair' : 'Player' }}</span>
                    <span class="hidden sm:block text-right">Record</span>
                    <span class="hidden sm:block text-right">Win %</span>
                    <span class="text-right">Streak</span>
                    <span class="text-right">ELO</span>
                </div>

                @forelse($rankings as $index => $ranking)
                    @php
                        $rank = $rankings->firstItem() + $index;
                        $badgeBg = match($rank) {
                            1 => 'bg-primary text-white',
                            2 => 'bg-secondary text-white',
                            3 => 'bg-primary-soft text-primary',
                            default => 'text-muted',
                        };
                        $isPairRow = $ranking->isPair();
                        $profileUrl = (! $isPairRow && $ranking->user) ? route('player.profile', $ranking->user->id) : null;
                        $streak = (int) $ranking->current_streak;
                        $streakLabel = $ranking->streak_label;
                        $streakClass = $streak > 0
                            ? 'bg-primary-soft text-primary border-primary'
                            : ($streak < 0 ? 'bg-surface text-muted border-theme' : 'text-muted');
                    @endphp

                    @if($profileUrl)
                    <a href="{{ $profileUrl }}"
                       class="grid grid-cols-[2.25rem_minmax(0,1fr)_auto_auto] sm:grid-cols-[2.5rem_minmax(0,1fr)_5.5rem_4.5rem_3.5rem_4.75rem] gap-x-2 sm:gap-x-3 items-center px-3 py-2 border-b border-theme last:border-b-0 hover:bg-surface transition group">
                    @else
                    <div class="grid grid-cols-[2.25rem_minmax(0,1fr)_auto_auto] sm:grid-cols-[2.5rem_minmax(0,1fr)_5.5rem_4.5rem_3.5rem_4.75rem] gap-x-2 sm:gap-x-3 items-center px-3 py-2 border-b border-theme last:border-b-0 hover:bg-surface transition group">
                    @endif
                        
                        <span class="w-6 h-6 sm:w-7 sm:h-7 mx-auto rounded-md flex items-center justify-center font-heading font-bold text-[11px] sm:text-xs {{ $badgeBg }}">
                            {{ $rank }}
                        </span>

                        <div class="min-w-0">
                            <div class="font-heading font-semibold text-sm text-ink group-hover:text-secondary transition truncate leading-tight">
                                {{ $ranking->display_name }}
                            </div>
                            @if($isPairRow && $ranking->user && $ranking->player2)
                                <div class="text-[10px] text-muted mt-0.5 truncate">
                                    <a href="{{ route('player.profile', $ranking->user->id) }}" class="hover:text-secondary">{{ $ranking->user->name }}</a>
                                    &amp;
                                    <a href="{{ route('player.profile', $ranking->player2->id) }}" class="hover:text-secondary">{{ $ranking->player2->name }}</a>
                                </div>
                            @endif
                            <div class="sm:hidden text-[10px] text-muted mt-0.5 truncate">
                                {{ $ranking->wins }}W–{{ $ranking->losses }}L · <span class="text-primary font-medium">{{ $ranking->win_rate }}%</span> · {{ $ranking->matches_played }} played
                            </div>
                        </div>

                        <div class="hidden sm:block text-right text-xs text-muted tabular-nums">
                            {{ $ranking->wins }}–{{ $ranking->losses }}
                            <span class="text-[10px] block text-slate-400">{{ $ranking->matches_played }} m</span>
                        </div>

                        <div class="hidden sm:block text-right text-xs font-semibold text-primary tabular-nums">
                            {{ $ranking->win_rate }}%
                        </div>

                        <div class="text-right">
                            @if($streakLabel)
                                <span class="inline-flex items-center justify-center min-w-[2rem] px-1.5 py-0.5 rounded-md text-[10px] font-bold border tabular-nums {{ $streakClass }}">
                                    {{ $streakLabel }}
                                </span>
                            @else
                                <span class="text-[10px] text-muted tabular-nums">—</span>
                            @endif
                        </div>

                        <div class="text-right">
                            <span class="inline-flex items-center justify-end min-w-[3.25rem] font-heading font-extrabold text-sm text-secondary tabular-nums">
                                {{ $ranking->elo_rating }}
                            </span>
                        </div>
                    @if($profileUrl)
                    </a>
                    @else
                    </div>
                    @endif
                @empty
                    <div class="px-4 py-8 text-center text-muted text-sm">
                        @if($search !== '')
                            No players match “{{ $search }}”.
                        @else
                            No rankings registered for this list yet.
                        @endif
                    </div>
                @endforelse
            </div>

            @if($rankings->hasPages())
                <div class="mt-3">
                    {{ $rankings->links() }}
                </div>
            @endif
        </div>
    </div>
    @endif

</div>
@endsection
