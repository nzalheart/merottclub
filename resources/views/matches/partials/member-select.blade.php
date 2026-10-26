<select
    id="{{ $name }}"
    name="{{ $name }}"
    required
    class="w-full rounded-xl border border-theme bg-white px-3 py-2.5 text-sm text-ink focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary"
>
    <option value="">Select a member</option>
    @foreach($members as $member)
        @if(in_array($member->id, $exclude ?? [], true))
            @continue
        @endif
        <option value="{{ $member->id }}" @selected((int) $selected === (int) $member->id)>
            {{ $member->publicName() }}@if(isset($elos[$member->id])) · {{ $elos[$member->id] }} ELO @else · unranked @endif
        </option>
    @endforeach
</select>
