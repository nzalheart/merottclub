@php
    $statePath = $getStatePath();
    $side1 = $getSide1();
    $side2 = $getSide2();
    $minSets = $getMinSets();
    $maxSets = $getMaxSets();
    $kFactor = $getKFactor();
    $matrixKey = $getMatrixKey();
    $isDisabled = $isDisabled();
    $side1Elo = $side1['elo'];
    $side2Elo = $side2['elo'];
    $hasElo = $side1Elo !== null && $side2Elo !== null;
@endphp

<style>
    .tt-score-matrix {
        --tt-gap: 0.25rem;
        --tt-cell: 2rem;
        --tt-name: 5.75rem;
        --tt-pad: 0.5rem;
        --tt-total: 1.25rem;
    }

    @media (min-width: 640px) {
        .tt-score-matrix {
            --tt-gap: 0.5rem;
            --tt-cell: 2.5rem;
            --tt-name: 7.5rem;
            --tt-pad: 0.875rem;
            --tt-total: 1.5rem;
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
        color: rgb(3 7 18);
    }

    .tt-score-matrix__nick {
        display: block;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        font-size: 0.6875rem;
        line-height: 1.2;
        color: rgb(107 114 128);
    }

    @media (min-width: 640px) {
        .tt-score-matrix__name {
            font-size: 0.8125rem;
        }

        .tt-score-matrix__nick {
            font-size: 0.75rem;
        }
    }

    .tt-score-matrix__totals {
        flex-shrink: 0;
        display: flex;
        flex-direction: column;
        gap: var(--tt-gap);
        padding-inline-end: var(--tt-gap);
        margin-inline-end: 0.125rem;
        border-inline-end: 1px solid rgb(229 231 235);
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
        .tt-score-matrix__total {
            font-size: 1.25rem;
        }
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
        color: rgb(156 163 175);
        line-height: 1;
        display: flex;
        align-items: center;
    }

    .tt-score-matrix__input {
        width: var(--tt-cell) !important;
        height: var(--tt-cell) !important;
        min-width: 0 !important;
        max-width: var(--tt-cell) !important;
        padding: 0 !important;
        margin: 0 !important;
        border-radius: 0.375rem !important;
        text-align: center !important;
        font-size: 0.75rem !important;
        font-weight: 600 !important;
        font-variant-numeric: tabular-nums;
        background: white !important;
        box-shadow: none !important;
        outline: none !important;
        border: 1px solid rgb(209 213 219) !important;
        transition: border-color 0.15s ease, box-shadow 0.15s ease;
    }

    @media (min-width: 640px) {
        .tt-score-matrix__input {
            border-radius: 0.5rem !important;
            font-size: 0.875rem !important;
        }
    }

    .tt-score-matrix__input:focus {
        border-color: #b91c1c !important;
        box-shadow: 0 0 0 3px rgb(185 28 28 / 0.18) !important;
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
        transition: background-color 0.15s ease, color 0.15s ease, opacity 0.15s ease;
    }

    .tt-score-matrix__btn:disabled {
        opacity: 0.35;
        cursor: not-allowed;
    }

    .dark .tt-score-matrix__name {
        color: white;
    }

    .dark .tt-score-matrix__nick {
        color: rgb(156 163 175);
    }

    .dark .tt-score-matrix__totals {
        border-inline-end-color: rgb(255 255 255 / 0.1);
    }

    .dark .tt-score-matrix__input {
        background: rgb(17 24 39) !important;
        border-color: rgb(255 255 255 / 0.15) !important;
        color: white !important;
    }
</style>

<x-dynamic-component
    :component="$getFieldWrapperView()"
    :field="$field"
>
    <div
        wire:ignore
        wire:key="set-score-matrix.{{ $matrixKey }}.{{ $minSets }}.{{ $maxSets }}.{{ $side1Elo }}.{{ $side2Elo }}"
        x-data="{
            state: $wire.{{ $applyStateBindingModifiers("\$entangle('{$statePath}')") }},
            minSets: {{ $minSets }},
            maxSets: {{ $maxSets }},
            kFactor: {{ $kFactor }},
            rating1: {{ $side1Elo ?? 'null' }},
            rating2: {{ $side2Elo ?? 'null' }},
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
                if (!Array.isArray(this.state)) return 0
                let wins = 0
                for (const set of this.state) {
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
                const s1 = this.setsWon(1)
                const s2 = this.setsWon(2)
                if (s1 >= this.minSets) return 1
                if (s2 >= this.minSets) return 2
                return 0
            },
            expected(side) {
                if (this.rating1 === null || this.rating2 === null) return 0.5
                if (side === 1) {
                    return 1 / (1 + Math.pow(10, (this.rating2 - this.rating1) / 400))
                }
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
                const current = side === 1 ? this.rating1 : this.rating2
                return current + delta
            },
            formatDelta(value) {
                if (value === null || value === undefined) return '—'
                return (value > 0 ? '+' : '') + value
            },
            addSet() {
                if (!Array.isArray(this.state)) this.state = []
                if (this.state.length >= this.maxSets) return
                this.state.push({
                    set_number: this.state.length + 1,
                    side1_score: null,
                    side2_score: null,
                    winner_side: null,
                })
            },
            removeLast() {
                if (!Array.isArray(this.state) || this.state.length <= this.minSets) return
                this.state.pop()
            },
        }"
        class="fi-fo-set-score-matrix tt-score-matrix w-full space-y-2"
    >
        <div class="w-full overflow-x-auto rounded-xl bg-gray-50 dark:bg-white/5">
            <div class="tt-score-matrix__board">
                <div class="tt-score-matrix__names">
                    <div class="tt-score-matrix__player" title="{{ $side1['title'] }}">
                        <span class="tt-score-matrix__name">{{ $side1['short_name'] }}</span>
                        @if ($side1['nickname'])
                            <span class="tt-score-matrix__nick">{{ $side1['nickname'] }}</span>
                        @endif
                    </div>
                    <div class="tt-score-matrix__player" title="{{ $side2['title'] }}">
                        <span class="tt-score-matrix__name">{{ $side2['short_name'] }}</span>
                        @if ($side2['nickname'])
                            <span class="tt-score-matrix__nick">{{ $side2['nickname'] }}</span>
                        @endif
                    </div>
                </div>

                <div class="tt-score-matrix__totals">
                    <div
                        class="tt-score-matrix__total"
                        :class="leadingSide() === 1 ? 'text-primary-600 dark:text-primary-400' : 'text-gray-400 dark:text-gray-500'"
                        x-text="setsWon(1)"
                    ></div>
                    <div
                        class="tt-score-matrix__total"
                        :class="leadingSide() === 2 ? 'text-primary-600 dark:text-primary-400' : 'text-gray-400 dark:text-gray-500'"
                        x-text="setsWon(2)"
                    ></div>
                </div>

                <div class="tt-score-matrix__sets">
                    <template x-for="(set, index) in state" :key="index">
                        <div class="tt-score-matrix__set">
                            <span class="tt-score-matrix__set-label" x-text="index + 1"></span>
                            <input
                                type="text"
                                inputmode="numeric"
                                maxlength="3"
                                @disabled($isDisabled)
                                x-model="state[index].side1_score"
                                class="tt-score-matrix__input fi-input"
                            />
                            <input
                                type="text"
                                inputmode="numeric"
                                maxlength="3"
                                @disabled($isDisabled)
                                x-model="state[index].side2_score"
                                class="tt-score-matrix__input fi-input"
                            />
                        </div>
                    </template>
                </div>

                @unless ($isDisabled)
                    <div class="tt-score-matrix__actions">
                        <button
                            type="button"
                            @click="addSet()"
                            :disabled="state.length >= maxSets"
                            class="tt-score-matrix__btn bg-primary-600 text-white hover:bg-primary-500"
                            title="Add set"
                        >
                            <x-filament::icon icon="heroicon-m-plus" class="h-3.5 w-3.5 sm:h-4 sm:w-4" />
                        </button>
                        <button
                            type="button"
                            @click="removeLast()"
                            :disabled="state.length <= minSets"
                            class="tt-score-matrix__btn text-gray-400 hover:bg-white hover:text-danger-600 dark:hover:bg-white/10"
                            title="Remove last set"
                        >
                            <x-filament::icon icon="heroicon-m-minus" class="h-3.5 w-3.5 sm:h-4 sm:w-4" />
                        </button>
                    </div>
                @endunless
            </div>
        </div>

        @if ($hasElo)
            <div class="grid grid-cols-2 gap-2 rounded-xl bg-gray-50 px-3 py-2.5 text-xs dark:bg-white/5 sm:gap-3 sm:px-4 sm:py-3 sm:text-sm">
                <div class="min-w-0 space-y-0.5">
                    <div class="truncate font-semibold text-gray-950 dark:text-white">{{ $side1['short_name'] }}</div>
                    <div class="tabular-nums text-gray-600 dark:text-gray-300">
                        ELO <span class="font-semibold text-gray-950 dark:text-white">{{ $side1Elo }}</span>
                    </div>
                    <template x-if="matchWinner()">
                        <div class="tabular-nums font-semibold"
                             :class="projectedDelta(1) >= 0 ? 'text-success-600 dark:text-success-400' : 'text-danger-600 dark:text-danger-400'">
                            <span x-text="formatDelta(projectedDelta(1))"></span>
                            <span class="font-normal text-gray-500">→</span>
                            <span x-text="projectedRating(1)"></span>
                        </div>
                    </template>
                    <template x-if="!matchWinner()">
                        <div class="tabular-nums text-gray-500 dark:text-gray-400">
                            Win <span class="font-semibold text-success-600 dark:text-success-400" x-text="formatDelta(winDelta(1))"></span>
                            <span class="mx-1 text-gray-300 dark:text-gray-600">·</span>
                            Lose <span class="font-semibold text-danger-600 dark:text-danger-400" x-text="formatDelta(loseDelta(1))"></span>
                        </div>
                    </template>
                </div>

                <div class="min-w-0 space-y-0.5 text-right">
                    <div class="truncate font-semibold text-gray-950 dark:text-white">{{ $side2['short_name'] }}</div>
                    <div class="tabular-nums text-gray-600 dark:text-gray-300">
                        ELO <span class="font-semibold text-gray-950 dark:text-white">{{ $side2Elo }}</span>
                    </div>
                    <template x-if="matchWinner()">
                        <div class="tabular-nums font-semibold"
                             :class="projectedDelta(2) >= 0 ? 'text-success-600 dark:text-success-400' : 'text-danger-600 dark:text-danger-400'">
                            <span x-text="formatDelta(projectedDelta(2))"></span>
                            <span class="font-normal text-gray-500">→</span>
                            <span x-text="projectedRating(2)"></span>
                        </div>
                    </template>
                    <template x-if="!matchWinner()">
                        <div class="tabular-nums text-gray-500 dark:text-gray-400">
                            Win <span class="font-semibold text-success-600 dark:text-success-400" x-text="formatDelta(winDelta(2))"></span>
                            <span class="mx-1 text-gray-300 dark:text-gray-600">·</span>
                            Lose <span class="font-semibold text-danger-600 dark:text-danger-400" x-text="formatDelta(loseDelta(2))"></span>
                        </div>
                    </template>
                </div>
            </div>
        @endif
    </div>
</x-dynamic-component>
