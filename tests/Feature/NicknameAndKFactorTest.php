<?php

namespace Tests\Feature;

use App\Models\RankingList;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NicknameAndKFactorTest extends TestCase
{
    use RefreshDatabase;

    public function test_member_has_unique_nickname(): void
    {
        $user1 = User::factory()->create([
            'name' => 'John Doe',
            'nickname' => 'johndoe',
            'role' => 'member',
        ]);

        $this->assertEquals('johndoe', $user1->nickname);

        $this->expectException(\Illuminate\Database\QueryException::class);

        User::factory()->create([
            'name' => 'Jane Doe',
            'nickname' => 'johndoe', // duplicate nickname should fail unique constraint
            'role' => 'member',
        ]);
    }

    public function test_public_urls_do_not_expose_k_factor(): void
    {
        $list = RankingList::create([
            'title' => 'Secret K-Factor League',
            'type' => 'singles',
            'format' => 'best_of_5',
            'initial_elo' => 1200,
            'k_factor' => 64,
            'is_active' => true,
        ]);

        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertDontSee('K-FACTOR');
    }
}
