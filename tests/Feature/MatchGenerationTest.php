<?php

use App\Models\Team;
use App\Models\Tournament;
use App\Models\User;

test('matches are created when tournament is created with teams', function () {
    $user = User::factory()->create();
    $team1 = Team::factory()->create();
    $team2 = Team::factory()->create();
    $team3 = Team::factory()->create();

    $response = $this->actingAs($user)->post(route('tournaments.store'), [
        'name' => 'Test Tournament',
        'teams' => [
            ['id' => $team1->id, 'strength' => 10],
            ['id' => $team2->id, 'strength' => 20],
            ['id' => $team3->id, 'strength' => 30],
        ],
    ]);

    $tournament = Tournament::where('name', 'Test Tournament')->first();

    // 3 teams: each plays 2 others home and away = 3 * 2 = 6 matches
    expect($tournament->matches)->toHaveCount(6);
});

test('correct number of matches are created for round-robin tournament', function () {
    $user = User::factory()->create();
    $teams = Team::factory()->count(4)->create();

    $teamsData = $teams->map(fn ($team) => ['id' => $team->id, 'strength' => 10])->toArray();

    $response = $this->actingAs($user)->post(route('tournaments.store'), [
        'name' => 'Test Tournament',
        'teams' => $teamsData,
    ]);

    $tournament = Tournament::where('name', 'Test Tournament')->first();

    // 4 teams: each plays 3 others home and away = 4 * 3 = 12 matches
    expect($tournament->matches)->toHaveCount(12);
});

test('matches are distributed across weeks correctly', function () {
    $user = User::factory()->create();
    $teams = Team::factory()->count(4)->create();

    $teamsData = $teams->map(fn ($team) => ['id' => $team->id, 'strength' => 10])->toArray();

    $response = $this->actingAs($user)->post(route('tournaments.store'), [
        'name' => 'Test Tournament',
        'teams' => $teamsData,
    ]);

    $tournament = Tournament::where('name', 'Test Tournament')->first();
    $matches = $tournament->matches;

    $weeks = $matches->pluck('week')->unique()->sort()->values();
    expect($weeks->count())->toBeGreaterThan(0);

    foreach ($weeks as $week) {
        $weekMatches = $matches->where('week', $week);
        expect($weekMatches->count())->toBeGreaterThan(0);
    }
});

test('no team plays twice in same week', function () {
    $user = User::factory()->create();
    $teams = Team::factory()->count(4)->create();

    $teamsData = $teams->map(fn ($team) => ['id' => $team->id, 'strength' => 10])->toArray();

    $response = $this->actingAs($user)->post(route('tournaments.store'), [
        'name' => 'Test Tournament',
        'teams' => $teamsData,
    ]);

    $tournament = Tournament::where('name', 'Test Tournament')->first();
    $matches = $tournament->matches;

    $weeks = $matches->pluck('week')->unique();

    foreach ($weeks as $week) {
        $weekMatches = $matches->where('week', $week);
        $teamIdsInWeek = collect();

        foreach ($weekMatches as $match) {
            $teamIdsInWeek->push($match->home_team_id);
            $teamIdsInWeek->push($match->away_team_id);
        }

        expect($teamIdsInWeek->duplicates()->count())->toBe(0);
    }
});

test('matches are not created if tournament has less than 2 teams', function () {
    $user = User::factory()->create();
    $team1 = Team::factory()->create();

    $response = $this->actingAs($user)->post(route('tournaments.store'), [
        'name' => 'Test Tournament',
        'teams' => [
            ['id' => $team1->id, 'strength' => 10],
        ],
    ]);

    $tournament = Tournament::where('name', 'Test Tournament')->first();

    expect($tournament->matches)->toHaveCount(0);
});

test('matches are not created if tournament has no teams', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('tournaments.store'), [
        'name' => 'Test Tournament',
        'teams' => [],
    ]);

    $tournament = Tournament::where('name', 'Test Tournament')->first();

    expect($tournament->matches)->toHaveCount(0);
});

test('matches are deleted when tournament is deleted', function () {
    $user = User::factory()->create();
    $team1 = Team::factory()->create();
    $team2 = Team::factory()->create();

    $response = $this->actingAs($user)->post(route('tournaments.store'), [
        'name' => 'Test Tournament',
        'teams' => [
            ['id' => $team1->id, 'strength' => 10],
            ['id' => $team2->id, 'strength' => 20],
        ],
    ]);

    $tournament = Tournament::where('name', 'Test Tournament')->first();
    $matchIds = $tournament->matches->pluck('id')->toArray();

    $this->actingAs($user)->delete(route('tournaments.destroy', $tournament));

    foreach ($matchIds as $matchId) {
        $this->assertDatabaseMissing('matches', ['id' => $matchId]);
    }
});

