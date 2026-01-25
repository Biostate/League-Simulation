<?php

use App\Enums\TournamentStatus;
use App\Models\Game;
use App\Models\Standing;
use App\Models\Team;
use App\Models\Tournament;
use App\Services\MatchGenerationService;
use App\Services\SimulationService;
use App\Services\StandingService;

test('playNextWeek throws exception if all weeks have been played', function () {
    $tournament = Tournament::factory()->create([
        'status' => TournamentStatus::SIMULATING,
        'current_week' => 2,
        'total_weeks' => 2,
    ]);

    $service = new SimulationService;

    expect(fn () => $service->playNextWeek($tournament))
        ->toThrow(\RuntimeException::class, 'All weeks have been played.');
});

test('playNextWeek throws exception if no matches found for next week', function () {
    $tournament = Tournament::factory()->create([
        'status' => TournamentStatus::SIMULATING,
        'current_week' => 0,
        'total_weeks' => 2,
    ]);

    $service = new SimulationService;

    expect(fn () => $service->playNextWeek($tournament))
        ->toThrow(\RuntimeException::class, 'No matches found for week 1.');
});

test('playNextWeek simulates matches for next week', function () {
    $tournament = Tournament::factory()->create([
        'status' => TournamentStatus::SIMULATING,
        'current_week' => 0,
        'total_weeks' => 2,
    ]);

    $team1 = Team::factory()->create();
    $team2 = Team::factory()->create();

    $tournament->teams()->attach([
        $team1->id => ['strength' => 50],
        $team2->id => ['strength' => 60],
    ]);

    $standingService = new StandingService;
    $standingService->createStandings($tournament);

    $matchGenerationService = new MatchGenerationService;
    $matchGenerationService->generateMatches($tournament);

    $service = new SimulationService;
    $service->playNextWeek($tournament);

    $tournament->refresh();

    expect($tournament->current_week)->toBe(1);
    expect($tournament->status)->toBe(TournamentStatus::IN_PROGRESS);

    $week1Matches = Game::where('tournament_id', $tournament->id)
        ->where('week', 1)
        ->get();

    foreach ($week1Matches as $match) {
        expect($match->is_played)->toBeTrue();
        expect($match->home_score)->not->toBeNull();
        expect($match->away_score)->not->toBeNull();
        expect($match->home_score)->toBeGreaterThanOrEqual(0);
        expect($match->away_score)->toBeGreaterThanOrEqual(0);
    }
});

test('playNextWeek updates standings after simulating matches', function () {
    $tournament = Tournament::factory()->create([
        'status' => TournamentStatus::SIMULATING,
        'current_week' => 0,
        'total_weeks' => 2,
    ]);

    $team1 = Team::factory()->create();
    $team2 = Team::factory()->create();

    $tournament->teams()->attach([
        $team1->id => ['strength' => 50],
        $team2->id => ['strength' => 60],
    ]);

    $standingService = new StandingService;
    $standingService->createStandings($tournament);

    $matchGenerationService = new MatchGenerationService;
    $matchGenerationService->generateMatches($tournament);

    $service = new SimulationService;
    $service->playNextWeek($tournament);

    $standing1 = Standing::where('tournament_id', $tournament->id)
        ->where('team_id', $team1->id)
        ->first();

    $standing2 = Standing::where('tournament_id', $tournament->id)
        ->where('team_id', $team2->id)
        ->first();

    expect($standing1->played)->toBeGreaterThan(0);
    expect($standing2->played)->toBeGreaterThan(0);
    expect($standing1->goals_for)->toBeGreaterThanOrEqual(0);
    expect($standing2->goals_for)->toBeGreaterThanOrEqual(0);
});

test('playNextWeek sets tournament to completed when last week is played', function () {
    $tournament = Tournament::factory()->create([
        'status' => TournamentStatus::SIMULATING,
        'current_week' => 1,
        'total_weeks' => 2,
    ]);

    $team1 = Team::factory()->create();
    $team2 = Team::factory()->create();

    $tournament->teams()->attach([
        $team1->id => ['strength' => 50],
        $team2->id => ['strength' => 60],
    ]);

    $standingService = new StandingService;
    $standingService->createStandings($tournament);

    $matchGenerationService = new MatchGenerationService;
    $matchGenerationService->generateMatches($tournament);

    $service = new SimulationService;
    $service->playNextWeek($tournament);

    $tournament->refresh();

    expect($tournament->current_week)->toBe(2);
    expect($tournament->status)->toBe(TournamentStatus::COMPLETED);
});

test('playAllWeeks plays all remaining weeks', function () {
    $tournament = Tournament::factory()->create([
        'status' => TournamentStatus::SIMULATING,
        'current_week' => 0,
        'total_weeks' => 2,
    ]);

    $team1 = Team::factory()->create();
    $team2 = Team::factory()->create();

    $tournament->teams()->attach([
        $team1->id => ['strength' => 50],
        $team2->id => ['strength' => 60],
    ]);

    $standingService = new StandingService;
    $standingService->createStandings($tournament);

    $matchGenerationService = new MatchGenerationService;
    $matchGenerationService->generateMatches($tournament);

    $service = new SimulationService;
    $service->playAllWeeks($tournament);

    $tournament->refresh();

    expect($tournament->current_week)->toBe(2);
    expect($tournament->status)->toBe(TournamentStatus::COMPLETED);

    $allMatches = Game::where('tournament_id', $tournament->id)->get();
    foreach ($allMatches as $match) {
        expect($match->is_played)->toBeTrue();
        expect($match->home_score)->not->toBeNull();
        expect($match->away_score)->not->toBeNull();
    }
});

test('simulateMatch generates valid scores', function () {
    $tournament = Tournament::factory()->create([
        'status' => TournamentStatus::SIMULATING,
        'current_week' => 0,
        'total_weeks' => 2,
    ]);

    $team1 = Team::factory()->create();
    $team2 = Team::factory()->create();

    $tournament->teams()->attach([
        $team1->id => ['strength' => 50],
        $team2->id => ['strength' => 60],
    ]);

    $match = Game::factory()->create([
        'tournament_id' => $tournament->id,
        'home_team_id' => $team1->id,
        'away_team_id' => $team2->id,
        'week' => 1,
        'is_played' => false,
    ]);

    $service = new SimulationService;
    $reflection = new \ReflectionClass($service);
    $method = $reflection->getMethod('simulateMatch');
    $method->setAccessible(true);
    $method->invoke($service, $match, $tournament);

    $match->refresh();

    expect($match->is_played)->toBeTrue();
    expect($match->home_score)->toBeGreaterThanOrEqual(0);
    expect($match->away_score)->toBeGreaterThanOrEqual(0);
    expect($match->home_score)->toBeInt();
    expect($match->away_score)->toBeInt();
});
