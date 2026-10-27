@extends('layouts.app')

@section('title', $user->name . ' - Player Profile')

@section('content')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>

@php
    $matchListIds = $matches->pluck('ranking_list_id')->values();
@endphp
<div
    class="space-y-5 lg:space-y-8"
    x-data="playerProfile(@js($eloChartData), @js($matchListIds))"
    x-init="initChart()"
>
    
    <!-- Player Profile Card Header -->
    <div class="surface-card rounded-2xl p-5 lg:p-8 relative overflow-hidden">
        <div class="absolute inset-x-0 top-0 h-1.5" style="background: linear-gradient(90deg, var(--color-primary), var(--color-secondary));"></div>
        <div class="flex flex-col sm:flex-row sm:items-center gap-4 relative z-10">
            <div class="w-16 h-16 lg:w-20 lg:h-20 rounded-2xl p-0.5 shadow-sm shrink-0" style="background: linear-gradient(135deg, var(--color-primary), var(--color-secondary));">
                @if($avatarUrl = $user->avatarPublicUrl())
                    <img src="{{ $avatarUrl }}" alt="" class="w-full h-full rounded-[14px] object-cover bg-white">
                @else
                    <div class="w-full h-full bg-white rounded-[14px] flex items-center justify-center font-heading font-black text-2xl lg:text-3xl text-secondary">
                        {{ strtoupper(substr($user->name, 0, 2)) }}
                    </div>
                @endif
            </div>

            <div class="min-w-0 flex-1">
                <div class="flex items-center gap-2 flex-wrap">
                    <h2 class="text-xl lg:text-3xl font-bold font-heading text-ink">{{ $user->name }}</h2>
                    @if($user->nickname)
                        <span class="px-2 py-0.5 rounded-md text-xs font-semibold bg-primary-soft text-primary border border-primary">
                            {{ '@' . $user->nickname }}
                        </span>
                    @endif
                </div>
                <p class="text-xs lg:text-sm text-muted mt-0.5 truncate">{{ $user->email }}</p>
                <div class="flex items-center gap-2 mt-2 flex-wrap">
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase bg-secondary-soft text-secondary border border-secondary">
                        {{ strtoupper($user->status) }} PLAYER
                    </span>
                    @auth
                        @if(auth()->id() !== $user->id && auth()->user()->status === 'active' && $user->canBeChallenged())
                            <a href="{{ route('matches.start', ['opponent' => $user->id]) }}" class="px-2.5 py-1 rounded-lg text-[11px] font-semibold bg-primary text-white hover:opacity-90 transition">
                                Start match
                            </a>
                        @endif
                    @endauth
                    @if($user->address)
                        <span class="text-xs text-muted flex items-center gap-1">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path></svg>
                            {{ $user->address }}
                        </span>
                    @endif
                </div>
            </div>
        </div>

        @if($user->date_of_birth)
            <div class="mt-4 pt-3 border-t border-theme text-xs text-muted flex items-center justify-between">
                <span>Date of Birth: <strong class="text-ink">{{ $user->date_of_birth->format('M d, Y') }}</strong></span>
                <span class="text-[10px] text-muted bg-surface px-2 py-0.5 rounded-md border border-theme">Public Profile</span>
            </div>
        @endif
    </div>

    <!-- Ranking list pills -->
    @if($availableLists->isNotEmpty())
        <div class="flex flex-wrap gap-2">
            <button
                type="button"
                @click="selectList(null)"
                :class="selectedListId === null ? 'pill-active' : 'pill-idle'"
                class="px-3 py-1.5 rounded-xl text-xs font-semibold whitespace-nowrap transition border"
            >
                All Rankings
            </button>
            @foreach($availableLists as $list)
                <button
                    type="button"
                    @click="selectList({{ $list->id }})"
                    :class="selectedListId === {{ $list->id }} ? 'pill-active' : 'pill-idle'"
                    class="px-3 py-1.5 rounded-xl text-xs font-semibold whitespace-nowrap transition border"
                >
                    {{ $list->title }}
                </button>
            @endforeach
        </div>
    @endif

    <!-- Rating history chart -->
    <div class="surface-card rounded-2xl p-4 lg:p-5" x-show="hasChartData" x-cloak>
        <div class="flex items-center justify-between gap-3 mb-3">
            <div>
                <h3 class="text-xs uppercase tracking-wider text-muted font-semibold">Rating History</h3>
                <p class="text-[11px] text-muted mt-0.5" x-text="chartSubtitle"></p>
            </div>
            <span
                class="px-2.5 py-1 rounded-lg bg-secondary-soft text-secondary font-heading font-bold text-sm border border-secondary"
                x-show="selectedCurrentElo !== null"
                x-text="selectedCurrentElo + ' ELO'"
            ></span>
        </div>
        <div class="relative h-48 lg:h-56">
            <canvas id="elo-rating-chart"></canvas>
        </div>
    </div>

    <div class="lg:grid lg:grid-cols-2 lg:gap-8 lg:items-start space-y-5 lg:space-y-0">
        <div class="space-y-3">
            <h3 class="text-xs uppercase tracking-wider text-muted font-semibold px-1">ELO Ratings Across Lists</h3>
            
            <div class="grid grid-cols-1 gap-2.5">
                @forelse($rankings as $r)
                    <div
                        class="surface-card rounded-2xl p-4 flex items-center justify-between gap-3"
                        x-show="selectedListId === null || selectedListId === {{ $r->ranking_list_id }}"
                        x-cloak
                    >
                        <div class="min-w-0">
                            <span class="text-xs font-medium text-secondary block truncate">{{ $r->rankingList?->title }}</span>
                            @if($r->rankingList?->type === 'doubles')
                                <div class="mt-1 flex items-center gap-1.5 flex-wrap">
                                    <span class="px-1.5 py-0.5 rounded text-[10px] font-semibold uppercase border {{ $r->isPair() ? 'bg-secondary-soft text-secondary border-secondary' : 'bg-primary-soft text-primary border-primary' }}">
                                        {{ $r->isPair() ? 'Pair' : 'Individual' }}
                                    </span>
                                    @if($r->isPair())
                                        <x-ranking-name :ranking="$r" size="xs" :link="true" class="text-[10px] text-muted" />
                                    @endif
                                </div>
                            @endif
                            <div class="text-xs text-muted flex items-center gap-2 mt-1 flex-wrap">
                                <span>Matches: <strong class="text-ink">{{ $r->matches_played }}</strong></span>
                                <span>•</span>
                                <span>Win Rate: <strong class="text-primary">{{ $r->win_rate }}%</strong></span>
                            </div>
                        </div>

                        <div class="text-right shrink-0">
                            <span class="px-3 py-1 rounded-xl bg-secondary-soft text-secondary font-heading font-extrabold text-base border border-secondary">
                                {{ $r->elo_rating }} ELO
                            </span>
                            <div class="text-[10px] text-muted mt-1">
                                W: {{ $r->wins }} / L: {{ $r->losses }}
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="surface-card rounded-2xl p-4 text-center text-xs text-muted">
                        No active ranking list entries yet.
                    </div>
                @endforelse
            </div>
        </div>

        <div class="space-y-3">
            <div class="flex items-center justify-between gap-2 px-1">
                <h3 class="text-xs uppercase tracking-wider text-muted font-semibold">Recent Matches</h3>
                <span class="text-[10px] text-muted" x-text="visibleMatchCountLabel"></span>
            </div>

            <div class="space-y-2.5">
                @forelse($matches as $m)
                    @php
                        $isSide1 = $m->side1Ranking?->user_id === $user->id || $m->side1Ranking?->player2_id === $user->id;
                        $userSets = $isSide1 ? $m->side1_sets_won : $m->side2_sets_won;
                        $oppSets = $isSide1 ? $m->side2_sets_won : $m->side1_sets_won;
                        $opponentRanking = $isSide1 ? $m->side2Ranking : $m->side1Ranking;
                        $wonMatch = ($isSide1 && $m->winner_side === 1) || (!$isSide1 && $m->winner_side === 2);
                    @endphp
                    <div
                        class="surface-card rounded-xl p-3 flex items-center justify-between gap-3"
                        x-show="selectedListId === null || selectedListId === {{ $m->ranking_list_id }}"
                        x-cloak
                    >
                        <div class="flex items-center gap-3 min-w-0">
                            <span class="w-7 h-7 shrink-0 rounded-lg font-heading font-bold text-xs flex items-center justify-center {{ $wonMatch ? 'bg-primary-soft text-primary border border-primary' : 'bg-surface text-muted border border-theme' }}">
                                {{ $wonMatch ? 'W' : 'L' }}
                            </span>
                            <div class="min-w-0">
                                <div class="text-xs font-bold text-ink flex items-center gap-1.5 min-w-0">
                                    <span class="shrink-0 text-muted">vs</span>
                                    <x-ranking-name :ranking="$opponentRanking" size="xs" class="text-xs font-bold text-ink" />
                                </div>
                                <div class="text-[10px] text-muted truncate">{{ $m->rankingList?->title }}</div>
                            </div>
                        </div>

                        <div class="text-right shrink-0">
                            <div class="font-mono text-sm font-bold {{ $wonMatch ? 'text-primary' : 'text-ink' }}">
                                {{ $userSets }} - {{ $oppSets }}
                            </div>
                            <div class="text-[10px] text-muted">
                                {{ $m->played_at ? $m->played_at->format('M d') : '' }}
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="surface-card rounded-xl p-4 text-center text-xs text-muted">
                        No matches played yet.
                    </div>
                @endforelse

                <div
                    class="surface-card rounded-xl p-4 text-center text-xs text-muted"
                    x-show="{{ $matches->isNotEmpty() ? 'true' : 'false' }} && !hasVisibleMatches"
                    x-cloak
                >
                    No matches in this ranking yet.
                </div>
            </div>
        </div>
    </div>

