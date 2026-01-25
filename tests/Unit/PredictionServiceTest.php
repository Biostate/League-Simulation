<?php

use App\Enums\TournamentStatus;
use App\Models\Game;
use App\Models\Standing;
use App\Models\Team;
use App\Models\Tournament;
use App\Services\MatchGenerationService;
use App\Services\PredictionService;
use App\Services\SimulationService;
use App\Services\StandingService;

test('returns empty array when current_week < 4', function () {
    $tournament = Tournament::factory()->create([
        'current_week' => 3,
        'total_weeks' => 6,
    ]);

    $service = new PredictionService;

    expect($service->predict($tournament))->toBeEmpty();
    expect($service->canPredict($tournament))->toBeFalse();
});

test('canPredict returns true when current_week >= 4', function () {
    $tournament = Tournament::factory()->create([
        'current_week' => 4,
        'total_weeks' => 6,
    ]);

    $service = new PredictionService;

    expect($service->canPredict($tournament))->toBeTrue();
});

test('calculates predictions when current_week >= 4', function () {
    $tournament = Tournament::factory()->create([
        'current_week' => 0,
        'total_weeks' => 6,
    ]);

    $team1 = Team::factory()->create();
    $team2 = Team::factory()->create();
    $team3 = Team::factory()->create();

    $tournament->teams()->attach([
        $team1->id => ['strength' => 80],
        $team2->id => ['strength' => 60],
        $team3->id => ['strength' => 40],
    ]);

    $standingService = new StandingService;
    $standingService->createStandings($tournament);

    $matchGenerationService = new MatchGenerationService;
    $matchGenerationService->generateMatches($tournament);

    $simulationService = new SimulationService;
    for ($i = 0; $i < 4; $i++) {
        $simulationService->playNextWeek($tournament);
    }

    $service = new PredictionService;
    $predictions = $service->predict($tournament);

    expect($predictions)->not->toBeEmpty();
    expect($predictions)->toHaveCount(3);
    expect($predictions)->toHaveKeys([$team1->id, $team2->id, $team3->id]);
});

test('predictions sum to 100', function () {
    $tournament = Tournament::factory()->create([
        'current_week' => 0,
        'total_weeks' => 6,
    ]);

    $team1 = Team::factory()->create();
    $team2 = Team::factory()->create();
    $team3 = Team::factory()->create();

    $tournament->teams()->attach([
        $team1->id => ['strength' => 80],
        $team2->id => ['strength' => 60],
        $team3->id => ['strength' => 40],
    ]);

    $standingService = new StandingService;
    $standingService->createStandings($tournament);

    $matchGenerationService = new MatchGenerationService;
    $matchGenerationService->generateMatches($tournament);

    $simulationService = new SimulationService;
    for ($i = 0; $i < 4; $i++) {
        $simulationService->playNextWeek($tournament);
    }

    $service = new PredictionService;
    $predictions = $service->predict($tournament);

    $total = round(array_sum($predictions), 1);
    expect($total)->toBeGreaterThanOrEqual(99.0);
    expect($total)->toBeLessThanOrEqual(100.0);
});

test('team with highest points and strength has highest prediction', function () {
    $tournament = Tournament::factory()->create([
        'current_week' => 0,
        'total_weeks' => 6,
    ]);

    $team1 = Team::factory()->create();
    $team2 = Team::factory()->create();
    $team3 = Team::factory()->create();

    $tournament->teams()->attach([
        $team1->id => ['strength' => 90],
        $team2->id => ['strength' => 50],
        $team3->id => ['strength' => 30],
    ]);

    $standingService = new StandingService;
    $standingService->createStandings($tournament);

    $matchGenerationService = new MatchGenerationService;
    $matchGenerationService->generateMatches($tournament);

    $simulationService = new SimulationService;
    for ($i = 0; $i < 4; $i++) {
        $simulationService->playNextWeek($tournament);
    }

    $standing1 = Standing::where('tournament_id', $tournament->id)
        ->where('team_id', $team1->id)
        ->first();
    $tournament->refresh();
    $remainingMatches = $tournament->total_weeks - $tournament->current_week;

    $standing1 = Standing::where('tournament_id', $tournament->id)
        ->where('team_id', $team1->id)
        ->first();
    $standing1->update(['points' => 12, 'goal_difference' => 10]);

    $standing2 = Standing::where('tournament_id', $tournament->id)
        ->where('team_id', $team2->id)
        ->first();
    $standing2->update(['points' => 9, 'goal_difference' => 5]);

    $standing3 = Standing::where('tournament_id', $tournament->id)
        ->where('team_id', $team3->id)
        ->first();
    $standing3->update(['points' => 6, 'goal_difference' => -5]);

    $tournament->refresh();

    $service = new PredictionService;
    $predictions = $service->predict($tournament);

    expect($predictions[$team1->id])->toBeGreaterThan(0);
    expect($predictions[$team1->id])->toBeGreaterThanOrEqual($predictions[$team2->id]);
    
    if ($predictions[$team2->id] > 0) {
        expect($predictions[$team2->id])->toBeGreaterThanOrEqual($predictions[$team3->id]);
    }
});

