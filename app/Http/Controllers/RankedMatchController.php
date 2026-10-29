<?php

namespace App\Http\Controllers;

use App\Models\MatchChallenge;
use App\Models\PlayerRanking;
use App\Models\RankingList;
use App\Models\User;
use App\Services\RankedMatchService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use InvalidArgumentException;

class RankedMatchController extends Controller
{
    public function __construct(private RankedMatchService $matches) {}

    public function create(Request $request): View
    {
        $lists = RankingList::query()->where('is_active', true)->orderBy('id')->get();
        $list = $lists->firstWhere('id', (int) $request->query('list_id')) ?? $lists->first();

        $opponent = null;
        if ($request->filled('opponent')) {
            $opponent = User::query()->activePlayers()->find($request->query('opponent'));
            if ($opponent && $opponent->is($request->user())) {
                $opponent = null;
            }
        }

        $members = User::query()->activePlayers()->orderBy('name')->get();
        $referees = User::query()->activeReferees()->orderBy('name')->get();
        $elos = $list
            ? PlayerRanking::query()
                ->where('ranking_list_id', $list->id)
                ->individuals()
                ->pluck('elo_rating', 'user_id')
            : collect();

        return view('matches.start', compact('lists', 'list', 'opponent', 'members', 'referees', 'elos'));
    }

    public function store(Request $request): RedirectResponse
    {
        $list = RankingList::query()
            ->where('is_active', true)
            ->find($request->input('ranking_list_id'));

        $rules = [
            'ranking_list_id' => ['required', 'integer'],
            'opponent_id' => ['required', 'integer', 'exists:users,id'],
            'referee_id' => ['nullable', 'integer', 'exists:users,id'],
            'partner_id' => ['nullable', 'integer', 'exists:users,id'],
            'opponent_partner_id' => ['nullable', 'integer', 'exists:users,id'],
        ];

        if ($list?->type === 'doubles') {
            $rules['partner_id'] = ['required', 'integer', 'exists:users,id'];
            $rules['opponent_partner_id'] = ['required', 'integer', 'exists:users,id'];
        }

        $data = $request->validate($rules);

        if (! $list) {
            return back()->withInput()->withErrors([
                'match' => 'Choose an active ranking list.',
            ]);
        }

        return $this->attempt(function () use ($request, $list, $data) {
            $challenge = $this->matches->start(
                $request->user(),
                $list,
                (int) $data['opponent_id'],
                isset($data['referee_id']) ? (int) $data['referee_id'] : null,
                isset($data['partner_id']) ? (int) $data['partner_id'] : null,
                isset($data['opponent_partner_id']) ? (int) $data['opponent_partner_id'] : null,
            );

            $status = $challenge->hasReferee()
                ? 'Match started. The opponent and the referee can see it after they refresh. Anyone can enter the scores. Updates are not live yet.'
                : 'Match started without a referee. The players can see it after they refresh. Anyone can enter the scores. The match is recorded when every player accepts the score. Updates are not live yet.';

            return redirect()
                ->route('matches.challenge', $challenge)
                ->with('status', $status);
        });
    }

    public function index(Request $request): View
    {
        $challenges = MatchChallenge::query()
            ->visibleTo((int) $request->user()->id)
            ->with(['rankingList', 'participants.user', 'referee'])
            ->latest()
            ->get();

        return view('matches.requests', compact('challenges'));
    }

    public function show(MatchChallenge $challenge): View
    {
        $challenge->load(['rankingList', 'participants.user', 'referee', 'initiator', 'matchRecord']);

        return view('matches.show', compact('challenge'));
    }

    public function acceptMatch(Request $request, MatchChallenge $challenge): RedirectResponse
    {
        return $this->attempt(function () use ($request, $challenge) {
            $challenge = $this->matches->acceptMatch($challenge, $request->user());

            return redirect()
                ->route('matches.challenge', $challenge)
                ->with('status', $this->statusAfter($challenge, 'You accepted the match. Ask the others to refresh this page — updates are not live yet.'));
        });
    }

    public function saveScores(Request $request, MatchChallenge $challenge): RedirectResponse
    {
        $data = $request->validate([
            'sets' => ['required', 'array', 'min:1'],
            'sets.*.side1_score' => ['nullable', 'integer', 'min:0', 'max:99'],
            'sets.*.side2_score' => ['nullable', 'integer', 'min:0', 'max:99'],
            'intent' => ['nullable', 'in:save,accept'],
        ]);

        return $this->attempt(function () use ($request, $challenge, $data) {
            $challenge = $this->matches->saveScores($challenge, $data['sets'], $request->user());
            $accepting = ($data['intent'] ?? 'save') === 'accept';

            if ($accepting && $challenge->isOpen()) {
                $challenge = $this->matches->acceptScore($challenge, $request->user());
            }

            if ($challenge->status === MatchChallenge::STATUS_COMPLETED) {
                $message = 'Everyone accepted the score. The match is now on the rankings.';
            } elseif ($accepting) {
                $message = 'You accepted the score. It is added to the rankings only after every participant accepts. Ask the others to refresh this page.';
            } else {
                $message = 'Scores saved. Changing them clears previous score acceptances. Ask the others to refresh this page.';
            }

            return redirect()
                ->route('matches.challenge', $challenge)
                ->with('status', $message);
        });
    }

    public function acceptScore(Request $request, MatchChallenge $challenge): RedirectResponse
    {
        return $this->attempt(function () use ($request, $challenge) {
            $challenge = $this->matches->acceptScore($challenge, $request->user());

            $status = $this->statusAfter(
                $challenge,
                'You accepted the score. It is added to the rankings only after every participant accepts. Ask the others to refresh this page.'
            );

            $redirect = $request->input('return_to') === 'requests'
                ? redirect()->route('matches.requests')
                : redirect()->route('matches.challenge', $challenge);

            return $redirect->with('status', $status);
        });
    }

    public function decline(Request $request, MatchChallenge $challenge): RedirectResponse
    {
        return $this->attempt(function () use ($request, $challenge) {
            $challenge = $this->matches->decline($challenge, $request->user());

            return redirect()
                ->route('matches.challenge', $challenge)
                ->with('status', 'You declined the match. It will not be added to the rankings.');
        });
    }

    public function cancel(Request $request, MatchChallenge $challenge): RedirectResponse
    {
        return $this->attempt(function () use ($request, $challenge) {
            $challenge = $this->matches->cancel($challenge, $request->user());

            return redirect()
                ->route('matches.challenge', $challenge)
                ->with('status', 'You cancelled the match. It will not be added to the rankings.');
        });
    }

    protected function statusAfter(MatchChallenge $challenge, string $waiting): string
    {
        if ($challenge->status === MatchChallenge::STATUS_COMPLETED) {
            return 'Everyone accepted the score. The match is now on the rankings.';
        }

        return $waiting;
    }

    protected function attempt(callable $callback): RedirectResponse
    {
        try {
            return $callback();
        } catch (AuthorizationException $exception) {
            abort(403, $exception->getMessage());
        } catch (InvalidArgumentException $exception) {
            return back()->withInput()->withErrors([
                'match' => $exception->getMessage(),
            ]);
        }
    }
}