</div>

<script>
    function playerProfile(chartSeries, matchListIds) {
        const palette = [
            getComputedStyle(document.documentElement).getPropertyValue('--color-primary').trim() || '#DC143C',
            getComputedStyle(document.documentElement).getPropertyValue('--color-secondary').trim() || '#003893',
            '#0d9488',
            '#ca8a04',
            '#7c3aed',
            '#ea580c',
        ];

        // Keep Chart.js outside Alpine's reactive proxy — proxied chart instances
        // update data correctly but often fail to redraw the canvas.
        let eloChart = null;

        return {
            selectedListId: null,
            chartSeries: chartSeries || [],
            matchListIds: matchListIds || [],

            get hasChartData() {
                return this.chartSeries.some((s) => s.points && s.points.length > 0);
            },

            get selectedSeries() {
                if (this.selectedListId === null) {
                    return this.chartSeries;
                }
                return this.chartSeries.filter((s) => Number(s.listId) === Number(this.selectedListId));
            },

            get selectedCurrentElo() {
                if (this.selectedListId === null) {
                    return null;
                }
                const series = this.chartSeries.find((s) => Number(s.listId) === Number(this.selectedListId));
                return series ? series.currentElo : null;
            },

            get chartSubtitle() {
                if (this.selectedListId === null) {
                    return this.chartSeries.length > 1
                        ? 'Comparing ELO across all ranking lists'
                        : (this.chartSeries[0]?.title || 'ELO over time');
                }
                return this.selectedSeries[0]?.title || 'ELO over time';
            },

            get visibleMatchCount() {
                if (this.selectedListId === null) {
                    return this.matchListIds.length;
                }
                return this.matchListIds.filter((id) => Number(id) === Number(this.selectedListId)).length;
            },

            get hasVisibleMatches() {
                return this.visibleMatchCount > 0;
            },

            get visibleMatchCountLabel() {
                return this.visibleMatchCount ? `${this.visibleMatchCount} shown` : '';
            },

            selectList(listId) {
                this.selectedListId = listId;
                this.renderChart();
            },

            initChart() {
                this.renderChart();
            },

            buildDatasets(series) {
                const multi = series.length > 1;
                return series.map((s, index) => {
                    const color = palette[index % palette.length];
                    return {
                        label: s.title,
                        data: s.points.map((p) => p.elo),
                        borderColor: color,
                        backgroundColor: color + '22',
                        fill: !multi,
                        spanGaps: true,
                        tension: 0.25,
                        borderWidth: 2.5,
                        pointRadius: 3,
                        pointHoverRadius: 5,
                    };
                });
            },

            renderChart() {
                const canvas = document.getElementById('elo-rating-chart');
                if (!canvas || typeof Chart === 'undefined' || !this.hasChartData) {
                    return;
                }

                const series = this.selectedSeries;
                if (!series.length) {
                    return;
                }

                const multi = series.length > 1;
                const labelSource = multi
                    ? series.reduce((a, b) => (a.points.length >= b.points.length ? a : b))
                    : series[0];
                const labels = (labelSource?.points || []).map((p) => p.label);
                const datasets = this.buildDatasets(series);

                // Destroy + recreate so the canvas always matches the selected pills.
                const existing = Chart.getChart(canvas);
                if (existing) {
                    existing.destroy();
                }
                if (eloChart) {
                    try { eloChart.destroy(); } catch (e) {}
                    eloChart = null;
                }

                eloChart = new Chart(canvas.getContext('2d'), {
                    type: 'line',
                    data: { labels, datasets },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        animation: { duration: 250 },
                        interaction: { mode: 'index', intersect: false },
                        plugins: {
                            legend: {
                                display: multi,
                                position: 'bottom',
                                labels: {
                                    boxWidth: 10,
                                    boxHeight: 10,
                                    usePointStyle: true,
                                    pointStyle: 'circle',
                                    font: { size: 11 },
                                    color: '#64748b',
                                },
                            },
                            tooltip: {
                                callbacks: {
                                    label: (ctx) => `${ctx.dataset.label}: ${ctx.parsed.y} ELO`,
                                },
                            },
                        },
                        scales: {
                            x: {
                                grid: { display: false },
                                ticks: { color: '#94a3b8', font: { size: 10 }, maxRotation: 0 },
                            },
                            y: {
                                grace: '8%',
                                grid: { color: '#f1f5f9' },
                                ticks: { color: '#94a3b8', font: { size: 10 }, precision: 0 },
                            },
                        },
                    },
                });
            },
        };
    }
</script>

<style>
    [x-cloak] { display: none !important; }
</style>
@endsection