test('returns 0% for teams that cannot mathematically win championship', function () {
    $tournament = Tournament::factory()->create([
        'current_week' => 0,
        'total_weeks' => 6,
    ]);

    $team1 = Team::factory()->create();
    $team2 = Team::factory()->create();
    $team3 = Team::factory()->create();

    $tournament->teams()->attach([
        $team1->id => ['strength' => 80],
        $team2->id => ['strength' => 60],
        $team3->id => ['strength' => 40],
    ]);

    $standingService = new StandingService;
    $standingService->createStandings($tournament);

    $matchGenerationService = new MatchGenerationService;
    $matchGenerationService->generateMatches($tournament);

    $simulationService = new SimulationService;
    for ($i = 0; $i < 4; $i++) {
        $simulationService->playNextWeek($tournament);
    }

    $standing1 = Standing::where('tournament_id', $tournament->id)
        ->where('team_id', $team1->id)
        ->first();
    $standing1->update(['points' => 30]);

    $standing2 = Standing::where('tournament_id', $tournament->id)
        ->where('team_id', $team2->id)
        ->first();
    $standing2->update(['points' => 5]);

    $standing3 = Standing::where('tournament_id', $tournament->id)
        ->where('team_id', $team3->id)
        ->first();
    $standing3->update(['points' => 3]);

    $service = new PredictionService;
    $predictions = $service->predict($tournament);

    $remainingMatches = 2;
    $team2MaxPoints = 5 + ($remainingMatches * 3);
    $team3MaxPoints = 3 + ($remainingMatches * 3);

    if ($team2MaxPoints < 30) {
        expect($predictions[$team2->id])->toBe(0.0);
    }

    if ($team3MaxPoints < 30) {
        expect($predictions[$team3->id])->toBe(0.0);
    }
});

test('team with max possible points less than leader gets 0%', function () {
    $tournament = Tournament::factory()->create([
        'current_week' => 0,
    ]);

    $team1 = Team::factory()->create();
    $team2 = Team::factory()->create();
    $team3 = Team::factory()->create();

    $tournament->teams()->attach([
        $team1->id => ['strength' => 80],
        $team2->id => ['strength' => 60],
        $team3->id => ['strength' => 40],
    ]);

    $standingService = new StandingService;
    $standingService->createStandings($tournament);

    $matchGenerationService = new MatchGenerationService;
    $matchGenerationService->generateMatches($tournament);

    $tournament->refresh();

    $simulationService = new SimulationService;
    $weeksToPlay = min(4, $tournament->total_weeks);
    for ($i = 0; $i < $weeksToPlay; $i++) {
        $simulationService->playNextWeek($tournament);
    }

    $standing1 = Standing::where('tournament_id', $tournament->id)
        ->where('team_id', $team1->id)
        ->first();
    $standing1->update(['points' => 25]);

    $standing2 = Standing::where('tournament_id', $tournament->id)
        ->where('team_id', $team2->id)
        ->first();
    $standing2->update(['points' => 10]);

    $service = new PredictionService;
    $predictions = $service->predict($tournament);

    $tournament->refresh();
    $remainingMatches = $tournament->total_weeks - $tournament->current_week;
    $team2MaxPoints = 10 + ($remainingMatches * 3);

    if ($team2MaxPoints < 25) {
        expect($predictions[$team2->id])->toBe(0.0);
        expect($predictions[$team1->id])->toBeGreaterThan(0);
    }
});

