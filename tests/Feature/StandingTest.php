<?php

use App\Models\Game;
use App\Models\Standing;
use App\Models\Team;
use App\Models\Tournament;
use App\Services\StandingService;

test('standings are updated when a game is updated with home win', function () {
    $tournament = Tournament::factory()->create();
    $team1 = Team::factory()->create();
    $team2 = Team::factory()->create();

    $tournament->teams()->attach([
        $team1->id => ['strength' => 10],
        $team2->id => ['strength' => 20],
    ]);

    $standingService = new StandingService;
    $standingService->createStandings($tournament);

    $game = Game::factory()->create([
        'tournament_id' => $tournament->id,
        'home_team_id' => $team1->id,
        'away_team_id' => $team2->id,
        'home_score' => 2,
        'away_score' => 1,
        'is_played' => true,
        'week' => 1,
    ]);

    $standingService->updateStandings($game);

    $homeStanding = Standing::where('tournament_id', $tournament->id)->where('team_id', $team1->id)->first();
    expect($homeStanding->played)->toBe(1);
    expect($homeStanding->won)->toBe(1);
    expect($homeStanding->drawn)->toBe(0);
    expect($homeStanding->lost)->toBe(0);
    expect($homeStanding->goals_for)->toBe(2);
    expect($homeStanding->goals_against)->toBe(1);
    expect($homeStanding->goal_difference)->toBe(1);
    expect($homeStanding->points)->toBe(3);

    $awayStanding = Standing::where('tournament_id', $tournament->id)->where('team_id', $team2->id)->first();
    expect($awayStanding->played)->toBe(1);
    expect($awayStanding->won)->toBe(0);
    expect($awayStanding->drawn)->toBe(0);
    expect($awayStanding->lost)->toBe(1);
    expect($awayStanding->goals_for)->toBe(1);
    expect($awayStanding->goals_against)->toBe(2);
    expect($awayStanding->goal_difference)->toBe(-1);
    expect($awayStanding->points)->toBe(0);
});

test('standings are updated when a game is updated with away win', function () {
    $tournament = Tournament::factory()->create();
    $team1 = Team::factory()->create();
    $team2 = Team::factory()->create();

    $tournament->teams()->attach([
        $team1->id => ['strength' => 10],
        $team2->id => ['strength' => 20],
    ]);

    $standingService = new StandingService;
    $standingService->createStandings($tournament);

    $game = Game::factory()->create([
        'tournament_id' => $tournament->id,
        'home_team_id' => $team1->id,
        'away_team_id' => $team2->id,
        'home_score' => 0,
        'away_score' => 3,
        'is_played' => true,
        'week' => 1,
    ]);

    $standingService->updateStandings($game);

    $homeStanding = Standing::where('tournament_id', $tournament->id)->where('team_id', $team1->id)->first();
    expect($homeStanding->won)->toBe(0);
    expect($homeStanding->lost)->toBe(1);
    expect($homeStanding->points)->toBe(0);

    $awayStanding = Standing::where('tournament_id', $tournament->id)->where('team_id', $team2->id)->first();
    expect($awayStanding->won)->toBe(1);
    expect($awayStanding->lost)->toBe(0);
    expect($awayStanding->points)->toBe(3);
});

test('standings are updated when a game is updated with draw', function () {
    $tournament = Tournament::factory()->create();
    $team1 = Team::factory()->create();
    $team2 = Team::factory()->create();

    $tournament->teams()->attach([
        $team1->id => ['strength' => 10],
        $team2->id => ['strength' => 20],
    ]);

    $standingService = new StandingService;
    $standingService->createStandings($tournament);

    $game = Game::factory()->create([
        'tournament_id' => $tournament->id,
        'home_team_id' => $team1->id,
        'away_team_id' => $team2->id,
        'home_score' => 2,
        'away_score' => 2,
        'is_played' => true,
        'week' => 1,
    ]);

    $standingService->updateStandings($game);

    $homeStanding = Standing::where('tournament_id', $tournament->id)->where('team_id', $team1->id)->first();
    expect($homeStanding->won)->toBe(0);
    expect($homeStanding->drawn)->toBe(1);
    expect($homeStanding->lost)->toBe(0);
    expect($homeStanding->points)->toBe(1);

    $awayStanding = Standing::where('tournament_id', $tournament->id)->where('team_id', $team2->id)->first();
    expect($awayStanding->won)->toBe(0);
    expect($awayStanding->drawn)->toBe(1);
    expect($awayStanding->lost)->toBe(0);
    expect($awayStanding->points)->toBe(1);
});

