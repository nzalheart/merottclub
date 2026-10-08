<?php

namespace Database\Seeders;

use App\Models\MatchRecord;
use App\Models\MatchSet;
use App\Models\PlayerRanking;
use App\Models\RankingList;
use App\Models\ThemeSetting;
use App\Models\User;
use App\Services\EloService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    private const MEMBER_COUNT = 100;

    private const MATCH_COUNT = 1000;

    /**
     * Seed the application's database with a large demo club.
     */
    public function run(): void
    {
        ThemeSetting::current()->saveColors(
            ThemeSetting::DEFAULT_PRIMARY,
            ThemeSetting::DEFAULT_SECONDARY,
        );

        $admin = User::firstOrCreate(
            ['email' => 'admin@ttclub.com'],
            [
                'name' => 'Club Admin',
                'nickname' => 'admin_boss',
                'password' => Hash::make('password'),
                'role' => 'admin',
                'phone' => '+977 9801234567',
                'address' => 'Kathmandu, Nepal',
                'status' => 'active',
            ]
        );

        $manager = User::firstOrCreate(
            ['email' => 'manager@ttclub.com'],
            [
                'name' => 'Scorekeeper Manager',
                'nickname' => 'scorekeeper',
                'password' => Hash::make('password'),
                'role' => 'manager',
                'phone' => '+977 9812345678',
                'address' => 'Lalitpur, Nepal',
                'status' => 'active',
            ]
        );

        $starPlayers = [
            ['name' => 'Ma Long', 'nickname' => 'dragon', 'email' => 'malong@ttclub.com', 'address' => 'Beijing, China', 'phone' => '+86 1380000001', 'dob' => '1988-10-20'],
            ['name' => 'Fan Zhendong', 'nickname' => 'little_fat', 'email' => 'fanzhendong@ttclub.com', 'address' => 'Guangzhou, China', 'phone' => '+86 1380000002', 'dob' => '1997-01-22'],
            ['name' => 'Wang Chuqin', 'nickname' => 'head_killer', 'email' => 'wangchuqin@ttclub.com', 'address' => 'Jilin, China', 'phone' => '+86 1380000003', 'dob' => '2000-05-11'],
            ['name' => 'Lin Shidong', 'nickname' => 'young_gun', 'email' => 'linshidong@ttclub.com', 'address' => 'Hainan, China', 'phone' => '+86 1380000004', 'dob' => '2005-04-18'],
            ['name' => 'Truls Moregard', 'nickname' => 'wizard', 'email' => 'truls@ttclub.com', 'address' => 'Hovmantorp, Sweden', 'phone' => '+46 701234567', 'dob' => '2002-02-16'],
            ['name' => 'Felix Lebrun', 'nickname' => 'penholder', 'email' => 'felix@ttclub.com', 'address' => 'Montpellier, France', 'phone' => '+33 601234567', 'dob' => '2006-09-12'],
            ['name' => 'Tomokazu Harimoto', 'nickname' => 'harimoto', 'email' => 'harimoto@ttclub.com', 'address' => 'Sendai, Japan', 'phone' => '+81 901234567', 'dob' => '2003-06-27'],
            ['name' => 'Hugo Calderano', 'nickname' => 'thriller', 'email' => 'hugo@ttclub.com', 'address' => 'Rio de Janeiro, Brazil', 'phone' => '+55 2190123456', 'dob' => '1996-06-22'],
        ];

        $members = collect();
        foreach ($starPlayers as $data) {
            $members->push(User::firstOrCreate(
                ['email' => $data['email']],
                [
                    'name' => $data['name'],
                    'nickname' => $data['nickname'],
                    'password' => Hash::make('password'),
                    'role' => 'member',
                    'phone' => $data['phone'],
                    'address' => $data['address'],
                    'date_of_birth' => $data['dob'],
                    'status' => 'active',
                ]
            ));
        }

        $remaining = self::MEMBER_COUNT - $members->count();
        if ($remaining > 0) {
            $extra = User::factory()
                ->member()
                ->count($remaining)
                ->create();
            $members = $members->concat($extra)->values();
        }

        $bestOf5 = RankingList::firstOrCreate(
            ['title' => 'Pro Singles League (Best of 5)'],
            [
                'description' => 'Official singles ranking list for advanced players in Best of 5 set format.',
                'type' => 'singles',
                'format' => 'best_of_5',
                'initial_elo' => 1200,
                'k_factor' => 32,
                'is_active' => true,
            ]
        );

        $bestOf3 = RankingList::firstOrCreate(
            ['title' => 'Weekend Sprint Circuit (Best of 3)'],
            [
                'description' => 'Fast-paced weekend singles circuit with Best of 3 sets.',
                'type' => 'singles',
                'format' => 'best_of_3',
                'initial_elo' => 1200,
                'k_factor' => 24,
                'is_active' => true,
            ]
        );

        $bestOf7 = RankingList::firstOrCreate(
            ['title' => 'Masters Best of 7'],
            [
                'description' => 'Long-format masters singles ladder (Best of 7).',
                'type' => 'singles',
                'format' => 'best_of_7',
                'initial_elo' => 1200,
                'k_factor' => 28,
                'is_active' => true,
            ]
        );

        $doubles = RankingList::firstOrCreate(
            ['title' => 'Club Championship Doubles'],
            [
                'description' => 'Official doubles tournament ranking list in Best of 5 format.',
                'type' => 'doubles',
                'format' => 'best_of_5',
                'initial_elo' => 1200,
                'k_factor' => 32,
                'is_active' => true,
            ]
        );

        $bo5Rankings = $this->enrollSingles($bestOf5, $members);
        $bo3Rankings = $this->enrollSingles($bestOf3, $members->take(80));
        $bo7Rankings = $this->enrollSingles($bestOf7, $members->take(60));
        $doublesRankings = $this->enrollDoubles($doubles, $members, teamCount: 30);

        // Fresh bulk history — wipe prior demo matches so re-seed is predictable.
        DB::table('elo_histories')->delete();
        DB::table('match_sets')->delete();
        DB::table('match_records')->delete();

        PlayerRanking::query()->update([
            'matches_played' => 0,
            'wins' => 0,
            'losses' => 0,
            'sets_won' => 0,
            'sets_lost' => 0,
        ]);

        // Reset ELOs to list initial before replaying history.
        foreach ([$bestOf5, $bestOf3, $bestOf7, $doubles] as $list) {
            PlayerRanking::where('ranking_list_id', $list->id)
                ->update(['elo_rating' => $list->initial_elo]);
        }

        $plans = [
            ['list' => $bestOf5, 'rankings' => $bo5Rankings, 'count' => 420],
            ['list' => $bestOf3, 'rankings' => $bo3Rankings, 'count' => 320],
            ['list' => $bestOf7, 'rankings' => $bo7Rankings, 'count' => 140],
            ['list' => $doubles, 'rankings' => $doublesRankings, 'count' => 120],
        ];

        $eloService = new EloService();
        $created = 0;
        $daysBack = 180;

        foreach ($plans as $plan) {
            $list = $plan['list'];
            $rankings = $plan['rankings']->values();
            $n = $rankings->count();

            if ($n < 2) {
                continue;
            }

            for ($i = 0; $i < $plan['count']; $i++) {
                $side1Index = random_int(0, $n - 1);
                do {
                    $side2Index = random_int(0, $n - 1);
                } while ($side2Index === $side1Index);

                $side1 = $rankings[$side1Index]->fresh();
                $side2 = $rankings[$side2Index]->fresh();

                $playedAt = now()
                    ->subDays(random_int(0, $daysBack))
                    ->subMinutes(random_int(0, 60 * 20))
                    ->seconds(0);

                $match = MatchRecord::create([
                    'ranking_list_id' => $list->id,
                    'match_type' => $list->type,
                    'side1_ranking_id' => $side1->id,
                    'side2_ranking_id' => $side2->id,
                    'manager_id' => $manager->id,
                    'status' => 'in_progress',
                    'played_at' => $playedAt,
                ]);

                $this->createSetsForMatch($match, $list->getMaxSetsToWin());
                $eloService->processMatchResult($match->fresh(['sets', 'rankingList', 'side1Ranking', 'side2Ranking']));
                $created++;
            }
        }

        $this->command?->info(sprintf(
            'Seeded %d members, %d ranking lists, %d matches (admin: %s / manager: %s, password: password).',
            $members->count(),
            4,
            $created,
            $admin->email,
            $manager->email,
        ));
    }

    /**
     * @param  \Illuminate\Support\Collection<int, User>  $members
     * @return \Illuminate\Support\Collection<int, PlayerRanking>
     */
    private function enrollSingles(RankingList $list, $members)
    {
        $rankings = collect();

        foreach ($members as $index => $user) {
            $rankings->push(PlayerRanking::firstOrCreate(
                [
                    'ranking_list_id' => $list->id,
                    'user_id' => $user->id,
                    'player2_id' => null,
                ],
                [
                    'elo_rating' => $list->initial_elo + ($index % 40) - 20,
                    'matches_played' => 0,
                    'wins' => 0,
                    'losses' => 0,
                    'sets_won' => 0,
                    'sets_lost' => 0,
                ]
            ));
        }

        return $rankings;
    }

    /**
     * @param  \Illuminate\Support\Collection<int, User>  $members
     * @return \Illuminate\Support\Collection<int, PlayerRanking>
     */
    private function enrollDoubles(RankingList $list, $members, int $teamCount)
    {
        $shuffled = $members->shuffle()->values();
        $rankings = collect();
        $pairCount = min($teamCount, intdiv($shuffled->count(), 2));

        for ($i = 0; $i < $pairCount; $i++) {
            $p1 = $shuffled[$i * 2];
            $p2 = $shuffled[$i * 2 + 1];

            // Canonical order so unique (list, user_id, player2_id) stays stable.
            if ($p1->id > $p2->id) {
                [$p1, $p2] = [$p2, $p1];
            }

            $rankings->push(PlayerRanking::firstOrCreate(
                [
                    'ranking_list_id' => $list->id,
                    'user_id' => $p1->id,
                    'player2_id' => $p2->id,
                ],
                [
                    'team_name' => "{$p1->name} / {$p2->name}",
                    'elo_rating' => $list->initial_elo + random_int(-30, 30),
                    'matches_played' => 0,
                    'wins' => 0,
                    'losses' => 0,
                    'sets_won' => 0,
                    'sets_lost' => 0,
                ]
            ));
        }

        return $rankings;
    }

    private function createSetsForMatch(MatchRecord $match, int $setsToWin): void
    {
        $side1Sets = 0;
        $side2Sets = 0;
        $setNumber = 1;

        while ($side1Sets < $setsToWin && $side2Sets < $setsToWin) {
            $winnerSide = random_int(1, 2);
            [$s1, $s2] = $this->randomSetScore($winnerSide);

            MatchSet::create([
                'match_record_id' => $match->id,
                'set_number' => $setNumber,
                'side1_score' => $s1,
                'side2_score' => $s2,
                'winner_side' => $winnerSide,
            ]);

            if ($winnerSide === 1) {
                $side1Sets++;
            } else {
                $side2Sets++;
            }

            $setNumber++;
        }
    }

    /**
     * @return array{0: int, 1: int}
     */
    private function randomSetScore(int $winnerSide): array
    {
        // Mostly standard 11-point sets; occasional deuce.
        if (random_int(1, 5) === 1) {
            $loser = random_int(10, 14);
            $winner = $loser + 2;
        } else {
            $winner = 11;
            $loser = random_int(0, 9);
        }

        return $winnerSide === 1 ? [$winner, $loser] : [$loser, $winner];
    }
}
