<?php

use App\Enums\TournamentStatus;
use App\Models\Game;
use App\Models\Team;
use App\Models\Tournament;
use App\Models\User;
use App\Services\MatchGenerationService;
use App\Services\StandingService;

test('guests cannot play next week', function () {
    $tournament = Tournament::factory()->create();

    $response = $this->post(route('tournaments.play-next-week', $tournament));

    $response->assertRedirect(route('login'));
});

test('users cannot play next week for tournaments they do not own', function () {
    $owner = User::factory()->create();
    $otherUser = User::factory()->create();
    $tournament = Tournament::factory()->create(['user_id' => $owner->id]);

    $response = $this->actingAs($otherUser)
        ->post(route('tournaments.play-next-week', $tournament));

    $response->assertForbidden();
});

test('users can play next week for their own tournament', function () {
    $user = User::factory()->create();
    $tournament = Tournament::factory()->create([
        'user_id' => $user->id,
        'status' => TournamentStatus::CREATED,
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

    $response = $this->actingAs($user)
        ->post(route('tournaments.play-next-week', $tournament));

    $response->assertRedirect();
    $response->assertSessionHas('success');

    $tournament->refresh();
    expect($tournament->status)->toBe(TournamentStatus::IN_PROGRESS);
    expect($tournament->current_week)->toBe(1);
});

test('play next week sets tournament to simulating if created', function () {
    $user = User::factory()->create();
    $tournament = Tournament::factory()->create([
        'user_id' => $user->id,
        'status' => TournamentStatus::CREATED,
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

    $this->actingAs($user)
        ->post(route('tournaments.play-next-week', $tournament));

    $tournament->refresh();
    expect($tournament->status)->toBe(TournamentStatus::IN_PROGRESS);
});

test('play next week sets tournament to simulating if in progress', function () {
    $user = User::factory()->create();
    $tournament = Tournament::factory()->create([
        'user_id' => $user->id,
        'status' => TournamentStatus::IN_PROGRESS,
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

    $this->actingAs($user)
        ->post(route('tournaments.play-next-week', $tournament));

    $tournament->refresh();
    expect($tournament->status)->toBe(TournamentStatus::IN_PROGRESS);
});

test('play next week returns error if tournament is already simulating', function () {
    $user = User::factory()->create();
    $tournament = Tournament::factory()->create([
        'user_id' => $user->id,
        'status' => TournamentStatus::SIMULATING,
        'current_week' => 0,
        'total_weeks' => 2,
    ]);

    $response = $this->actingAs($user)
        ->post(route('tournaments.play-next-week', $tournament));

    $response->assertRedirect();
    $response->assertSessionHas('error');
    expect(session('error'))->toContain('already simulating');
});

test('play next week returns error if tournament is completed', function () {
    $user = User::factory()->create();
    $tournament = Tournament::factory()->create([
        'user_id' => $user->id,
        'status' => TournamentStatus::COMPLETED,
    ]);

    $response = $this->actingAs($user)
        ->post(route('tournaments.play-next-week', $tournament));

    $response->assertRedirect();
    $response->assertSessionHas('error');
    expect(session('error'))->toContain('already completed');
});

test('play next week completes tournament when last week is played', function () {
    $user = User::factory()->create();
    $tournament = Tournament::factory()->create([
        'user_id' => $user->id,
        'status' => TournamentStatus::CREATED,
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

    $this->actingAs($user)
        ->post(route('tournaments.play-next-week', $tournament));

    $tournament->refresh();
    expect($tournament->status)->toBe(TournamentStatus::COMPLETED);
    expect($tournament->current_week)->toBe(2);
});

test('guests cannot play all weeks', function () {
    $tournament = Tournament::factory()->create();

    $response = $this->post(route('tournaments.play-all-weeks', $tournament));

    $response->assertRedirect(route('login'));
});

test('users cannot play all weeks for tournaments they do not own', function () {
    $owner = User::factory()->create();
    $otherUser = User::factory()->create();
    $tournament = Tournament::factory()->create(['user_id' => $owner->id]);

    $response = $this->actingAs($otherUser)
        ->post(route('tournaments.play-all-weeks', $tournament));

    $response->assertForbidden();
});

test('users can play all weeks for their own tournament', function () {
    $user = User::factory()->create();
    $tournament = Tournament::factory()->create([
        'user_id' => $user->id,
        'status' => TournamentStatus::CREATED,
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

    $response = $this->actingAs($user)
        ->post(route('tournaments.play-all-weeks', $tournament));

    $response->assertRedirect();
    $response->assertSessionHas('success');

    $tournament->refresh();
    expect($tournament->status)->toBe(TournamentStatus::COMPLETED);
    expect($tournament->current_week)->toBe(2);

    $allMatches = Game::where('tournament_id', $tournament->id)->get();
    foreach ($allMatches as $match) {
        expect($match->is_played)->toBeTrue();
    }
});

test('play all weeks sets tournament to simulating if created', function () {
    $user = User::factory()->create();
    $tournament = Tournament::factory()->create([
        'user_id' => $user->id,
        'status' => TournamentStatus::CREATED,
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

    $this->actingAs($user)
        ->post(route('tournaments.play-all-weeks', $tournament));

    $tournament->refresh();
    expect($tournament->status)->toBe(TournamentStatus::COMPLETED);
});

test('play all weeks returns error if tournament is already simulating', function () {
    $user = User::factory()->create();
    $tournament = Tournament::factory()->create([
        'user_id' => $user->id,
        'status' => TournamentStatus::SIMULATING,
    ]);

    $response = $this->actingAs($user)
        ->post(route('tournaments.play-all-weeks', $tournament));

    $response->assertRedirect();
    $response->assertSessionHas('error');
    expect(session('error'))->toContain('already simulating');
});

test('play all weeks returns error if tournament is completed', function () {
    $user = User::factory()->create();
    $tournament = Tournament::factory()->create([
        'user_id' => $user->id,
        'status' => TournamentStatus::COMPLETED,
    ]);

    $response = $this->actingAs($user)
        ->post(route('tournaments.play-all-weeks', $tournament));

    $response->assertRedirect();
    $response->assertSessionHas('error');
    expect(session('error'))->toContain('already completed');
});

test('play all weeks plays all remaining weeks', function () {
    $user = User::factory()->create();
    $tournament = Tournament::factory()->create([
        'user_id' => $user->id,
        'status' => TournamentStatus::CREATED,
        'current_week' => 0,
        'total_weeks' => 4,
    ]);

    $team1 = Team::factory()->create();
    $team2 = Team::factory()->create();
    $team3 = Team::factory()->create();

    $tournament->teams()->attach([
        $team1->id => ['strength' => 50],
        $team2->id => ['strength' => 60],
        $team3->id => ['strength' => 70],
    ]);

    $standingService = new StandingService;
    $standingService->createStandings($tournament);

    $matchGenerationService = new MatchGenerationService;
    $matchGenerationService->generateMatches($tournament);

    $this->actingAs($user)
        ->post(route('tournaments.play-all-weeks', $tournament));

    $tournament->refresh();
    expect($tournament->status)->toBe(TournamentStatus::COMPLETED);
    expect($tournament->current_week)->toBe($tournament->total_weeks);

    $allMatches = Game::where('tournament_id', $tournament->id)->get();
    expect($allMatches->count())->toBeGreaterThan(0);
    foreach ($allMatches as $match) {
        expect($match->is_played)->toBeTrue();
        expect($match->home_score)->not->toBeNull();
        expect($match->away_score)->not->toBeNull();
    }
});
