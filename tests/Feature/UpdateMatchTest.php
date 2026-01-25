<?php

use App\Models\Game;
use App\Models\Standing;
use App\Models\Team;
use App\Models\Tournament;
use App\Models\User;

test('user can update match scores', function () {
    $user = User::factory()->create();
    $tournament = Tournament::factory()->create([
        'user_id' => $user->id,
        'current_week' => 1,
    ]);
    $team1 = Team::factory()->create();
    $team2 = Team::factory()->create();

    $tournament->teams()->attach([
        $team1->id => ['strength' => 10],
        $team2->id => ['strength' => 20],
    ]);

    $match = Game::factory()->create([
        'tournament_id' => $tournament->id,
        'home_team_id' => $team1->id,
        'away_team_id' => $team2->id,
        'home_score' => 1,
        'away_score' => 2,
        'is_played' => true,
        'week' => 1,
    ]);

    $response = $this->actingAs($user)->put(
        route('tournaments.matches.update', [$tournament, $match]),
        [
            'home_score' => 3,
            'away_score' => 1,
        ],
    );

    $response->assertRedirect();
    $response->assertSessionHas('success');

    $match->refresh();
    expect($match->home_score)->toBe(3);
    expect($match->away_score)->toBe(1);
    expect($match->is_played)->toBeTrue();
});

test('standings are recalculated after match update', function () {
    $user = User::factory()->create();
    $tournament = Tournament::factory()->create([
        'user_id' => $user->id,
        'current_week' => 1,
    ]);
    $team1 = Team::factory()->create();
    $team2 = Team::factory()->create();

    $tournament->teams()->attach([
        $team1->id => ['strength' => 10],
        $team2->id => ['strength' => 20],
    ]);

    $standingService = new \App\Services\StandingService;
    $standingService->createStandings($tournament);

    $match = Game::factory()->create([
        'tournament_id' => $tournament->id,
        'home_team_id' => $team1->id,
        'away_team_id' => $team2->id,
        'home_score' => 1,
        'away_score' => 2,
        'is_played' => true,
        'week' => 1,
    ]);

    $standingService->updateStandings($match);

    $this->actingAs($user)->put(
        route('tournaments.matches.update', [$tournament, $match]),
        [
            'home_score' => 3,
            'away_score' => 1,
        ],
    );

    $homeStanding = Standing::where('tournament_id', $tournament->id)
        ->where('team_id', $team1->id)
        ->first();

    expect($homeStanding->won)->toBe(1);
    expect($homeStanding->points)->toBe(3);
    expect($homeStanding->goals_for)->toBe(3);
    expect($homeStanding->goals_against)->toBe(1);
});

test('validation rejects negative scores', function () {
    $user = User::factory()->create();
    $tournament = Tournament::factory()->create([
        'user_id' => $user->id,
        'current_week' => 1,
    ]);
    $team1 = Team::factory()->create();
    $team2 = Team::factory()->create();

    $tournament->teams()->attach([
        $team1->id => ['strength' => 10],
        $team2->id => ['strength' => 20],
    ]);

    $match = Game::factory()->create([
        'tournament_id' => $tournament->id,
        'home_team_id' => $team1->id,
        'away_team_id' => $team2->id,
        'week' => 1,
    ]);

    $response = $this->actingAs($user)->put(
        route('tournaments.matches.update', [$tournament, $match]),
        [
            'home_score' => -1,
            'away_score' => 2,
        ],
    );

    $response->assertSessionHasErrors('home_score');
});

test('validation rejects non-integer scores', function () {
    $user = User::factory()->create();
    $tournament = Tournament::factory()->create([
        'user_id' => $user->id,
        'current_week' => 1,
    ]);
    $team1 = Team::factory()->create();
    $team2 = Team::factory()->create();

    $tournament->teams()->attach([
        $team1->id => ['strength' => 10],
        $team2->id => ['strength' => 20],
    ]);

    $match = Game::factory()->create([
        'tournament_id' => $tournament->id,
        'home_team_id' => $team1->id,
        'away_team_id' => $team2->id,
        'week' => 1,
    ]);

    $response = $this->actingAs($user)->put(
        route('tournaments.matches.update', [$tournament, $match]),
        [
            'home_score' => 'not-a-number',
            'away_score' => 2,
        ],
    );

    $response->assertSessionHasErrors('home_score');
});

