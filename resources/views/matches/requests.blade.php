@extends('layouts.app')

@section('title', 'Match requests')

@section('content')
<div class="space-y-4 lg:space-y-6 max-w-3xl">
    <div>
        <h2 class="text-xl lg:text-2xl font-bold font-heading text-ink">Match requests</h2>
        <p class="text-xs lg:text-sm text-muted mt-1">
            A match shows here as soon as it starts. Refresh to see a new one — updates are not live.
        </p>
    </div>

    @forelse($challenges as $challenge)
        @php
            $me = $challenge->participantFor((int) auth()->id());
        @endphp
        <a href="{{ route('matches.challenge', $challenge) }}" class="surface-card rounded-2xl p-4 lg:p-5 block hover:bg-surface transition">
            <div class="flex items-start justify-between gap-3">
                <div class="min-w-0">
                    <div class="text-xs font-semibold text-secondary truncate">{{ $challenge->rankingList?->title }}</div>
                    <div class="font-heading font-bold text-ink mt-1">
                        {{ $challenge->sideLabel(1) }}
                        <span class="text-muted font-semibold">vs</span>
                        {{ $challenge->sideLabel(2) }}
                    </div>
                    <div class="text-xs text-muted mt-1">
                        Referee: {{ $challenge->referee?->publicName() }}
                    </div>
                </div>
                <div class="text-right shrink-0">
                    @if($challenge->needsActionFrom((int) auth()->id()))
                        <span class="inline-flex px-2 py-0.5 rounded-md text-[10px] font-bold uppercase bg-primary-soft text-primary border border-primary">Action needed</span>
                    @endif
                    <div class="text-[11px] text-muted mt-1">{{ $challenge->phaseLabel() }}</div>
                </div>
            </div>
            @if($challenge->hasSavedScores())
                <div class="mt-3">
                    @include('matches.partials.score-matrix', [
                        'editable' => false,
                        'side1' => $challenge->sideMatrixLabel(1),
                        'side2' => $challenge->sideMatrixLabel(2),
                        'sets' => $challenge->scores ?? [],
                    ])
                </div>
            @endif
            @if($challenge->hasSavedScores())
                <div class="mt-3">
                    @include('matches.partials.score-acceptance', ['linkNames' => false])
                </div>
            @endif
            @if($me && $challenge->hasValidScores() && ! $challenge->participantAcceptedScore($me))
                <div class="mt-3 text-xs font-semibold text-primary">Accept the score</div>
            @endif
        </a>
    @empty
        <div class="surface-card rounded-2xl p-8 text-center text-sm text-muted">
            You have no open match requests.
        </div>
    @endforelse
</div>
@endsection
