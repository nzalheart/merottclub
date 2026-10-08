@extends('layouts.app')

@section('title', $user->name . ' - Player Profile')

@section('content')
<div class="space-y-5 lg:space-y-8">
    
    <!-- Player Profile Card Header -->
    <div class="surface-card rounded-2xl p-5 lg:p-8 relative overflow-hidden">
        <div class="absolute inset-x-0 top-0 h-1.5" style="background: linear-gradient(90deg, var(--color-primary), var(--color-secondary));"></div>
        <div class="flex flex-col sm:flex-row sm:items-center gap-4 relative z-10">
            <div class="w-16 h-16 lg:w-20 lg:h-20 rounded-2xl p-0.5 shadow-sm shrink-0" style="background: linear-gradient(135deg, var(--color-primary), var(--color-secondary));">
                <div class="w-full h-full bg-white rounded-[14px] flex items-center justify-center font-heading font-black text-2xl lg:text-3xl text-secondary">
                    {{ strtoupper(substr($user->name, 0, 2)) }}
                </div>
            </div>

            <div class="min-w-0 flex-1">
                <div class="flex items-center gap-2 flex-wrap">
                    <h2 class="text-xl lg:text-3xl font-bold font-heading text-ink">{{ $user->name }}</h2>
                    @if($user->nickname)
                        <span class="px-2 py-0.5 rounded-md text-xs font-semibold bg-primary-soft text-primary border border-primary">
                            {{ '@' . $user->nickname }}
                        </span>
                    @endif
                </div>
                <p class="text-xs lg:text-sm text-muted mt-0.5 truncate">{{ $user->email }}</p>
                <div class="flex items-center gap-2 mt-2 flex-wrap">
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase bg-secondary-soft text-secondary border border-secondary">
                        {{ strtoupper($user->status) }} PLAYER
                    </span>
                    @if($user->address)
                        <span class="text-xs text-muted flex items-center gap-1">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path></svg>
                            {{ $user->address }}
                        </span>
                    @endif
                </div>
            </div>
        </div>

        @if($user->date_of_birth)
            <div class="mt-4 pt-3 border-t border-theme text-xs text-muted flex items-center justify-between">
                <span>Date of Birth: <strong class="text-ink">{{ $user->date_of_birth->format('M d, Y') }}</strong></span>
                <span class="text-[10px] text-muted bg-surface px-2 py-0.5 rounded-md border border-theme">Public Profile</span>
            </div>
        @endif
    </div>

    <div class="lg:grid lg:grid-cols-2 lg:gap-8 lg:items-start space-y-5 lg:space-y-0">
        <div class="space-y-3">
            <h3 class="text-xs uppercase tracking-wider text-muted font-semibold px-1">ELO Ratings Across Lists</h3>
            
            <div class="grid grid-cols-1 gap-2.5">
                @forelse($rankings as $r)
                    <div class="surface-card rounded-2xl p-4 flex items-center justify-between gap-3">
                        <div class="min-w-0">
                            <span class="text-xs font-medium text-secondary block truncate">{{ $r->rankingList?->title }}</span>
                            <div class="text-xs text-muted flex items-center gap-2 mt-1 flex-wrap">
                                <span>Matches: <strong class="text-ink">{{ $r->matches_played }}</strong></span>
                                <span>•</span>
                                <span>Win Rate: <strong class="text-primary">{{ $r->win_rate }}%</strong></span>
                            </div>
                        </div>

                        <div class="text-right shrink-0">
                            <span class="px-3 py-1 rounded-xl bg-secondary-soft text-secondary font-heading font-extrabold text-base border border-secondary">
                                {{ $r->elo_rating }} ELO
                            </span>
                            <div class="text-[10px] text-muted mt-1">
                                W: {{ $r->wins }} / L: {{ $r->losses }}
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="surface-card rounded-2xl p-4 text-center text-xs text-muted">
                        No active ranking list entries yet.
                    </div>
                @endforelse
            </div>
        </div>

        <div class="space-y-3">
            <h3 class="text-xs uppercase tracking-wider text-muted font-semibold px-1">Recent Matches</h3>

            <div class="space-y-2.5">
                @forelse($matches as $m)
                    @php
                        $isSide1 = $m->side1Ranking?->user_id === $user->id;
                        $userSets = $isSide1 ? $m->side1_sets_won : $m->side2_sets_won;
                        $oppSets = $isSide1 ? $m->side2_sets_won : $m->side1_sets_won;
                        $opponentName = $isSide1 ? $m->side2Ranking?->display_name : $m->side1Ranking?->display_name;
                        $wonMatch = ($isSide1 && $m->winner_side === 1) || (!$isSide1 && $m->winner_side === 2);
                    @endphp
                    <div class="surface-card rounded-xl p-3 flex items-center justify-between gap-3">
                        <div class="flex items-center gap-3 min-w-0">
                            <span class="w-7 h-7 shrink-0 rounded-lg font-heading font-bold text-xs flex items-center justify-center {{ $wonMatch ? 'bg-primary-soft text-primary border border-primary' : 'bg-surface text-muted border border-theme' }}">
                                {{ $wonMatch ? 'W' : 'L' }}
                            </span>
                            <div class="min-w-0">
                                <div class="text-xs font-bold text-ink truncate">vs {{ $opponentName }}</div>
                                <div class="text-[10px] text-muted truncate">{{ $m->rankingList?->title }}</div>
                            </div>
                        </div>

                        <div class="text-right shrink-0">
                            <div class="font-mono text-sm font-bold {{ $wonMatch ? 'text-primary' : 'text-ink' }}">
                                {{ $userSets }} - {{ $oppSets }}
                            </div>
                            <div class="text-[10px] text-muted">
                                {{ $m->played_at ? $m->played_at->format('M d') : '' }}
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="surface-card rounded-xl p-4 text-center text-xs text-muted">
                        No matches played yet.
                    </div>
                @endforelse
            </div>
        </div>
    </div>

</div>
@endsection
