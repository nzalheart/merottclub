@extends('layouts.app')

@section('title', 'Ranked match')

@section('content')
@php
    $list = $challenge->rankingList;
    $me = $challenge->participantFor((int) auth()->id());
    $validScores = $challenge->hasValidScores();
    $setRows = old('sets', $challenge->scores ?? []);
    $setCount = $list?->getTotalSetsCount() ?? 3;
    $setsToWin = $list?->getMaxSetsToWin() ?? 2;
    $sideOne = $challenge->sideMatrixLabel(1);
    $sideTwo = $challenge->sideMatrixLabel(2);
    $canAcceptScore = $me && $validScores && ! $challenge->participantAcceptedScore($me);
@endphp

<div class="space-y-4 lg:space-y-6">
    <div class="flex items-start justify-between gap-3">
        <div class="min-w-0">
            <div class="text-xs uppercase tracking-wider text-muted font-semibold">Ranked match</div>
            <h2 class="text-xl lg:text-2xl font-bold font-heading text-ink leading-tight mt-1">
                {{ $list?->title }}
            </h2>
            <p class="text-xs text-muted mt-1">
                {{ strtoupper((string) $challenge->match_type) }}
                · {{ strtoupper(str_replace('_', ' ', (string) $list?->format)) }}
            </p>
        </div>
        <span class="shrink-0 px-2 py-1 rounded-md text-[10px] font-bold uppercase border {{ $challenge->status === 'completed' ? 'bg-secondary-soft text-secondary border-secondary' : 'bg-primary-soft text-primary border-primary' }}">
            {{ $challenge->phaseLabel() }}
        </span>
    </div>

    @if($errors->any())
        <div class="rounded-xl border border-primary bg-primary-soft px-4 py-3 text-sm text-primary">
            {{ $errors->first() }}
        </div>
    @endif

    @if($challenge->isOpen())
        <div class="rounded-xl border border-secondary bg-secondary-soft px-4 py-3 text-sm text-secondary">
            This page does not update live. Refresh it after someone accepts. Share this link with the other participants if they do not see the request yet.
            <input readonly value="{{ route('matches.challenge', $challenge) }}" onclick="this.select()" class="mt-2 w-full rounded-lg border border-secondary/30 bg-white px-3 py-2 text-xs text-ink">
        </div>
    @endif

    <div class="surface-card rounded-2xl p-4 lg:p-5 space-y-4">
        <div class="flex items-start justify-between gap-3">
            <div class="min-w-0 text-sm">
                <span class="text-[10px] uppercase tracking-wider text-muted font-semibold">Referee</span>
                <a href="{{ route('player.profile', $challenge->referee_id) }}" class="ml-2 font-semibold text-ink hover:text-secondary">
                    {{ $challenge->referee?->publicName() }}
                </a>
            </div>
            @if($challenge->isOpen())
                <form method="POST" action="{{ route('matches.challenge.cancel', $challenge) }}" class="shrink-0" onsubmit="return confirm('Cancel this match? It will not be added to the rankings.')">
                    @csrf
                    <button type="submit" class="inline-flex items-center justify-center px-3 py-1.5 rounded-xl text-xs font-semibold border border-theme text-muted hover:text-primary hover:border-primary transition">
                        Cancel match
                    </button>
                </form>
            @endif
        </div>
        @if($challenge->status === 'completed')
            @include('matches.partials.score-matrix', [
                'editable' => false,
                'side1' => $sideOne,
                'side2' => $sideTwo,
                'sets' => $challenge->scores ?? [],
            ])
            @include('matches.partials.score-acceptance')
            <a href="{{ route('leaderboard', ['list_id' => $challenge->ranking_list_id]) }}" class="inline-flex text-sm font-semibold text-secondary hover:underline">
                View the ranking list
            </a>
        @elseif($challenge->isOpen())
            <form method="POST" action="{{ route('matches.challenge.scores', $challenge) }}" class="space-y-4">
                @csrf
                <p class="text-[11px] text-muted">
                    First side to win {{ $setsToWin }} sets. Each set is first to 11, win by 2. Use + to add a set.
                </p>

                @include('matches.partials.score-matrix', [
                    'editable' => true,
                    'side1' => $sideOne,
                    'side2' => $sideTwo,
                    'sets' => $setRows,
                    'minSets' => $setsToWin,
                    'maxSets' => $setCount,
                    'rating1' => $challenge->sideRating(1),
                    'rating2' => $challenge->sideRating(2),
                    'kFactor' => (int) ($list->k_factor ?? 32),
                ])

                <div class="flex flex-wrap gap-2">
                    <button type="submit" name="intent" value="save" class="inline-flex items-center justify-center px-4 py-2.5 rounded-xl text-sm font-semibold border border-secondary text-secondary bg-secondary-soft hover:bg-secondary hover:text-white transition">
                        {{ $challenge->scoreButtonLabel() }}
                    </button>
                </div>
            </form>

            @if($challenge->hasSavedScores())
                @include('matches.partials.score-acceptance')
            @endif

            @if($challenge->hasSavedScores() && ! $validScores)
                <p class="text-[11px] text-muted">A finished match has to be saved before anyone can confirm it. First side to {{ $setsToWin }} sets.</p>
            @elseif($me && $challenge->participantAcceptedScore($me))
                <p class="text-[11px] text-secondary font-medium">You accepted this score. If the scores change, you will need to accept again.</p>
            @elseif(! $me)
                <p class="text-[11px] text-muted">You can enter scores. Only the players and the referee can accept them.</p>
            @endif

            @if($canAcceptScore)
                <form method="POST" action="{{ route('matches.challenge.accept-score', $challenge) }}">
                    @csrf
                    <button type="submit" class="inline-flex items-center justify-center px-4 py-2.5 rounded-xl text-sm font-semibold bg-primary text-white hover:opacity-90 transition">
                        Accept score
                    </button>
                </form>
            @endif
        @else
            <p class="text-sm text-muted">This match was {{ $challenge->status }} and was not added to the rankings.</p>
            @if($challenge->hasSavedScores())
                @include('matches.partials.score-acceptance')
            @endif
        @endif
    </div>
</div>
@endsection