test('standings are recalculated from scratch when game is updated', function () {
    $tournament = Tournament::factory()->create();
    $team1 = Team::factory()->create();
    $team2 = Team::factory()->create();

    $tournament->teams()->attach([
        $team1->id => ['strength' => 10],
        $team2->id => ['strength' => 20],
    ]);

    $standingService = new StandingService;
    $standingService->createStandings($tournament);

    // Create first game: team1 wins 2-1
    $game1 = Game::factory()->create([
        'tournament_id' => $tournament->id,
        'home_team_id' => $team1->id,
        'away_team_id' => $team2->id,
        'home_score' => 2,
        'away_score' => 1,
        'is_played' => true,
        'week' => 1,
    ]);

    $standingService->updateStandings($game1);

    $homeStanding = Standing::where('tournament_id', $tournament->id)->where('team_id', $team1->id)->first();
    expect($homeStanding->points)->toBe(3);
    expect($homeStanding->goals_for)->toBe(2);

    // Create second game: team2 wins 3-0
    $game2 = Game::factory()->create([
        'tournament_id' => $tournament->id,
        'home_team_id' => $team2->id,
        'away_team_id' => $team1->id,
        'home_score' => 3,
        'away_score' => 0,
        'is_played' => true,
        'week' => 2,
    ]);

    $standingService->updateStandings($game2);

    // Team1 should have 1 win, 1 loss
    $homeStanding->refresh();
    expect($homeStanding->played)->toBe(2);
    expect($homeStanding->won)->toBe(1);
    expect($homeStanding->lost)->toBe(1);
    expect($homeStanding->points)->toBe(3);
    expect($homeStanding->goals_for)->toBe(2);
    expect($homeStanding->goals_against)->toBe(4);

    // Team2 should have 1 win, 1 loss
    $awayStanding = Standing::where('tournament_id', $tournament->id)->where('team_id', $team2->id)->first();
    expect($awayStanding->played)->toBe(2);
    expect($awayStanding->won)->toBe(1);
    expect($awayStanding->lost)->toBe(1);
    expect($awayStanding->points)->toBe(3);
    expect($awayStanding->goals_for)->toBe(4);
    expect($awayStanding->goals_against)->toBe(2);
});

test('standings are not updated when game is not played', function () {
    $tournament = Tournament::factory()->create();
    $team1 = Team::factory()->create();
    $team2 = Team::factory()->create();

    $tournament->teams()->attach([
        $team1->id => ['strength' => 10],
        $team2->id => ['strength' => 20],
    ]);

    $standingService = new StandingService;
    $standingService->createStandings($tournament);

    $game = Game::factory()->create([
        'tournament_id' => $tournament->id,
        'home_team_id' => $team1->id,
        'away_team_id' => $team2->id,
        'home_score' => null,
        'away_score' => null,
        'is_played' => false,
        'week' => 1,
    ]);

    $standingService->updateStandings($game);

    $homeStanding = Standing::where('tournament_id', $tournament->id)->where('team_id', $team1->id)->first();
    expect($homeStanding->played)->toBe(0);
    expect($homeStanding->points)->toBe(0);
});

test('standings are updated via observer when game is updated', function () {
    $tournament = Tournament::factory()->create();
    $team1 = Team::factory()->create();
    $team2 = Team::factory()->create();

    $tournament->teams()->attach([
        $team1->id => ['strength' => 10],
        $team2->id => ['strength' => 20],
    ]);

    $standingService = new StandingService;
    $standingService->createStandings($tournament);

    $game = Game::factory()->create([
        'tournament_id' => $tournament->id,
        'home_team_id' => $team1->id,
        'away_team_id' => $team2->id,
        'home_score' => null,
        'away_score' => null,
        'is_played' => false,
        'week' => 1,
    ]);

    // Update game to played
    $game->update([
        'home_score' => 3,
        'away_score' => 1,
        'is_played' => true,
    ]);

    $homeStanding = Standing::where('tournament_id', $tournament->id)->where('team_id', $team1->id)->first();
    expect($homeStanding->played)->toBe(1);
    expect($homeStanding->won)->toBe(1);
    expect($homeStanding->points)->toBe(3);
});

test('standings handle multiple games correctly', function () {
    $tournament = Tournament::factory()->create();
    $team1 = Team::factory()->create();
    $team2 = Team::factory()->create();
    $team3 = Team::factory()->create();

    $tournament->teams()->attach([
        $team1->id => ['strength' => 10],
        $team2->id => ['strength' => 20],
        $team3->id => ['strength' => 15],
    ]);

    $standingService = new StandingService;
    $standingService->createStandings($tournament);

    // Team1 vs Team2: Team1 wins 2-1
    $game1 = Game::factory()->create([
        'tournament_id' => $tournament->id,
        'home_team_id' => $team1->id,
        'away_team_id' => $team2->id,
        'home_score' => 2,
        'away_score' => 1,
        'is_played' => true,
        'week' => 1,
    ]);

    // Team1 vs Team3: Draw 1-1
    $game2 = Game::factory()->create([
        'tournament_id' => $tournament->id,
        'home_team_id' => $team1->id,
        'away_team_id' => $team3->id,
        'home_score' => 1,
        'away_score' => 1,
        'is_played' => true,
        'week' => 2,
    ]);

    // Update game2 to trigger standings update for team1
    $standingService->updateStandings($game2);

    $team1Standing = Standing::where('tournament_id', $tournament->id)->where('team_id', $team1->id)->first();
    expect($team1Standing->played)->toBe(2);
    expect($team1Standing->won)->toBe(1);
    expect($team1Standing->drawn)->toBe(1);
    expect($team1Standing->lost)->toBe(0);
    expect($team1Standing->points)->toBe(4);
    expect($team1Standing->goals_for)->toBe(3);
    expect($team1Standing->goals_against)->toBe(2);
});
