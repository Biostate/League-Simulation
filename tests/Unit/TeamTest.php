<?php

use App\Models\Team;
use App\Models\Tournament;

test('team has tournaments relationship', function () {
    $team = Team::factory()->create();
    $tournament = Tournament::factory()->create();

    $team->tournaments()->attach($tournament->id, ['strength' => 10]);

    expect($team->tournaments)->toHaveCount(1);
    expect($team->tournaments->first()->id)->toBe($tournament->id);
    expect($team->tournaments->first()->pivot->strength)->toBe(10);
});

test('isUsedInTournaments returns true when team has tournaments', function () {
    $team = Team::factory()->create();
    $tournament = Tournament::factory()->create();

    $team->tournaments()->attach($tournament->id, ['strength' => 10]);

    expect($team->isUsedInTournaments())->toBeTrue();
});

test('isUsedInTournaments returns false when team has no tournaments', function () {
    $team = Team::factory()->create();

    expect($team->isUsedInTournaments())->toBeFalse();
});
