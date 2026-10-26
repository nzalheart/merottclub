@php
    $editable = $editable ?? false;
    $sets = array_values($sets ?? []);
    $minSets = (int) ($minSets ?? 2);
    $maxSets = (int) ($maxSets ?? 5);
    if ($editable) {
        while (count($sets) < $minSets) {
            $sets[] = ['side1_score' => '', 'side2_score' => ''];
        }
        $sets = array_slice($sets, 0, $maxSets);
    }
    $rating1 = $rating1 ?? null;
    $rating2 = $rating2 ?? null;
    $kFactor = (int) ($kFactor ?? 32);
    $showElo = $editable && $rating1 !== null && $rating2 !== null;

    $side1Wins = 0;
    $side2Wins = 0;
    foreach ($sets as $set) {
        $a = $set['side1_score'] ?? null;
        $b = $set['side2_score'] ?? null;
        if ($a === null || $a === '' || $b === null || $b === '' || (int) $a === (int) $b) {
            continue;
        }
        if ((int) $a > (int) $b) {
            $side1Wins++;
        } else {
            $side2Wins++;
        }
    }

    $matrixConfig = [
        'sets' => collect($sets)->map(fn ($set) => [
            'side1_score' => (string) ($set['side1_score'] ?? ''),
            'side2_score' => (string) ($set['side2_score'] ?? ''),
        ])->values(),
        'minSets' => $minSets,
        'maxSets' => $maxSets,
        'kFactor' => $kFactor,
        'rating1' => $rating1 === null ? null : (int) $rating1,
        'rating2' => $rating2 === null ? null : (int) $rating2,
    ];
@endphp

