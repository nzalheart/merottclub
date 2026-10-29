@extends('layouts.app')

@section('title', 'Start a match')

@section('content')
<div class="space-y-4 lg:space-y-6 max-w-2xl">
    <div>
        <h2 class="text-xl lg:text-2xl font-bold font-heading text-ink">Start a ranked match</h2>
        <p class="text-xs lg:text-sm text-muted mt-1">
            Pick the opponent. A referee is optional. Without one, the match is added to the rankings after every player accepts the score.
        </p>
    </div>

    @if($errors->any())
        <div class="rounded-xl border border-primary bg-primary-soft px-4 py-3 text-sm text-primary">
            {{ $errors->first() }}
        </div>
    @endif

    @if($lists->isEmpty())
        <div class="surface-card rounded-2xl p-6 text-sm text-muted">
            There is no active ranking list yet.
        </div>
    @else
        <div>
            <h3 class="text-xs uppercase tracking-wider text-muted font-semibold mb-2">Ranking list</h3>
            <div class="flex flex-wrap gap-2">
                @foreach($lists as $item)
                    <a href="{{ route('matches.start', array_filter(['list_id' => $item->id, 'opponent' => $opponent?->id])) }}"
                       class="px-3 py-1.5 rounded-lg text-xs font-semibold whitespace-nowrap transition border {{ $list && $list->id === $item->id ? 'pill-active' : 'pill-idle' }}">
                        {{ $item->title }}
                    </a>
                @endforeach
            </div>
        </div>

        @if($list)
            <form method="POST" action="{{ route('matches.start.store') }}" class="surface-card rounded-2xl p-4 lg:p-5 space-y-4">
                @csrf
                <input type="hidden" name="ranking_list_id" value="{{ $list->id }}">

                <div class="flex items-center justify-between gap-3">
                    <div>
                        <div class="text-xs uppercase tracking-wider text-muted font-semibold">You</div>
                        <x-player-name :user="auth()->user()" :link="false" size="sm" class="font-heading font-semibold text-ink" />
                    </div>
                    <span class="px-2 py-0.5 rounded text-[10px] font-bold tracking-wide uppercase bg-secondary-soft text-secondary border border-secondary">
                        {{ strtoupper($list->type) }} · {{ strtoupper(str_replace('_', ' ', $list->format)) }}
                    </span>
                </div>

                @if($list->type === 'doubles')
                    <div>
                        <label for="partner_id" class="block text-xs font-semibold text-muted mb-1.5">Your partner</label>
                        @include('matches.partials.member-select', [
                            'name' => 'partner_id',
                            'members' => $members,
                            'elos' => $elos,
                            'selected' => old('partner_id'),
                            'exclude' => [auth()->id()],
                        ])
                    </div>
                    <div>
                        <label for="opponent_id" class="block text-xs font-semibold text-muted mb-1.5">Opponent</label>
                        @include('matches.partials.member-select', [
                            'name' => 'opponent_id',
                            'members' => $members,
                            'elos' => $elos,
                            'selected' => old('opponent_id', $opponent?->id),
                            'exclude' => [auth()->id()],
                        ])
                    </div>
                    <div>
                        <label for="opponent_partner_id" class="block text-xs font-semibold text-muted mb-1.5">Opponent's partner</label>
                        @include('matches.partials.member-select', [
                            'name' => 'opponent_partner_id',
                            'members' => $members,
                            'elos' => $elos,
                            'selected' => old('opponent_partner_id'),
                            'exclude' => [auth()->id()],
                        ])
                    </div>
                @else
                    <div>
                        <label for="opponent_id" class="block text-xs font-semibold text-muted mb-1.5">Opponent</label>
                        @include('matches.partials.member-select', [
                            'name' => 'opponent_id',
                            'members' => $members,
                            'elos' => $elos,
                            'selected' => old('opponent_id', $opponent?->id),
                            'exclude' => [auth()->id()],
                        ])
                    </div>
                @endif

                <div>
                    <label for="referee_id" class="block text-xs font-semibold text-muted mb-1.5">Referee <span class="font-medium">(optional)</span></label>
                    <p class="text-[11px] text-muted mb-1.5">The default is mutual consent, so the players confirm the score themselves. Choose a member if you want them to confirm it too. Admins can referee.</p>
                    @include('matches.partials.member-select', [
                        'name' => 'referee_id',
                        'members' => $referees,
                        'elos' => $elos,
                        'selected' => old('referee_id'),
                        'exclude' => [auth()->id()],
                        'blankLabel' => \App\Models\MatchChallenge::WITHOUT_REFEREE_LABEL,
                    ])
                </div>

                <button type="submit" class="w-full inline-flex items-center justify-center px-4 py-2.5 rounded-xl text-sm font-semibold bg-primary text-white hover:opacity-90 transition">
                    Send match request
                </button>
            </form>
        @endif
    @endif
</div>
@endsection
