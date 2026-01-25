<?php

use App\Models\Game;
use App\Models\Team;
use App\Models\Tournament;

test('match belongs to tournament', function () {
    $tournament = Tournament::factory()->create();
    $team1 = Team::factory()->create();
    $team2 = Team::factory()->create();

    $match = Game::factory()->create([
        'tournament_id' => $tournament->id,
        'home_team_id' => $team1->id,
        'away_team_id' => $team2->id,
        'week' => 1,
    ]);

    expect($match->tournament->id)->toBe($tournament->id);
});

test('match belongs to home team', function () {
    $tournament = Tournament::factory()->create();
    $team1 = Team::factory()->create();
    $team2 = Team::factory()->create();

    $match = Game::factory()->create([
        'tournament_id' => $tournament->id,
        'home_team_id' => $team1->id,
        'away_team_id' => $team2->id,
        'week' => 1,
    ]);

    expect($match->homeTeam->id)->toBe($team1->id);
});

test('match belongs to away team', function () {
    $tournament = Tournament::factory()->create();
    $team1 = Team::factory()->create();
    $team2 = Team::factory()->create();

    $match = Game::factory()->create([
        'tournament_id' => $tournament->id,
        'home_team_id' => $team1->id,
        'away_team_id' => $team2->id,
        'week' => 1,
    ]);

    expect($match->awayTeam->id)->toBe($team2->id);
});

test('match has correct fillable fields', function () {
    $tournament = Tournament::factory()->create();
    $team1 = Team::factory()->create();
    $team2 = Team::factory()->create();

    $match = Game::create([
        'tournament_id' => $tournament->id,
        'home_team_id' => $team1->id,
        'away_team_id' => $team2->id,
        'home_score' => 2,
        'away_score' => 1,
        'week' => 1,
        'is_played' => true,
    ]);

    expect($match->tournament_id)->toBe($tournament->id);
    expect($match->home_team_id)->toBe($team1->id);
    expect($match->away_team_id)->toBe($team2->id);
    expect($match->home_score)->toBe(2);
    expect($match->away_score)->toBe(1);
    expect($match->week)->toBe(1);
    expect($match->is_played)->toBeTrue();
});

test('match casts is_played as boolean', function () {
    $tournament = Tournament::factory()->create();
    $team1 = Team::factory()->create();
    $team2 = Team::factory()->create();

    $match = Game::create([
        'tournament_id' => $tournament->id,
        'home_team_id' => $team1->id,
        'away_team_id' => $team2->id,
        'is_played' => 1,
        'week' => 1,
    ]);

    expect($match->is_played)->toBeBool();
    expect($match->is_played)->toBeTrue();
});

test('match casts scores as integers', function () {
    $tournament = Tournament::factory()->create();
    $team1 = Team::factory()->create();
    $team2 = Team::factory()->create();

    $match = Game::create([
        'tournament_id' => $tournament->id,
        'home_team_id' => $team1->id,
        'away_team_id' => $team2->id,
        'home_score' => '3',
        'away_score' => '1',
        'week' => 1,
    ]);

    expect($match->home_score)->toBeInt();
    expect($match->away_score)->toBeInt();
    expect($match->home_score)->toBe(3);
    expect($match->away_score)->toBe(1);
});

test('match casts week as integer', function () {
    $tournament = Tournament::factory()->create();
    $team1 = Team::factory()->create();
    $team2 = Team::factory()->create();

    $match = Game::create([
        'tournament_id' => $tournament->id,
        'home_team_id' => $team1->id,
        'away_team_id' => $team2->id,
        'week' => '5',
    ]);

    expect($match->week)->toBeInt();
    expect($match->week)->toBe(5);
});
