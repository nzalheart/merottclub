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

        $this->call(AdminSeeder::class);
        $admin = User::where('email', 'admin@ttclub.com')->firstOrFail();

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
        $doublesIndividuals = $this->enrollDoublesIndividuals($doubles, $members);

        // Fresh bulk history — wipe prior demo matches so re-seed is predictable.
        DB::table('elo_histories')->delete();
        DB::table('match_sets')->delete();
        DB::table('match_records')->delete();

        // Drop legacy pair-only rows so re-seed starts from individuals + pairs formed by matches.
        PlayerRanking::where('ranking_list_id', $doubles->id)->pairs()->delete();

        PlayerRanking::query()->update([
            'matches_played' => 0,
            'wins' => 0,
            'losses' => 0,
            'sets_won' => 0,
            'sets_lost' => 0,
            'current_streak' => 0,
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
            ['list' => $doubles, 'rankings' => $doublesIndividuals, 'count' => 120],
        ];

        $eloService = new EloService();
        $created = 0;
        $daysBack = 180;

        foreach ($plans as $plan) {
            $list = $plan['list'];
            $rankings = $plan['rankings']->values();
            $n = $rankings->count();
            $minPlayers = $list->type === 'doubles' ? 4 : 2;

            if ($n < $minPlayers) {
                continue;
            }

            for ($i = 0; $i < $plan['count']; $i++) {
                if ($list->type === 'doubles') {
                    [$side1, $side2] = $this->pickDoublesSides($list, $rankings);
                } else {
                    $side1Index = random_int(0, $n - 1);
                    do {
                        $side2Index = random_int(0, $n - 1);
                    } while ($side2Index === $side1Index);

                    $side1 = $rankings[$side1Index]->fresh();
                    $side2 = $rankings[$side2Index]->fresh();
                }

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
                    'current_streak' => 0,
                ]
            ));
        }

        return $rankings;
    }

    /**
     * Enroll every member as an individual on a doubles list.
     * Pair rankings are created on demand when matches are seeded.
     *
     * @param  \Illuminate\Support\Collection<int, User>  $members
     * @return \Illuminate\Support\Collection<int, PlayerRanking>
     */
    private function enrollDoublesIndividuals(RankingList $list, $members)
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
                    'current_streak' => 0,
                ]
            ));
        }

        return $rankings;
    }

    /**
     * Pick four distinct enrolled individuals and resolve/create pair sides.
     *
     * @param  \Illuminate\Support\Collection<int, PlayerRanking>  $individuals
     * @return array{0: PlayerRanking, 1: PlayerRanking}
     */
    private function pickDoublesSides(RankingList $list, $individuals): array
    {
        $n = $individuals->count();
        $indexes = [];

        while (count($indexes) < 4) {
            $pick = random_int(0, $n - 1);
            if (! in_array($pick, $indexes, true)) {
                $indexes[] = $pick;
            }
        }

        $p1 = $individuals[$indexes[0]]->fresh();
        $p2 = $individuals[$indexes[1]]->fresh();
        $p3 = $individuals[$indexes[2]]->fresh();
        $p4 = $individuals[$indexes[3]]->fresh();

        $side1 = PlayerRanking::findOrCreatePair($list, (int) $p1->user_id, (int) $p2->user_id);
        $side2 = PlayerRanking::findOrCreatePair($list, (int) $p3->user_id, (int) $p4->user_id);

        return [$side1, $side2];
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
