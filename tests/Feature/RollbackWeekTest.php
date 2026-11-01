<?php

use App\Enums\TournamentStatus;
use App\Models\Game;
use App\Models\Standing;
use App\Models\Team;
use App\Models\Tournament;
use App\Models\User;

test('user can rollback to a previous week', function () {
    $user = User::factory()->create();
    $tournament = Tournament::factory()->create([
        'user_id' => $user->id,
        'current_week' => 3,
        'total_weeks' => 5,
        'status' => TournamentStatus::IN_PROGRESS,
    ]);
    $team1 = Team::factory()->create();
    $team2 = Team::factory()->create();

    $tournament->teams()->attach([
        $team1->id => ['strength' => 10],
        $team2->id => ['strength' => 20],
    ]);

    $standingService = new \App\Services\StandingService;
    $standingService->createStandings($tournament);

    // Create matches for week 1, 2, and 3
    // Note: Can't have same team pair twice due to unique constraint, so we use different combinations
    $match1 = Game::factory()->create([
        'tournament_id' => $tournament->id,
        'home_team_id' => $team1->id,
        'away_team_id' => $team2->id,
        'home_score' => 2,
        'away_score' => 1,
        'is_played' => true,
        'week' => 1,
    ]);

    // For week 2, we need a different match - but with only 2 teams, we can't create another match
    // So we'll just test with matches that don't violate the constraint
    // Actually, the constraint allows reverse matches (team2 vs team1), so that's fine
    $match2 = Game::factory()->create([
        'tournament_id' => $tournament->id,
        'home_team_id' => $team2->id,
        'away_team_id' => $team1->id,
        'home_score' => 3,
        'away_score' => 0,
        'is_played' => true,
        'week' => 2,
    ]);

    // Week 3 would violate constraint if same as week 1, but we already have team1 vs team2
    // So we skip week 3 match for this test

    $standingService->recalculateAllStandings($tournament);

    $response = $this->actingAs($user)->post(
        route('tournaments.rollback-week', [$tournament, 2]),
    );

    $response->assertRedirect();
    $response->assertSessionHas('success');

    $tournament->refresh();
    expect($tournament->current_week)->toBe(2);
    expect($tournament->status)->toBe(TournamentStatus::IN_PROGRESS);

    $match2->refresh();
    expect($match2->is_played)->toBeTrue();
    expect($match2->home_score)->toBe(3);

    $match1->refresh();
    expect($match1->is_played)->toBeTrue();
    expect($match1->home_score)->toBe(2);
});

test('matches in later weeks are reset on rollback', function () {
    $user = User::factory()->create();
    $tournament = Tournament::factory()->create([
        'user_id' => $user->id,
        'current_week' => 3,
        'total_weeks' => 5,
    ]);
    $team1 = Team::factory()->create();
    $team2 = Team::factory()->create();

    $tournament->teams()->attach([
        $team1->id => ['strength' => 10],
        $team2->id => ['strength' => 20],
    ]);

    // Create matches for weeks 1, 2, and 3
    $match1 = Game::factory()->create([
        'tournament_id' => $tournament->id,
        'home_team_id' => $team1->id,
        'away_team_id' => $team2->id,
        'home_score' => 2,
        'away_score' => 1,
        'is_played' => true,
        'week' => 1,
    ]);

    $match2 = Game::factory()->create([
        'tournament_id' => $tournament->id,
        'home_team_id' => $team2->id,
        'away_team_id' => $team1->id,
        'home_score' => 3,
        'away_score' => 0,
        'is_played' => true,
        'week' => 2,
    ]);

    // Can't create match3 with same teams due to unique constraint
    // So we'll test rollback to week 1, which should reset match2
    $this->actingAs($user)->post(
        route('tournaments.rollback-week', [$tournament, 1]),
    );

    $match2->refresh();
    expect($match2->is_played)->toBeFalse();
    expect($match2->home_score)->toBeNull();
    expect($match2->away_score)->toBeNull();

    $match1->refresh();
    expect($match1->is_played)->toBeTrue();
});

