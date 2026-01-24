<?php

use App\Models\Team;
use App\Models\Tournament;
use App\Models\User;

test('tournament belongs to user', function () {
    $user = User::factory()->create();
    $tournament = Tournament::factory()->create(['user_id' => $user->id]);

    expect($tournament->user->id)->toBe($user->id);
});

test('tournament has teams relationship', function () {
    $tournament = Tournament::factory()->create();
    $team1 = Team::factory()->create();
    $team2 = Team::factory()->create();

    $tournament->teams()->attach($team1->id, ['strength' => 10]);
    $tournament->teams()->attach($team2->id, ['strength' => 20]);

    expect($tournament->teams)->toHaveCount(2);
    expect($tournament->teams->pluck('id')->toArray())->toContain($team1->id, $team2->id);
});

test('tournament can sync teams with strength', function () {
    $tournament = Tournament::factory()->create();
    $team1 = Team::factory()->create();
    $team2 = Team::factory()->create();

    $tournament->teams()->sync([
        $team1->id => ['strength' => 15],
        $team2->id => ['strength' => 25],
    ]);

    expect($tournament->teams)->toHaveCount(2);
    expect($tournament->teams->find($team1->id)->pivot->strength)->toBe(15);
    expect($tournament->teams->find($team2->id)->pivot->strength)->toBe(25);
});
