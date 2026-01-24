<?php

use App\Models\Team;
use App\Models\Tournament;
use App\Models\User;

test('guests cannot access teams index', function () {
    $response = $this->get(route('teams.index'));

    $response->assertRedirect(route('login'));
});

test('authenticated users can view teams index', function () {
    $user = User::factory()->create();
    Team::factory()->count(3)->create();

    $response = $this->actingAs($user)->get(route('teams.index'));

    $response->assertOk();
});

test('authenticated users can view create team form', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('teams.create'));

    $response->assertOk();
});

test('authenticated users can create a team', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('teams.store'), [
        'name' => 'Test Team',
    ]);

    $response->assertRedirect(route('teams.index'));
    $this->assertDatabaseHas('teams', [
        'name' => 'Test Team',
    ]);
});

test('team name must be unique', function () {
    $user = User::factory()->create();
    Team::factory()->create(['name' => 'Existing Team']);

    $response = $this->actingAs($user)->post(route('teams.store'), [
        'name' => 'Existing Team',
    ]);

    $response->assertSessionHasErrors('name');
});

test('authenticated users can view edit team form', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();

    $response = $this->actingAs($user)->get(route('teams.edit', $team));

    $response->assertOk();
});

test('authenticated users can update a team', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create(['name' => 'Old Name']);

    $response = $this->actingAs($user)->put(route('teams.update', $team), [
        'name' => 'New Name',
    ]);

    $response->assertRedirect(route('teams.index'));
    $this->assertDatabaseHas('teams', [
        'id' => $team->id,
        'name' => 'New Name',
    ]);
});

test('authenticated users can delete a team not used in tournaments', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();

    $response = $this->actingAs($user)->delete(route('teams.destroy', $team));

    $response->assertRedirect(route('teams.index'));
    $this->assertDatabaseMissing('teams', [
        'id' => $team->id,
    ]);
});

test('authenticated users cannot delete a team used in tournaments', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $tournament = Tournament::factory()->create(['user_id' => $user->id]);
    $team->tournaments()->attach($tournament->id, ['strength' => 10]);

    $response = $this->actingAs($user)->delete(route('teams.destroy', $team));

    $response->assertForbidden();
    $this->assertDatabaseHas('teams', [
        'id' => $team->id,
    ]);
});