test('standings are recalculated correctly after rollback', function () {
    $user = User::factory()->create();
    $tournament = Tournament::factory()->create([
        'user_id' => $user->id,
        'current_week' => 3,
        'total_weeks' => 5,
    ]);
    $team1 = Team::factory()->create();
    $team2 = Team::factory()->create();

    $tournament->teams()->attach([
        $team1->id => ['strength' => 10],
        $team2->id => ['strength' => 20],
    ]);

    $standingService = new \App\Services\StandingService;
    $standingService->createStandings($tournament);

    // Week 1: team1 wins 2-1 (team1 home vs team2 away)
    $match1 = Game::factory()->create([
        'tournament_id' => $tournament->id,
        'home_team_id' => $team1->id,
        'away_team_id' => $team2->id,
        'home_score' => 2,
        'away_score' => 1,
        'is_played' => true,
        'week' => 1,
    ]);

    // Week 2: team2 wins 3-0 (team2 home vs team1 away) - different from week 1
    $match2 = Game::factory()->create([
        'tournament_id' => $tournament->id,
        'home_team_id' => $team2->id,
        'away_team_id' => $team1->id,
        'home_score' => 3,
        'away_score' => 0,
        'is_played' => true,
        'week' => 2,
    ]);

    // Week 3: Can't create team1 vs team2 again (unique constraint)
    // So we'll test rollback to week 2, which should keep match1 and match2
    // and remove match3 if it existed, but we can't create it
    // Let's just test that rollback to week 2 works correctly

    $standingService->recalculateAllStandings($tournament);

    // Rollback to week 1 (before match2)
    $this->actingAs($user)->post(
        route('tournaments.rollback-week', [$tournament, 1]),
    );

    $team1Standing = Standing::where('tournament_id', $tournament->id)
        ->where('team_id', $team1->id)
        ->first();

    $team2Standing = Standing::where('tournament_id', $tournament->id)
        ->where('team_id', $team2->id)
        ->first();

    // After rollback to week 1, only match1 should count
    expect($team1Standing->played)->toBe(1);
    expect($team1Standing->won)->toBe(1);
    expect($team1Standing->lost)->toBe(0);
    expect($team1Standing->points)->toBe(3);

    expect($team2Standing->played)->toBe(1);
    expect($team2Standing->won)->toBe(0);
    expect($team2Standing->lost)->toBe(1);
    expect($team2Standing->points)->toBe(0);
});

test('tournament current week is updated on rollback', function () {
    $user = User::factory()->create();
    $tournament = Tournament::factory()->create([
        'user_id' => $user->id,
        'current_week' => 5,
        'total_weeks' => 5,
        'status' => TournamentStatus::COMPLETED,
    ]);
    $team1 = Team::factory()->create();
    $team2 = Team::factory()->create();

    $tournament->teams()->attach([
        $team1->id => ['strength' => 10],
        $team2->id => ['strength' => 20],
    ]);

    $this->actingAs($user)->post(
        route('tournaments.rollback-week', [$tournament, 3]),
    );

    $tournament->refresh();
    expect($tournament->current_week)->toBe(3);
    expect($tournament->status)->toBe(TournamentStatus::IN_PROGRESS);
});

test('cannot rollback to future weeks', function () {
    $user = User::factory()->create();
    $tournament = Tournament::factory()->create([
        'user_id' => $user->id,
        'current_week' => 3,
        'total_weeks' => 5,
    ]);
    $team1 = Team::factory()->create();
    $team2 = Team::factory()->create();

    $tournament->teams()->attach([
        $team1->id => ['strength' => 10],
        $team2->id => ['strength' => 20],
    ]);

    $response = $this->actingAs($user)->post(
        route('tournaments.rollback-week', [$tournament, 5]),
    );

    $response->assertRedirect();
    $response->assertSessionHasErrors('week');
});

test('cannot rollback to negative week', function () {
    $user = User::factory()->create();
    $tournament = Tournament::factory()->create([
        'user_id' => $user->id,
        'current_week' => 3,
        'total_weeks' => 5,
    ]);
    $team1 = Team::factory()->create();
    $team2 = Team::factory()->create();

    $tournament->teams()->attach([
        $team1->id => ['strength' => 10],
        $team2->id => ['strength' => 20],
    ]);

    $response = $this->actingAs($user)->post(
        route('tournaments.rollback-week', [$tournament, -1]),
    );

    $response->assertRedirect();
    $response->assertSessionHasErrors('week');
});

test('unauthorized users cannot rollback', function () {
    $owner = User::factory()->create();
    $otherUser = User::factory()->create();
    $tournament = Tournament::factory()->create([
        'user_id' => $owner->id,
        'current_week' => 3,
        'total_weeks' => 5,
    ]);
    $team1 = Team::factory()->create();
    $team2 = Team::factory()->create();

    $tournament->teams()->attach([
        $team1->id => ['strength' => 10],
        $team2->id => ['strength' => 20],
    ]);

    $response = $this->actingAs($otherUser)->post(
        route('tournaments.rollback-week', [$tournament, 2]),
    );

    $response->assertForbidden();
});

test('tournament status is updated correctly after rollback', function () {
    $user = User::factory()->create();
    $tournament = Tournament::factory()->create([
        'user_id' => $user->id,
        'current_week' => 5,
        'total_weeks' => 5,
        'status' => TournamentStatus::COMPLETED,
    ]);
    $team1 = Team::factory()->create();
    $team2 = Team::factory()->create();

    $tournament->teams()->attach([
        $team1->id => ['strength' => 10],
        $team2->id => ['strength' => 20],
    ]);

    // Rollback to week 5 (last week) - should still be completed
    $this->actingAs($user)->post(
        route('tournaments.rollback-week', [$tournament, 5]),
    );

    $tournament->refresh();
    expect($tournament->status)->toBe(TournamentStatus::COMPLETED);

    // Rollback to week 4 - should be in progress
    $this->actingAs($user)->post(
        route('tournaments.rollback-week', [$tournament, 4]),
    );

    $tournament->refresh();
    expect($tournament->status)->toBe(TournamentStatus::IN_PROGRESS);

    // Rollback to week 0 - should be created
    $this->actingAs($user)->post(
        route('tournaments.rollback-week', [$tournament, 0]),
    );

    $tournament->refresh();
    expect($tournament->status)->toBe(TournamentStatus::CREATED);
});