@once
    <style>
        .tt-score-matrix {
            --tt-gap: 0.25rem;
            --tt-cell: 2rem;
            --tt-name: 5.75rem;
            --tt-pad: 0.5rem;
            --tt-total: 1.25rem;
        }
        .tt-score-matrix--status {
            --tt-name: 11.5rem;
        }
        @media (min-width: 640px) {
            .tt-score-matrix {
                --tt-gap: 0.5rem;
                --tt-cell: 2.5rem;
                --tt-name: 7.5rem;
                --tt-pad: 0.875rem;
                --tt-total: 1.5rem;
            }
            .tt-score-matrix--status {
                --tt-name: 14rem;
            }
        }
        .tt-score-matrix__board {
            display: flex;
            align-items: flex-end;
            gap: var(--tt-gap);
            width: max-content;
            max-width: 100%;
            padding: var(--tt-pad);
        }
        .tt-score-matrix__names {
            width: var(--tt-name);
            flex-shrink: 0;
            display: flex;
            flex-direction: column;
            gap: var(--tt-gap);
        }
        .tt-score-matrix__player {
            height: var(--tt-cell);
            display: flex;
            flex-direction: column;
            justify-content: center;
            min-width: 0;
        }
        .tt-score-matrix__name {
            display: block;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            font-size: 0.75rem;
            font-weight: 600;
            line-height: 1.2;
            color: var(--color-ink);
        }
        .tt-score-matrix__nick {
            display: block;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            font-size: 0.6875rem;
            line-height: 1.2;
            color: var(--color-muted);
        }
        @media (min-width: 640px) {
            .tt-score-matrix__name { font-size: 0.8125rem; }
            .tt-score-matrix__nick { font-size: 0.75rem; }
        }
        .tt-score-matrix__totals {
            flex-shrink: 0;
            display: flex;
            flex-direction: column;
            gap: var(--tt-gap);
            padding-inline-end: var(--tt-gap);
            margin-inline-end: 0.125rem;
            border-inline-end: 1px solid var(--color-border);
        }
        .tt-score-matrix__total {
            width: var(--tt-total);
            height: var(--tt-cell);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
            font-weight: 700;
            font-variant-numeric: tabular-nums;
            line-height: 1;
        }
        @media (min-width: 640px) {
            .tt-score-matrix__total { font-size: 1.25rem; }
        }
        .tt-score-matrix__sets {
            display: flex;
            align-items: flex-end;
            gap: var(--tt-gap);
        }
        .tt-score-matrix__set {
            width: var(--tt-cell);
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: var(--tt-gap);
        }
        .tt-score-matrix__set-label {
            height: 0.75rem;
            font-size: 0.5625rem;
            font-weight: 600;
            letter-spacing: 0.04em;
            color: #9ca3af;
            line-height: 1;
            display: flex;
            align-items: center;
        }
        .tt-score-matrix__input,
        .tt-score-matrix__value {
            width: var(--tt-cell);
            height: var(--tt-cell);
            min-width: 0;
            max-width: var(--tt-cell);
            padding: 0;
            margin: 0;
            border-radius: 0.375rem;
            text-align: center;
            font-size: 0.75rem;
            font-weight: 600;
            font-variant-numeric: tabular-nums;
            background: white;
            border: 1px solid #d1d5db;
            color: var(--color-ink);
            display: flex;
            align-items: center;
            justify-content: center;
        }
        @media (min-width: 640px) {
            .tt-score-matrix__input,
            .tt-score-matrix__value {
                border-radius: 0.5rem;
                font-size: 0.875rem;
            }
        }
        .tt-score-matrix__input:focus {
            outline: none;
            border-color: var(--color-primary);
            box-shadow: 0 0 0 3px color-mix(in srgb, var(--color-primary) 18%, transparent);
        }
        .tt-score-matrix__actions {
            flex-shrink: 0;
            display: flex;
            flex-direction: column;
            justify-content: flex-end;
            gap: var(--tt-gap);
            padding-inline-start: 0.125rem;
        }
        .tt-score-matrix__btn {
            width: var(--tt-cell);
            height: var(--tt-cell);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 9999px;
        }
        .tt-score-matrix__btn:disabled {
            opacity: 0.35;
            cursor: not-allowed;
        }
    </style>
    <script>
            function ttScoreMatrix(config) {
                return {
                    sets: config.sets,
                    minSets: config.minSets,
                    maxSets: config.maxSets,
                    kFactor: config.kFactor,
                    rating1: config.rating1,
                    rating2: config.rating2,
                    isValidSet(a, b) {
                        a = parseInt(a, 10)
                        b = parseInt(b, 10)
                        if (Number.isNaN(a) || Number.isNaN(b) || a === b || a < 0 || b < 0) return false
                        const high = Math.max(a, b)
                        const low = Math.min(a, b)
                        if (high < 11) return false
                        if (low < 10) return high === 11
                        return (high - low) === 2
                    },
                    setsWon(side) {
                        let wins = 0
                        for (const set of this.sets) {
                            if (!this.isValidSet(set.side1_score, set.side2_score)) continue
                            const a = parseInt(set.side1_score, 10)
                            const b = parseInt(set.side2_score, 10)
                            if (side === 1 && a > b) wins++
                            if (side === 2 && b > a) wins++
                        }
                        return wins
                    },
                    leadingSide() {
                        const s1 = this.setsWon(1)
                        const s2 = this.setsWon(2)
                        if (s1 === s2) return 0
                        return s1 > s2 ? 1 : 2
                    },
                    matchWinner() {
                        if (this.setsWon(1) >= this.minSets) return 1
                        if (this.setsWon(2) >= this.minSets) return 2
                        return 0
                    },
                    expected(side) {
                        if (this.rating1 === null || this.rating2 === null) return 0.5
                        if (side === 1) return 1 / (1 + Math.pow(10, (this.rating2 - this.rating1) / 400))
                        return 1 / (1 + Math.pow(10, (this.rating1 - this.rating2) / 400))
                    },
                    winDelta(side) {
                        return Math.round(this.kFactor * (1 - this.expected(side)))
                    },
                    loseDelta(side) {
                        return Math.round(this.kFactor * (0 - this.expected(side)))
                    },
                    projectedDelta(side) {
                        const winner = this.matchWinner()
                        if (!winner) return null
                        const actual = winner === side ? 1 : 0
                        return Math.round(this.kFactor * (actual - this.expected(side)))
                    },
                    projectedRating(side) {
                        const delta = this.projectedDelta(side)
                        if (delta === null) return null
                        return (side === 1 ? this.rating1 : this.rating2) + delta
                    },
                    formatDelta(value) {
                        if (value === null || value === undefined) return '—'
                        return (value > 0 ? '+' : '') + value
                    },
                    addSet() {
                        if (this.sets.length >= this.maxSets) return
                        this.sets.push({ side1_score: '', side2_score: '' })
                    },
                    removeLast() {
                        if (this.sets.length <= this.minSets) return
                        this.sets.pop()
                    },
                }
            }
        </script>
@endonce

