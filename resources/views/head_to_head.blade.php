@extends('layouts.app')

@section('title', 'Head to Head Comparison')

@section('content')
<div class="space-y-5 lg:space-y-8 max-w-3xl lg:max-w-none mx-auto">
    <div>
        <h2 class="text-xl lg:text-2xl font-bold font-heading text-ink">Head-to-Head Lookup</h2>
        <p class="text-xs lg:text-sm text-muted">Compare records & match history between two players</p>
    </div>

    <!-- Select Competitors Form -->
    <form action="{{ route('head-to-head') }}" method="GET" class="surface-card rounded-2xl p-4 lg:p-6 space-y-3 lg:space-y-4 lg:max-w-3xl">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 lg:gap-4">
            <div>
                <label class="block text-[10px] uppercase font-bold text-secondary mb-1">Player 1</label>
                <select name="p1" class="w-full bg-white border border-theme rounded-xl px-3 py-2.5 text-xs lg:text-sm text-ink focus:outline-none focus:border-secondary">
                    <option value="">Select Player 1</option>
                    @foreach($players as $p)
                        <option value="{{ $p->id }}" {{ request('p1') == $p->id ? 'selected' : '' }}>{{ $p->name }} {{ $p->nickname ? '(@' . $p->nickname . ')' : '' }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-[10px] uppercase font-bold text-secondary mb-1">Player 2</label>
                <select name="p2" class="w-full bg-white border border-theme rounded-xl px-3 py-2.5 text-xs lg:text-sm text-ink focus:outline-none focus:border-secondary">
                    <option value="">Select Player 2</option>
                    @foreach($players as $p)
                        <option value="{{ $p->id }}" {{ request('p2') == $p->id ? 'selected' : '' }}>{{ $p->name }} {{ $p->nickname ? '(@' . $p->nickname . ')' : '' }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <button type="submit" class="w-full sm:w-auto sm:px-8 py-2.5 rounded-xl bg-primary hover:opacity-90 text-white font-heading font-bold text-xs lg:text-sm shadow-primary transition">
            Compare Head-to-Head
        </button>
    </form>

    @if($player1 && $player2)
        <div class="lg:grid lg:grid-cols-12 lg:gap-8 lg:items-start space-y-5 lg:space-y-0">
            <div class="surface-card rounded-2xl p-5 lg:p-8 text-center space-y-3 lg:col-span-5 lg:sticky lg:top-24 relative overflow-hidden">
                <div class="absolute inset-x-0 top-0 h-1.5" style="background: linear-gradient(90deg, var(--color-primary), var(--color-secondary));"></div>
                <span class="text-[10px] uppercase tracking-widest text-muted font-bold">ALL-TIME HEAD-TO-HEAD</span>
                
                <div class="grid grid-cols-3 items-center gap-2">
                    <div class="min-w-0 flex flex-col items-center">
                        <x-avatar :user="$player1" size="lg" />
                        <div class="font-heading font-extrabold text-base lg:text-lg text-ink truncate mt-2 max-w-full">{{ $player1->name }}</div>
                        @if($player1->nickname)
                            <div class="text-[10px] text-primary truncate">{{ '@' . $player1->nickname }}</div>
                        @endif
                        <div class="text-2xl lg:text-4xl font-black font-heading text-primary mt-1">{{ $p1Wins }}</div>
                        <div class="text-[10px] text-muted">Wins</div>
                    </div>

                    <div class="text-xs font-bold text-muted uppercase tracking-widest">
                        VS
                    </div>

                    <div class="min-w-0 flex flex-col items-center">
                        <x-avatar :user="$player2" size="lg" />
                        <div class="font-heading font-extrabold text-base lg:text-lg text-ink truncate mt-2 max-w-full">{{ $player2->name }}</div>
                        @if($player2->nickname)
                            <div class="text-[10px] text-secondary truncate">{{ '@' . $player2->nickname }}</div>
                        @endif
                        <div class="text-2xl lg:text-4xl font-black font-heading text-secondary mt-1">{{ $p2Wins }}</div>
                        <div class="text-[10px] text-muted">Wins</div>
                    </div>
                </div>
            </div>

            <div class="space-y-3 lg:col-span-7">
                <h3 class="text-xs uppercase tracking-wider text-muted font-semibold px-1">Matches Between Them ({{ $matches->count() }})</h3>

                <div class="space-y-2.5">
                    @forelse($matches as $m)
                        <div class="surface-card rounded-xl p-3 lg:p-4">
                            <div class="flex items-center justify-between text-[10px] lg:text-xs text-muted mb-2 gap-2">
                                <span class="truncate">{{ $m->rankingList?->title }}</span>
                                <span class="shrink-0">{{ $m->played_at ? $m->played_at->format('M d, Y') : '' }}</span>
                            </div>

                            <div class="flex items-center justify-between font-heading font-bold text-sm gap-2">
                                <span class="{{ $m->winner_side === 1 ? 'text-primary' : 'text-ink' }} min-w-0">
                                    <x-ranking-name :ranking="$m->side1Ranking" size="xs" class="font-heading font-bold text-sm" />
                                    <span class="ml-1 tabular-nums">({{ $m->side1_sets_won }})</span>
                                </span>
                                <span class="text-xs text-muted px-2 shrink-0">ft</span>
                                <span class="{{ $m->winner_side === 2 ? 'text-primary' : 'text-ink' }} min-w-0 text-right">
                                    <span class="mr-1 tabular-nums">({{ $m->side2_sets_won }})</span>
                                    <x-ranking-name :ranking="$m->side2Ranking" size="xs" align="right" class="font-heading font-bold text-sm" />
                                </span>
                            </div>
                        </div>
                    @empty
                        <div class="surface-card rounded-xl p-4 text-center text-xs text-muted">
                            No matches recorded between {{ $player1->name }} and {{ $player2->name }} yet.
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    @endif

</div>
@endsection