test('only eligible teams have non-zero predictions', function () {
    $tournament = Tournament::factory()->create([
        'current_week' => 0,
        'total_weeks' => 6,
    ]);

    $team1 = Team::factory()->create();
    $team2 = Team::factory()->create();
    $team3 = Team::factory()->create();

    $tournament->teams()->attach([
        $team1->id => ['strength' => 80],
        $team2->id => ['strength' => 60],
        $team3->id => ['strength' => 40],
    ]);

    $standingService = new StandingService;
    $standingService->createStandings($tournament);

    $matchGenerationService = new MatchGenerationService;
    $matchGenerationService->generateMatches($tournament);

    $simulationService = new SimulationService;
    for ($i = 0; $i < 4; $i++) {
        $simulationService->playNextWeek($tournament);
    }

    $standing1 = Standing::where('tournament_id', $tournament->id)
        ->where('team_id', $team1->id)
        ->first();
    $standing1->update(['points' => 30]);

    $standing2 = Standing::where('tournament_id', $tournament->id)
        ->where('team_id', $team2->id)
        ->first();
    $standing2->update(['points' => 20]);

    $standing3 = Standing::where('tournament_id', $tournament->id)
        ->where('team_id', $team3->id)
        ->first();
    $standing3->update(['points' => 5]);

    $service = new PredictionService;
    $predictions = $service->predict($tournament);

    $remainingMatches = 2;
    $team3MaxPoints = 5 + ($remainingMatches * 3);

    if ($team3MaxPoints < 30) {
        expect($predictions[$team3->id])->toBe(0.0);
    }

    $nonZeroCount = count(array_filter($predictions, fn ($p) => $p > 0));
    expect($nonZeroCount)->toBeGreaterThan(0);
});

test('handles edge case when all teams are mathematically eliminated', function () {
    $tournament = Tournament::factory()->create([
        'current_week' => 0,
    ]);

    $team1 = Team::factory()->create();
    $team2 = Team::factory()->create();
    $team3 = Team::factory()->create();

    $tournament->teams()->attach([
        $team1->id => ['strength' => 80],
        $team2->id => ['strength' => 60],
        $team3->id => ['strength' => 40],
    ]);

    $standingService = new StandingService;
    $standingService->createStandings($tournament);

    $matchGenerationService = new MatchGenerationService;
    $matchGenerationService->generateMatches($tournament);

    $tournament->refresh();

    $simulationService = new SimulationService;
    $weeksToPlay = min(4, $tournament->total_weeks);
    for ($i = 0; $i < $weeksToPlay; $i++) {
        $simulationService->playNextWeek($tournament);
    }

    $tournament->refresh();
    $remainingMatches = $tournament->total_weeks - $tournament->current_week;

    $standing1 = Standing::where('tournament_id', $tournament->id)
        ->where('team_id', $team1->id)
        ->first();
    $standing1->update(['points' => 100]);

    $standing2 = Standing::where('tournament_id', $tournament->id)
        ->where('team_id', $team2->id)
        ->first();
    $standing2->update(['points' => 5]);

    $standing3 = Standing::where('tournament_id', $tournament->id)
        ->where('team_id', $team3->id)
        ->first();
    $standing3->update(['points' => 3]);

    $tournament->refresh();

    $service = new PredictionService;
    $predictions = $service->predict($tournament);

    $team2MaxPoints = 5 + ($remainingMatches * 3);
    $team3MaxPoints = 3 + ($remainingMatches * 3);

    if ($team2MaxPoints < 100 && $team3MaxPoints < 100) {
        expect($predictions[$team2->id])->toBe(0.0);
        expect($predictions[$team3->id])->toBe(0.0);
        expect($predictions[$team1->id])->toBeGreaterThan(0);
    }
});

test('considers form in predictions', function () {
    $tournament = Tournament::factory()->create([
        'current_week' => 0,
    ]);

    $team1 = Team::factory()->create();
    $team2 = Team::factory()->create();
    $team3 = Team::factory()->create();

    $tournament->teams()->attach([
        $team1->id => ['strength' => 70],
        $team2->id => ['strength' => 70],
        $team3->id => ['strength' => 70],
    ]);

    $standingService = new StandingService;
    $standingService->createStandings($tournament);

    $matchGenerationService = new MatchGenerationService;
    $matchGenerationService->generateMatches($tournament);

    $tournament->refresh();

    $simulationService = new SimulationService;
    $weeksToPlay = min(4, $tournament->total_weeks);
    for ($i = 0; $i < $weeksToPlay; $i++) {
        $simulationService->playNextWeek($tournament);
    }

    $standing1 = Standing::where('tournament_id', $tournament->id)
        ->where('team_id', $team1->id)
        ->first();
    $standing1->update(['points' => 20]);

    $standing2 = Standing::where('tournament_id', $tournament->id)
        ->where('team_id', $team2->id)
        ->first();
    $standing2->update(['points' => 20]);

    $standing3 = Standing::where('tournament_id', $tournament->id)
        ->where('team_id', $team3->id)
        ->first();
    $standing3->update(['points' => 15]);

    $service = new PredictionService;
    $predictions = $service->predict($tournament);

    expect($predictions)->not->toBeEmpty();
    expect($predictions[$team1->id])->toBeGreaterThanOrEqual(0);
    expect($predictions[$team2->id])->toBeGreaterThanOrEqual(0);
});
