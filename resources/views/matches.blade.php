@extends('layouts.app')

@section('title', 'Match History')

@section('content')
<div class="space-y-4 lg:space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-xl lg:text-2xl font-bold font-heading text-ink">Match History</h2>
            <p class="text-xs lg:text-sm text-muted">Completed ranking matches & score breakdowns</p>
        </div>
    </div>

    <!-- Filter by Ranking List -->
    <div class="flex flex-wrap gap-2">
        <a href="{{ route('matches') }}" 
           class="px-3 py-1.5 rounded-xl text-xs font-semibold whitespace-nowrap transition border {{ !$selectedListId ? 'pill-active' : 'pill-idle' }}">
            All Matches
        </a>
        @foreach($rankingLists as $list)
            <a href="{{ route('matches', ['list_id' => $list->id]) }}" 
               class="px-3 py-1.5 rounded-xl text-xs font-semibold whitespace-nowrap transition border {{ $selectedListId == $list->id ? 'pill-active' : 'pill-idle' }}">
                {{ $list->title }}
            </a>
        @endforeach
    </div>

    <!-- Matches Cards -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-3 lg:gap-4">
        @forelse($matches as $match)
            <div class="surface-card rounded-2xl p-4 lg:p-5 space-y-3">
                <div class="flex items-center justify-between text-xs text-muted gap-2">
                    <span class="px-2 py-0.5 rounded-md bg-secondary-soft text-secondary font-medium truncate border border-secondary">
                        {{ $match->rankingList?->title }}
                    </span>
                    <span class="shrink-0">{{ $match->played_at ? $match->played_at->diffForHumans() : $match->created_at->diffForHumans() }}</span>
                </div>

                <div class="grid grid-cols-5 items-center gap-2 py-2 border-y border-theme">
                    
                    <div class="col-span-2 flex items-center gap-2 min-w-0">
                        <div class="w-2.5 h-2.5 shrink-0 rounded-full {{ $match->winner_side === 1 ? 'bg-primary' : 'bg-slate-300' }}"></div>
                        <div class="min-w-0">
                            <div class="font-heading font-bold text-sm {{ $match->winner_side === 1 ? 'text-ink' : 'text-muted' }} truncate">
                                {{ $match->side1Ranking?->display_name }}
                            </div>
                            <div class="text-[10px] text-muted">
                                ELO {{ $match->side1_elo_before }} → <span class="font-bold text-primary">{{ $match->side1_elo_after }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="col-span-1 text-center font-heading font-extrabold text-lg tracking-wider">
                        <span class="{{ $match->winner_side === 1 ? 'text-primary' : 'text-ink' }}">{{ $match->side1_sets_won }}</span>
                        <span class="text-slate-300 px-1">-</span>
                        <span class="{{ $match->winner_side === 2 ? 'text-primary' : 'text-ink' }}">{{ $match->side2_sets_won }}</span>
                    </div>

                    <div class="col-span-2 text-right flex items-center justify-end gap-2 min-w-0">
                        <div class="min-w-0">
                            <div class="font-heading font-bold text-sm {{ $match->winner_side === 2 ? 'text-ink' : 'text-muted' }} truncate">
                                {{ $match->side2Ranking?->display_name }}
                            </div>
                            <div class="text-[10px] text-muted">
                                ELO {{ $match->side2_elo_before }} → <span class="font-bold text-primary">{{ $match->side2_elo_after }}</span>
                            </div>
                        </div>
                        <div class="w-2.5 h-2.5 shrink-0 rounded-full {{ $match->winner_side === 2 ? 'bg-primary' : 'bg-slate-300' }}"></div>
                    </div>

                </div>

                <div class="flex items-center gap-1.5 flex-wrap pt-1">
                    <span class="text-[10px] uppercase tracking-wider text-muted font-semibold mr-1">Sets:</span>
                    @foreach($match->sets as $set)
                        <span class="px-2 py-0.5 rounded-lg text-xs font-mono font-medium bg-surface text-ink border border-theme">
                            {{ $set->side1_score }}-{{ $set->side2_score }}
                        </span>
                    @endforeach
                </div>
            </div>
        @empty
            <div class="surface-card rounded-2xl p-8 text-center text-muted text-sm lg:col-span-2">
                No completed matches found.
            </div>
        @endforelse
    </div>

    <div class="pt-2">
        {{ $matches->links() }}
    </div>
</div>
@endsection
