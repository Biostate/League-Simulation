<?php

use App\Models\Standing;
use App\Models\Team;
use App\Models\Tournament;
use App\Models\User;

test('guests cannot access tournaments index', function () {
    $response = $this->get(route('tournaments.index'));

    $response->assertRedirect(route('login'));
});

test('authenticated users can view their tournaments', function () {
    $user = User::factory()->create();
    Tournament::factory()->count(3)->create(['user_id' => $user->id]);
    Tournament::factory()->count(2)->create();

    $response = $this->actingAs($user)->get(route('tournaments.index'));

    $response->assertOk();
});

test('authenticated users can view create tournament form', function () {
    $user = User::factory()->create();
    Team::factory()->count(3)->create();

    $response = $this->actingAs($user)->get(route('tournaments.create'));

    $response->assertOk();
});

test('authenticated users can create a tournament', function () {
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

    $response->assertRedirect(route('tournaments.index'));
    $this->assertDatabaseHas('tournaments', [
        'name' => 'Test Tournament',
        'status' => 'created',
        'user_id' => $user->id,
    ]);

    $tournament = Tournament::where('name', 'Test Tournament')->first();
    expect($tournament->teams)->toHaveCount(2);
    expect($tournament->teams->find($team1->id)->pivot->strength)->toBe(10);
    expect($tournament->teams->find($team2->id)->pivot->strength)->toBe(20);

    // Verify standings are created for all teams
    expect(Standing::where('tournament_id', $tournament->id)->count())->toBe(2);
    expect(Standing::where('tournament_id', $tournament->id)->where('team_id', $team1->id)->exists())->toBeTrue();
    expect(Standing::where('tournament_id', $tournament->id)->where('team_id', $team2->id)->exists())->toBeTrue();

    // Verify standings are initialized with zero values
    $standing = Standing::where('tournament_id', $tournament->id)->where('team_id', $team1->id)->first();
    expect($standing->played)->toBe(0);
    expect($standing->won)->toBe(0);
    expect($standing->drawn)->toBe(0);
    expect($standing->lost)->toBe(0);
    expect($standing->goals_for)->toBe(0);
    expect($standing->goals_against)->toBe(0);
    expect($standing->goal_difference)->toBe(0);
    expect($standing->points)->toBe(0);
});

test('tournament is automatically attached to current user', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('tournaments.store'), [
        'name' => 'Test Tournament',
        'teams' => [],
    ]);

    $this->assertDatabaseHas('tournaments', [
        'name' => 'Test Tournament',
        'user_id' => $user->id,
    ]);
});

test('users can only edit their own tournaments', function () {
    $owner = User::factory()->create();
    $otherUser = User::factory()->create();
    $tournament = Tournament::factory()->create(['user_id' => $owner->id]);

    $response = $this->actingAs($otherUser)->get(route('tournaments.edit', $tournament));

    $response->assertForbidden();
});

test('users can update their own tournaments', function () {
    $user = User::factory()->create();
    $tournament = Tournament::factory()->create(['user_id' => $user->id, 'name' => 'Old Name', 'status' => 'created']);
    $team = Team::factory()->create();

    $response = $this->actingAs($user)->put(route('tournaments.update', $tournament), [
        'name' => 'New Name',
        'teams' => [
            ['id' => $team->id, 'strength' => 15],
        ],
    ]);

    $response->assertRedirect(route('tournaments.index'));
    $this->assertDatabaseHas('tournaments', [
        'id' => $tournament->id,
        'name' => 'New Name',
        'status' => 'created',
    ]);

    $tournament->refresh();
    expect($tournament->teams)->toHaveCount(1);
    expect($tournament->teams->first()->pivot->strength)->toBe(15);

    // Verify standings are recreated when teams change
    expect(Standing::where('tournament_id', $tournament->id)->count())->toBe(1);
    expect(Standing::where('tournament_id', $tournament->id)->where('team_id', $team->id)->exists())->toBeTrue();
});

test('standings are recreated correctly when tournament teams change', function () {
    $user = User::factory()->create();
    $team1 = Team::factory()->create();
    $team2 = Team::factory()->create();
    $team3 = Team::factory()->create();

    $tournament = Tournament::factory()->create(['user_id' => $user->id, 'status' => 'created']);

    // Create tournament with team1 and team2
    $response = $this->actingAs($user)->post(route('tournaments.store'), [
        'name' => 'Test Tournament',
        'teams' => [
            ['id' => $team1->id, 'strength' => 10],
            ['id' => $team2->id, 'strength' => 20],
        ],
    ]);

    $tournament = Tournament::where('name', 'Test Tournament')->first();
    expect(Standing::where('tournament_id', $tournament->id)->count())->toBe(2);
    expect(Standing::where('tournament_id', $tournament->id)->where('team_id', $team1->id)->exists())->toBeTrue();
    expect(Standing::where('tournament_id', $tournament->id)->where('team_id', $team2->id)->exists())->toBeTrue();

    // Update tournament to replace team1 with team3
    $response = $this->actingAs($user)->put(route('tournaments.update', $tournament), [
        'name' => 'Test Tournament',
        'teams' => [
            ['id' => $team2->id, 'strength' => 20],
            ['id' => $team3->id, 'strength' => 15],
        ],
    ]);

    $tournament->refresh();
    expect(Standing::where('tournament_id', $tournament->id)->count())->toBe(2);
    expect(Standing::where('tournament_id', $tournament->id)->where('team_id', $team1->id)->exists())->toBeFalse();
    expect(Standing::where('tournament_id', $tournament->id)->where('team_id', $team2->id)->exists())->toBeTrue();
    expect(Standing::where('tournament_id', $tournament->id)->where('team_id', $team3->id)->exists())->toBeTrue();
});

test('users cannot update tournaments that are not in created status', function () {
    $user = User::factory()->create();
    $tournament = Tournament::factory()->create(['user_id' => $user->id, 'status' => 'in_progress']);

    $response = $this->actingAs($user)->put(route('tournaments.update', $tournament), [
        'name' => 'New Name',
        'teams' => [],
    ]);

    $response->assertRedirect(route('tournaments.index'));
    $response->assertSessionHas('error');
});

test('users can only delete their own tournaments', function () {
    $owner = User::factory()->create();
    $otherUser = User::factory()->create();
    $tournament = Tournament::factory()->create(['user_id' => $owner->id]);

    $response = $this->actingAs($otherUser)->delete(route('tournaments.destroy', $tournament));

    $response->assertForbidden();
});

test('users can delete their own tournaments', function () {
    $user = User::factory()->create();
    $tournament = Tournament::factory()->create(['user_id' => $user->id]);

    $response = $this->actingAs($user)->delete(route('tournaments.destroy', $tournament));

    $response->assertRedirect(route('tournaments.index'));
    $this->assertDatabaseMissing('tournaments', [
        'id' => $tournament->id,
    ]);
});
