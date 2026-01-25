<?php

namespace Database\Seeders;

use App\Enums\TournamentStatus;
use App\Models\Team;
use App\Models\Tournament;
use App\Models\User;
use App\Services\MatchGenerationService;
use App\Services\StandingService;
use Illuminate\Database\Seeder;

class TournamentSeeder extends Seeder
{
    public function run(): void
    {
        $teams = Team::all();

        if ($teams->isEmpty()) {
            return;
        }

        $user = User::first();

        if (! $user) {
            $user = User::factory()->create([
                'name' => 'Tournament Seeder User',
                'email' => 'tournament@example.com',
            ]);
        }

        $tournament = Tournament::create([
            'name' => 'Premier League Tournament',
            'status' => TournamentStatus::CREATED,
            'user_id' => $user->id,
        ]);

        $teamsData = $teams->mapWithKeys(function ($team) {
            return [$team->id => ['strength' => 10]];
        });

        $tournament->teams()->sync($teamsData);

        $standingService = new StandingService;
        $standingService->createStandings($tournament);

        if ($tournament->teams()->count() >= 2) {
            $matchGenerationService = new MatchGenerationService;
            $matchGenerationService->generateMatches($tournament);
        }
    }
}
