<?php

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