test('unauthorized users cannot update matches', function () {
    $owner = User::factory()->create();
    $otherUser = User::factory()->create();
    $tournament = Tournament::factory()->create([
        'user_id' => $owner->id,
        'current_week' => 1,
    ]);
    $team1 = Team::factory()->create();
    $team2 = Team::factory()->create();

    $tournament->teams()->attach([
        $team1->id => ['strength' => 10],
        $team2->id => ['strength' => 20],
    ]);

    $match = Game::factory()->create([
        'tournament_id' => $tournament->id,
        'home_team_id' => $team1->id,
        'away_team_id' => $team2->id,
        'week' => 1,
    ]);

    $response = $this->actingAs($otherUser)->put(
        route('tournaments.matches.update', [$tournament, $match]),
        [
            'home_score' => 3,
            'away_score' => 1,
        ],
    );

    $response->assertForbidden();
});

test('can set scores to null to unplay a match', function () {
    $user = User::factory()->create();
    $tournament = Tournament::factory()->create([
        'user_id' => $user->id,
        'current_week' => 1,
    ]);
    $team1 = Team::factory()->create();
    $team2 = Team::factory()->create();

    $tournament->teams()->attach([
        $team1->id => ['strength' => 10],
        $team2->id => ['strength' => 20],
    ]);

    $match = Game::factory()->create([
        'tournament_id' => $tournament->id,
        'home_team_id' => $team1->id,
        'away_team_id' => $team2->id,
        'home_score' => 2,
        'away_score' => 1,
        'is_played' => true,
        'week' => 1,
    ]);

    $response = $this->actingAs($user)->put(
        route('tournaments.matches.update', [$tournament, $match]),
        [
            'home_score' => null,
            'away_score' => null,
        ],
    );

    $response->assertRedirect();
    $response->assertSessionHas('success');

    $match->refresh();
    expect($match->home_score)->toBeNull();
    expect($match->away_score)->toBeNull();
    expect($match->is_played)->toBeFalse();
});

test('returns error if match does not belong to tournament', function () {
    $user = User::factory()->create();
    $tournament1 = Tournament::factory()->create([
        'user_id' => $user->id,
        'current_week' => 1,
    ]);
    $tournament2 = Tournament::factory()->create([
        'user_id' => $user->id,
        'current_week' => 1,
    ]);
    $team1 = Team::factory()->create();
    $team2 = Team::factory()->create();

    $tournament1->teams()->attach([
        $team1->id => ['strength' => 10],
        $team2->id => ['strength' => 20],
    ]);

    $match = Game::factory()->create([
        'tournament_id' => $tournament1->id,
        'home_team_id' => $team1->id,
        'away_team_id' => $team2->id,
        'week' => 1,
    ]);

    $response = $this->actingAs($user)->put(
        route('tournaments.matches.update', [$tournament2, $match]),
        [
            'home_score' => 3,
            'away_score' => 1,
        ],
    );

    $response->assertRedirect();
    $response->assertSessionHas('error');
});

test('cannot edit matches in future weeks', function () {
    $user = User::factory()->create();
    $tournament = Tournament::factory()->create([
        'user_id' => $user->id,
        'current_week' => 1,
    ]);
    $team1 = Team::factory()->create();
    $team2 = Team::factory()->create();

    $tournament->teams()->attach([
        $team1->id => ['strength' => 10],
        $team2->id => ['strength' => 20],
    ]);

    $match = Game::factory()->create([
        'tournament_id' => $tournament->id,
        'home_team_id' => $team1->id,
        'away_team_id' => $team2->id,
        'week' => 2,
    ]);

    $response = $this->actingAs($user)->put(
        route('tournaments.matches.update', [$tournament, $match]),
        [
            'home_score' => 3,
            'away_score' => 1,
        ],
    );

    $response->assertRedirect();
    $response->assertSessionHas('error');
});