@if($editable)
    <div
        class="tt-score-matrix w-full space-y-2 {{ (! empty($side1['status']) || ! empty($side2['status'])) ? 'tt-score-matrix--status' : '' }}"
        x-data='ttScoreMatrix(@json($matrixConfig))'
    >
        <div class="w-full overflow-x-auto rounded-xl bg-surface">
            <div class="tt-score-matrix__board">
                @include('matches.partials.score-matrix-names', ['side1' => $side1, 'side2' => $side2])

                <div class="tt-score-matrix__totals">
                    <div class="tt-score-matrix__total" :class="leadingSide() === 1 ? 'text-primary' : 'text-muted'" x-text="setsWon(1)"></div>
                    <div class="tt-score-matrix__total" :class="leadingSide() === 2 ? 'text-primary' : 'text-muted'" x-text="setsWon(2)"></div>
                </div>

                <div class="tt-score-matrix__sets">
                    <template x-for="(set, index) in sets" :key="index">
                        <div class="tt-score-matrix__set">
                            <span class="tt-score-matrix__set-label" x-text="index + 1"></span>
                            <input type="text" inputmode="numeric" maxlength="2" class="tt-score-matrix__input" :name="'sets[' + index + '][side1_score]'" x-model="sets[index].side1_score" :aria-label="'Set ' + (index + 1) + ' side 1'">
                            <input type="text" inputmode="numeric" maxlength="2" class="tt-score-matrix__input" :name="'sets[' + index + '][side2_score]'" x-model="sets[index].side2_score" :aria-label="'Set ' + (index + 1) + ' side 2'">
                        </div>
                    </template>
                </div>

                <div class="tt-score-matrix__actions">
                    <button type="button" class="tt-score-matrix__btn bg-primary text-white" @click="addSet()" :disabled="sets.length >= maxSets" title="Add set" aria-label="Add set">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 5v14m-7-7h14"></path></svg>
                    </button>
                    <button type="button" class="tt-score-matrix__btn text-muted hover:text-primary" @click="removeLast()" :disabled="sets.length <= minSets" title="Remove last set" aria-label="Remove last set">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 12h14"></path></svg>
                    </button>
                </div>
            </div>
        </div>

        @if($showElo)
            <div class="grid grid-cols-2 gap-2 rounded-xl bg-surface px-3 py-2.5 text-xs sm:gap-3 sm:px-4 sm:py-3 sm:text-sm">
                <div class="min-w-0 space-y-0.5">
                    <div class="truncate font-semibold text-ink">{{ $side1['name'] }}</div>
                    <div class="tabular-nums text-muted">ELO <span class="font-semibold text-ink">{{ (int) $rating1 }}</span></div>
                    <template x-if="matchWinner()">
                        <div class="tabular-nums font-semibold" :class="projectedDelta(1) >= 0 ? 'text-primary' : 'text-muted'">
                            <span x-text="formatDelta(projectedDelta(1))"></span>
                            <span class="font-normal text-muted">→</span>
                            <span x-text="projectedRating(1)"></span>
                        </div>
                    </template>
                    <template x-if="!matchWinner()">
                        <div class="tabular-nums text-muted">
                            Win <span class="font-semibold text-primary" x-text="formatDelta(winDelta(1))"></span>
                            <span class="mx-1">·</span>
                            Lose <span class="font-semibold" x-text="formatDelta(loseDelta(1))"></span>
                        </div>
                    </template>
                </div>
                <div class="min-w-0 space-y-0.5 text-right">
                    <div class="truncate font-semibold text-ink">{{ $side2['name'] }}</div>
                    <div class="tabular-nums text-muted">ELO <span class="font-semibold text-ink">{{ (int) $rating2 }}</span></div>
                    <template x-if="matchWinner()">
                        <div class="tabular-nums font-semibold" :class="projectedDelta(2) >= 0 ? 'text-primary' : 'text-muted'">
                            <span x-text="formatDelta(projectedDelta(2))"></span>
                            <span class="font-normal text-muted">→</span>
                            <span x-text="projectedRating(2)"></span>
                        </div>
                    </template>
                    <template x-if="!matchWinner()">
                        <div class="tabular-nums text-muted">
                            Win <span class="font-semibold text-primary" x-text="formatDelta(winDelta(2))"></span>
                            <span class="mx-1">·</span>
                            Lose <span class="font-semibold" x-text="formatDelta(loseDelta(2))"></span>
                        </div>
                    </template>
                </div>
            </div>
        @endif
    </div>
@else
    <div class="tt-score-matrix w-full {{ (! empty($side1['status']) || ! empty($side2['status'])) ? 'tt-score-matrix--status' : '' }}">
        <div class="w-full overflow-x-auto rounded-xl bg-surface">
            <div class="tt-score-matrix__board">
                @include('matches.partials.score-matrix-names', ['side1' => $side1, 'side2' => $side2])

                <div class="tt-score-matrix__totals">
                    <div class="tt-score-matrix__total {{ $side1Wins > $side2Wins ? 'text-primary' : 'text-muted' }}">{{ $side1Wins }}</div>
                    <div class="tt-score-matrix__total {{ $side2Wins > $side1Wins ? 'text-primary' : 'text-muted' }}">{{ $side2Wins }}</div>
                </div>

                <div class="tt-score-matrix__sets">
                    @foreach($sets as $index => $set)
                        <div class="tt-score-matrix__set">
                            <span class="tt-score-matrix__set-label">{{ $index + 1 }}</span>
                            <span class="tt-score-matrix__value">{{ $set['side1_score'] }}</span>
                            <span class="tt-score-matrix__value">{{ $set['side2_score'] }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
@endif
