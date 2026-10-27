@php
    $excludeIds = collect($exclude ?? [])->map(fn ($id) => (int) $id)->all();
    $options = $members
        ->reject(fn ($member) => in_array((int) $member->id, $excludeIds, true))
        ->map(fn ($member) => [
            'id' => (int) $member->id,
            'label' => $member->publicName().(isset($elos[$member->id]) ? ' · '.$elos[$member->id].' ELO' : ' · unranked'),
            'search' => mb_strtolower($member->name.' '.$member->nickname.' '.$member->publicName()),
        ])
        ->values()
        ->all();
    $selectedId = $selected !== null && $selected !== '' ? (int) $selected : null;
@endphp

<div
    class="relative"
    x-data="{
        open: false,
        query: '',
        selectedId: {{ $selectedId !== null ? $selectedId : 'null' }},
        options: {{ \Illuminate\Support\Js::from($options) }},
        get selected() {
            return this.options.find(o => o.id === this.selectedId) || null
        },
        get filtered() {
            const q = this.query.trim().toLowerCase()
            if (! q) return this.options
            return this.options.filter(o => o.search.includes(q))
        },
        select(option) {
            this.selectedId = option.id
            this.query = ''
            this.open = false
        },
        clear() {
            this.selectedId = null
            this.query = ''
            this.open = true
            this.$nextTick(() => this.$refs.search?.focus())
        },
        openList() {
            this.open = true
            this.query = ''
            this.$nextTick(() => this.$refs.search?.focus())
        },
    }"
    @keydown.escape.window="open = false"
    @click.outside="open = false"
>
    {{-- Text input (not hidden) so the browser can enforce required before submit --}}
    <input
        type="text"
        name="{{ $name }}"
        id="{{ $name }}"
        required
        tabindex="-1"
        aria-hidden="true"
        class="sr-only absolute w-px h-px overflow-hidden opacity-0"
        value="{{ $selectedId ?? '' }}"
        :value="selectedId ?? ''"
        @focus="openList()"
    >

    <button
        type="button"
        @click="openList()"
        class="w-full flex items-center justify-between gap-2 rounded-xl border border-theme bg-white px-3 py-2.5 text-sm text-left focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary"
        :class="selected ? 'text-ink' : 'text-muted'"
        :aria-expanded="open"
        aria-haspopup="listbox"
        :aria-controls="'{{ $name }}_list'"
    >
        <span class="truncate" x-text="selected ? selected.label : 'Select a member'"></span>
        <svg class="w-4 h-4 shrink-0 text-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
        </svg>
    </button>

    <div
        x-show="open"
        x-cloak
        x-transition.opacity.duration.100ms
        id="{{ $name }}_list"
        class="absolute z-40 mt-1.5 w-full rounded-xl border border-theme bg-white shadow-lg overflow-hidden"
        role="listbox"
    >
        <div class="p-2 border-b border-theme">
            <input
                x-ref="search"
                type="search"
                x-model="query"
                placeholder="Search by name or nickname…"
                class="w-full rounded-lg border border-theme bg-surface px-3 py-2 text-sm text-ink focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary"
                autocomplete="off"
                @keydown.enter.prevent="filtered.length === 1 && select(filtered[0])"
            >
        </div>
        <ul class="max-h-56 overflow-y-auto py-1">
            <template x-for="option in filtered" :key="option.id">
                <li>
                    <button
                        type="button"
                        class="w-full text-left px-3 py-2 text-sm text-ink hover:bg-surface transition"
                        :class="option.id === selectedId ? 'bg-secondary-soft text-secondary font-semibold' : ''"
                        @click="select(option)"
                        role="option"
                        :aria-selected="option.id === selectedId"
                    >
                        <span x-text="option.label"></span>
                    </button>
                </li>
            </template>
            <li x-show="filtered.length === 0" class="px-3 py-3 text-sm text-muted">No members match.</li>
        </ul>
        <div x-show="selectedId" class="border-t border-theme p-1.5">
            <button type="button" class="w-full rounded-lg px-3 py-1.5 text-xs font-semibold text-muted hover:bg-surface hover:text-ink transition" @click="clear()">
                Clear selection
            </button>
        </div>
    </div>
</div>
