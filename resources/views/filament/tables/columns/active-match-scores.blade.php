@php
    /** @var \App\Models\MatchChallenge $record */
    $record = $getRecord();
    $sets = array_values($record->scores ?? []);
    $tally = $record->setTally();
@endphp
@if($sets === [])
    <span class="text-sm text-gray-500 dark:text-gray-400">No scores yet</span>
@else
    <div class="space-y-1.5">
        @if($tally)
            <div class="text-sm font-semibold text-gray-950 dark:text-white">
                Sets {{ $tally[0] }}–{{ $tally[1] }}
            </div>
        @endif
        <div class="flex flex-wrap gap-1.5">
            @foreach($sets as $index => $set)
                <span class="inline-flex items-center rounded-md bg-gray-50 px-2 py-0.5 text-xs font-medium text-gray-700 ring-1 ring-inset ring-gray-200 dark:bg-white/5 dark:text-gray-200 dark:ring-white/10">
                    S{{ $index + 1 }} {{ (int) ($set['side1_score'] ?? 0) }}–{{ (int) ($set['side2_score'] ?? 0) }}
                </span>
            @endforeach
        </div>
    </div>
@endif