test('matches are regenerated when tournament teams are updated', function () {
    $user = User::factory()->create();
    $team1 = Team::factory()->create();
    $team2 = Team::factory()->create();
    $team3 = Team::factory()->create();
    $team4 = Team::factory()->create();

    $tournament = Tournament::factory()->create(['user_id' => $user->id, 'status' => 'created']);

    $response = $this->actingAs($user)->put(route('tournaments.update', $tournament), [
        'name' => 'Updated Tournament',
        'teams' => [
            ['id' => $team1->id, 'strength' => 10],
            ['id' => $team2->id, 'strength' => 20],
            ['id' => $team3->id, 'strength' => 30],
            ['id' => $team4->id, 'strength' => 40],
        ],
    ]);

    $tournament->refresh();

    // 4 teams: each plays 3 others home and away = 4 * 3 = 12 matches
    expect($tournament->matches)->toHaveCount(12);
});

test('matches are generated efficiently for large number of teams', function () {
    $user = User::factory()->create();
    $teams = Team::factory()->count(20)->create();

    $teamsData = $teams->map(fn ($team) => ['id' => $team->id, 'strength' => 10])->toArray();

    $startTime = microtime(true);

    $response = $this->actingAs($user)->post(route('tournaments.store'), [
        'name' => 'Large Tournament',
        'teams' => $teamsData,
    ]);

    $endTime = microtime(true);
    $executionTime = $endTime - $startTime;

    $tournament = Tournament::where('name', 'Large Tournament')->first();

    // 20 teams: each plays 19 others home and away = 20 * 19 = 380 matches
    expect($tournament->matches)->toHaveCount(380);

    // Should have 38 weeks (2 * 19)
    $weeks = $tournament->matches->pluck('week')->unique();
    expect($weeks->count())->toBe(38);

    // Each team should play 38 matches (19 home + 19 away)
    foreach ($teams as $team) {
        $homeMatches = $tournament->matches->where('home_team_id', $team->id)->count();
        $awayMatches = $tournament->matches->where('away_team_id', $team->id)->count();
        expect($homeMatches)->toBe(19);
        expect($awayMatches)->toBe(19);
    }

    // Should complete in reasonable time (less than 5 seconds)
    expect($executionTime)->toBeLessThan(5.0);

    // Verify no team plays twice in same week
    $matches = $tournament->matches;
    $weeks = $matches->pluck('week')->unique();

    foreach ($weeks as $week) {
        $weekMatches = $matches->where('week', $week);
        $teamIdsInWeek = collect();

        foreach ($weekMatches as $match) {
            $teamIdsInWeek->push($match->home_team_id);
            $teamIdsInWeek->push($match->away_team_id);
        }

        expect($teamIdsInWeek->duplicates()->count())->toBe(0);
    }
});

test('return fixtures have reversed home and away', function () {
    $user = User::factory()->create();
    $teams = Team::factory()->count(4)->create();

    $teamsData = $teams->map(fn ($team) => ['id' => $team->id, 'strength' => 10])->toArray();

    $response = $this->actingAs($user)->post(route('tournaments.store'), [
        'name' => 'Test Tournament',
        'teams' => $teamsData,
    ]);

    $tournament = Tournament::where('name', 'Test Tournament')->first();
    $matches = $tournament->matches;

    // For 4 teams: first half is weeks 1-3, second half is weeks 4-6
    $firstHalfWeeks = 3;

    // Check that each match in first half has a reversed match in second half
    for ($week = 1; $week <= $firstHalfWeeks; $week++) {
        $returnWeek = $firstHalfWeeks + $week;
        $firstHalfMatches = $matches->where('week', $week);
        $returnMatches = $matches->where('week', $returnWeek);

        foreach ($firstHalfMatches as $firstMatch) {
            $returnMatch = $returnMatches->first(function ($match) use ($firstMatch) {
                return $match->home_team_id === $firstMatch->away_team_id &&
                       $match->away_team_id === $firstMatch->home_team_id;
            });

            expect($returnMatch)->not->toBeNull();
        }
    }
});

test('all matches have correct structure', function () {
    $user = User::factory()->create();
    $team1 = Team::factory()->create();
    $team2 = Team::factory()->create();

    $response = $this->actingAs($user)->post(route('tournaments.store'), [
        'name' => 'Test Tournament',
        'teams' => [
            ['id' => $team1->id, 'strength' => 10],
            ['id' => $team2->id, 'strength' => 20],
        ],
    ]);

    $tournament = Tournament::where('name', 'Test Tournament')->first();
    $match = $tournament->matches->first();

    expect($match->tournament_id)->toBe($tournament->id);
    expect($match->home_team_id)->toBeIn([$team1->id, $team2->id]);
    expect($match->away_team_id)->toBeIn([$team1->id, $team2->id]);
    expect($match->home_team_id)->not->toBe($match->away_team_id);
    expect($match->home_score)->toBeNull();
    expect($match->away_score)->toBeNull();
    expect($match->week)->toBeGreaterThan(0);
    expect($match->is_played)->toBeFalse();
});
