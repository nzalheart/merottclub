@php
    $linkNames = $linkNames ?? true;
    $parties = $challenge->sidePlayers(1)->concat($challenge->sidePlayers(2));
    $refereeParty = $challenge->participantFor((int) $challenge->referee_id);
    if ($refereeParty) {
        $parties->push($refereeParty);
    }
@endphp
<div class="space-y-2">
    <div class="text-[10px] uppercase tracking-wider text-muted font-semibold">Score acceptance</div>
    <ul class="rounded-xl border border-theme">
        @foreach($parties as $participant)
            <li class="flex items-center justify-between gap-3 px-3 py-2 text-sm border-b border-theme last:border-b-0">
                <div class="min-w-0 flex items-center gap-2">
                    <x-player-name
                        :user="$participant->user"
                        :link="$linkNames"
                        size="sm"
                        class="font-semibold text-ink"
                    />
                    @if($participant->role === \App\Models\MatchChallengeParticipant::ROLE_REFEREE)
                        <span class="text-[10px] uppercase tracking-wider text-muted font-semibold">Referee</span>
                    @endif
                </div>
                @if($challenge->participantAcceptedScore($participant))
                    <span class="shrink-0 inline-flex px-2 py-0.5 rounded-md text-[10px] font-bold uppercase bg-secondary-soft text-secondary border border-secondary">Score accepted</span>
                @else
                    <span class="shrink-0 inline-flex px-2 py-0.5 rounded-md text-[10px] font-bold uppercase bg-primary-soft text-primary border border-primary">Pending</span>
                @endif
            </li>
        @endforeach
    </ul>
</div>
