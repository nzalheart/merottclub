@php
    /** @var \App\Models\MatchChallenge $record */
    $record = $getRecord();
    $parties = $record->sidePlayers(1)->concat($record->sidePlayers(2));
    $refereeParty = $record->participantFor((int) $record->referee_id);
    if ($refereeParty) {
        $parties->push($refereeParty);
    }
@endphp
@if(! $record->hasSavedScores())
    <span class="text-sm text-gray-500 dark:text-gray-400">Waiting for scores</span>
@else
    <ul class="space-y-1 min-w-[12rem]">
        @foreach($parties as $participant)
            @php
                $accepted = $record->participantAcceptedScore($participant);
            @endphp
            <li class="flex items-center justify-between gap-2 text-xs">
                <span class="truncate text-gray-700 dark:text-gray-200">
                    {{ $participant->user?->publicName() ?? 'Player' }}
                    @if($participant->role === \App\Models\MatchChallengeParticipant::ROLE_REFEREE)
                        <span class="text-gray-400">(ref)</span>
                    @endif
                </span>
                @if($accepted)
                    <span class="shrink-0 rounded-md bg-success-50 px-1.5 py-0.5 text-[10px] font-bold uppercase text-success-700 ring-1 ring-inset ring-success-600/20 dark:bg-success-400/10 dark:text-success-400 dark:ring-success-400/20">
                        Accepted
                    </span>
                @else
                    <span class="shrink-0 rounded-md bg-warning-50 px-1.5 py-0.5 text-[10px] font-bold uppercase text-warning-700 ring-1 ring-inset ring-warning-600/20 dark:bg-warning-400/10 dark:text-warning-400 dark:ring-warning-400/20">
                        Pending
                    </span>
                @endif
            </li>
        @endforeach
    </ul>
@endif
