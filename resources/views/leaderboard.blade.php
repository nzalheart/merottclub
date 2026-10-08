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
                <a href="{{ route('leaderboard', ['list_id' => $list->id]) }}" 
                   class="px-3 py-1.5 rounded-lg text-xs font-semibold whitespace-nowrap transition border {{ $currentList && $currentList->id === $list->id ? 'pill-active' : 'pill-idle' }}">
                    {{ $list->title }}
                </a>
            @endforeach
        </div>
    </div>

    @if($currentList)
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
                <div class="mt-3 pt-2.5 border-t border-theme hidden lg:flex items-center justify-between text-xs text-muted">
                    <span>{{ $rankings->total() }} competitors</span>
                    <span class="text-secondary font-medium">Live standings</span>
                </div>
            </div>
        </div>

        <!-- Compact standings list (table-like rows) -->
        <div class="lg:col-span-8">
            <div class="surface-card rounded-xl overflow-hidden">
                <div class="grid grid-cols-[2.25rem_minmax(0,1fr)_auto] sm:grid-cols-[2.5rem_minmax(0,1fr)_5.5rem_4.5rem_4.75rem] gap-x-2 sm:gap-x-3 items-center px-3 py-2 bg-surface border-b border-theme text-[10px] font-semibold uppercase tracking-wider text-muted">
                    <span class="text-center">#</span>
                    <span>Player</span>
                    <span class="hidden sm:block text-right">Record</span>
                    <span class="hidden sm:block text-right">Win %</span>
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
                    @endphp
                    
                    <a href="{{ $ranking->user ? route('player.profile', $ranking->user->id) : '#' }}" 
                       class="grid grid-cols-[2.25rem_minmax(0,1fr)_auto] sm:grid-cols-[2.5rem_minmax(0,1fr)_5.5rem_4.5rem_4.75rem] gap-x-2 sm:gap-x-3 items-center px-3 py-2 border-b border-theme last:border-b-0 hover:bg-surface transition group">
                        
                        <span class="w-6 h-6 sm:w-7 sm:h-7 mx-auto rounded-md flex items-center justify-center font-heading font-bold text-[11px] sm:text-xs {{ $badgeBg }}">
                            {{ $rank }}
                        </span>

                        <div class="min-w-0">
                            <div class="font-heading font-semibold text-sm text-ink group-hover:text-secondary transition truncate leading-tight">
                                {{ $ranking->display_name }}
                            </div>
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
                            <span class="inline-flex items-center justify-end min-w-[3.25rem] font-heading font-extrabold text-sm text-secondary tabular-nums">
                                {{ $ranking->elo_rating }}
                            </span>
                        </div>
                    </a>
                @empty
                    <div class="px-4 py-8 text-center text-muted text-sm">
                        No rankings registered for this list yet.
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
