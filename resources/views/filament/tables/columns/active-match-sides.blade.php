@php
    /** @var \App\Models\MatchChallenge $record */
    $record = $getRecord();
@endphp
<div class="space-y-1 text-sm">
    <div class="font-medium text-gray-950 dark:text-white">
        {{ $record->sideLabel(1) }}
    </div>
    <div class="text-xs text-gray-500 dark:text-gray-400">vs</div>
    <div class="font-medium text-gray-950 dark:text-white">
        {{ $record->sideLabel(2) }}
    </div>
    <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
        @if($record->hasReferee())
            Referee: {{ $record->refereeLabel() }}
        @else
            {{ $record->refereeLabel() }}
        @endif
    </div>
</div>
