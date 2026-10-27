<?php

namespace App\Http\Controllers;

use App\Models\EloHistory;
use App\Models\MatchRecord;
use App\Models\PlayerRanking;
use App\Models\RankingList;
use App\Models\User;
use Illuminate\Http\Request;

class PublicRankingController extends Controller
{
    public function index(Request $request)
    {
        $rankingLists = RankingList::where('is_active', true)->get();
        $selectedListId = $request->get('list_id', $rankingLists->first()?->id);

        $currentList = $rankingLists->firstWhere('id', $selectedListId) ?? $rankingLists->first();

        $viewMode = $request->get('view', 'individual');
        if (! in_array($viewMode, ['individual', 'pair'], true)) {
            $viewMode = 'individual';
        }

        // Singles lists only have individuals; ignore pair view.
        if ($currentList && $currentList->type !== 'doubles') {
            $viewMode = 'individual';
        }

        $search = trim((string) $request->get('search', ''));

        $rankings = null;
        if ($currentList) {
            $query = PlayerRanking::where('ranking_list_id', $currentList->id)
                ->with(['user', 'player2']);

            if ($currentList->type === 'doubles') {
                if ($viewMode === 'pair') {
                    $query->pairs();
                } else {
                    $query->individuals();
                }
            } else {
                $query->individuals();
            }

            if ($search !== '') {
                $term = '%' . addcslashes(ltrim($search, '@'), '%_\\') . '%';
                $query->where(function ($q) use ($term) {
                    $q->whereHas('user', function ($userQuery) use ($term) {
                        $userQuery->where('name', 'like', $term)
                            ->orWhere('nickname', 'like', $term);
                    })->orWhereHas('player2', function ($userQuery) use ($term) {
                        $userQuery->where('name', 'like', $term)
                            ->orWhere('nickname', 'like', $term);
                    });
                });
            }

            $rankings = $query
                ->orderBy('elo_rating', 'desc')
                ->paginate(40)
                ->withQueryString();
        }

        return view('leaderboard', compact('rankingLists', 'currentList', 'rankings', 'viewMode', 'search'));
    }

    public function matches(Request $request)
    {
        $rankingLists = RankingList::where('is_active', true)->get();
        $selectedListId = $request->get('list_id');

        $query = MatchRecord::with(['rankingList', 'side1Ranking.user', 'side1Ranking.player2', 'side2Ranking.user', 'side2Ranking.player2', 'sets', 'manager'])
            ->where('status', 'completed')
            ->orderBy('played_at', 'desc');

        if ($selectedListId) {
            $query->where('ranking_list_id', $selectedListId);
        }

        $matches = $query->paginate(15);

        return view('matches', compact('rankingLists', 'selectedListId', 'matches'));
    }

    public function playerProfile(User $user)
    {
        $rankings = PlayerRanking::where(function ($q) use ($user) {
            $q->where('user_id', $user->id)->orWhere('player2_id', $user->id);
        })
            ->with(['rankingList', 'user', 'player2'])
            ->get();

        $availableLists = $rankings
            ->pluck('rankingList')
            ->filter()
            ->unique('id')
            ->values();

        $eloChartData = [];
        foreach ($availableLists as $list) {
            $ranking = $rankings->first(fn ($r) => $r->ranking_list_id === $list->id && $r->isIndividual())
                ?? $rankings->first(fn ($r) => $r->ranking_list_id === $list->id);

            if (! $ranking) {
                continue;
            }

            $histories = EloHistory::where('player_ranking_id', $ranking->id)
                ->orderBy('created_at')
                ->orderBy('id')
                ->get();

            $points = [];
            if ($histories->isNotEmpty()) {
                $sameDay = $histories->pluck('created_at')
                    ->filter()
                    ->map(fn ($dt) => $dt->toDateString())
                    ->unique()
                    ->count() <= 1;

                $first = $histories->first();
                $points[] = [
                    'label' => $sameDay ? 'Start' : ($first->created_at?->format('M d') ?? 'Start'),
                    'elo' => $first->old_elo,
                ];
                foreach ($histories as $index => $history) {
                    $points[] = [
                        'label' => $sameDay
                            ? (string) ($index + 1)
                            : ($history->created_at?->format('M d') ?? (string) ($index + 1)),
                        'elo' => $history->new_elo,
                    ];
                }
            } else {
                $points[] = [
                    'label' => 'Now',
                    'elo' => $ranking->elo_rating,
                ];
            }

            $eloChartData[] = [
                'listId' => $list->id,
                'title' => $list->title,
                'currentElo' => $ranking->elo_rating,
                'points' => $points,
            ];
        }

        $matches = MatchRecord::where(function ($query) use ($user) {
            $query->whereHas('side1Ranking', fn ($q) => $q->where('user_id', $user->id)->orWhere('player2_id', $user->id))
                ->orWhereHas('side2Ranking', fn ($q) => $q->where('user_id', $user->id)->orWhere('player2_id', $user->id));
        })
            ->with(['rankingList', 'side1Ranking.user', 'side1Ranking.player2', 'side2Ranking.user', 'side2Ranking.player2', 'sets'])
            ->orderBy('played_at', 'desc')
            ->take(50)
            ->get();

        return view('player_profile', compact('user', 'rankings', 'availableLists', 'eloChartData', 'matches'));
    }

    public function headToHead(Request $request)
    {
        $players = User::where('role', 'member')->where('status', 'active')->orderBy('name')->get();

        $player1Id = $request->get('p1');
        $player2Id = $request->get('p2');

        $player1 = $player1Id ? User::find($player1Id) : null;
        $player2 = $player2Id ? User::find($player2Id) : null;

        $matches = collect();
        $p1Wins = 0;
        $p2Wins = 0;

        if ($player1 && $player2) {
            $matches = MatchRecord::where(function ($query) use ($player1, $player2) {
                $query->whereHas('side1Ranking', fn ($q) => $q->where('user_id', $player1->id))
                      ->whereHas('side2Ranking', fn ($q) => $q->where('user_id', $player2->id));
            })->orWhere(function ($query) use ($player1, $player2) {
                $query->whereHas('side1Ranking', fn ($q) => $q->where('user_id', $player2->id))
                      ->whereHas('side2Ranking', fn ($q) => $q->where('user_id', $player1->id));
            })
            ->with(['rankingList', 'side1Ranking.user', 'side1Ranking.player2', 'side2Ranking.user', 'side2Ranking.player2', 'sets'])
            ->orderBy('played_at', 'desc')
            ->get();

            foreach ($matches as $m) {
                $p1IsSide1 = $m->side1Ranking?->user_id === $player1->id;
                if ($p1IsSide1 && $m->winner_side === 1) {
                    $p1Wins++;
                } elseif (! $p1IsSide1 && $m->winner_side === 2) {
                    $p1Wins++;
                } else {
                    $p2Wins++;
                }
            }
        }

        return view('head_to_head', compact('players', 'player1', 'player2', 'matches', 'p1Wins', 'p2Wins'));
    }
}
