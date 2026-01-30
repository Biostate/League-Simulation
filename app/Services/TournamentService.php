<?php

namespace App\Services;

use App\Data\Tournament\StoreTournamentData;
use App\Data\Tournament\UpdateTournamentData;
use App\Enums\TournamentStatus;
use App\Models\Tournament;

class TournamentService
{
    public function __construct(
        private StandingService $standingService,
        private MatchGenerationService $matchGenerationService
    ) {}

    public function create(StoreTournamentData $data): Tournament
    {
        $teamCount = $data->teams->count();
        $totalWeeks = MatchGenerationService::calculateTotalWeeks($teamCount);

        $tournament = Tournament::create([
            'name' => $data->name,
            'status' => TournamentStatus::CREATED,
            'user_id' => $data->userId,
            'current_week' => 0,
            'total_weeks' => $totalWeeks,
        ]);

        $teamsPivot = [];
        foreach ($data->teams as $team) {
            $teamsPivot[$team->id] = ['strength' => $team->strength];
        }
        $tournament->teams()->sync($teamsPivot);
        $this->standingService->createStandings($tournament);

        if ($tournament->teams()->count() >= 2) {
            $this->matchGenerationService->generateMatches($tournament);
        }

        return $tournament;
    }

    public function update(UpdateTournamentData $data, Tournament $tournament): void
    {
        $tournament->update(['name' => $data->name]);

        if ($data->teams->count() === 0) {
            return;
        }

        $teamCount = $data->teams->count();
        $totalWeeks = MatchGenerationService::calculateTotalWeeks($teamCount);

        $tournament->update(['total_weeks' => $totalWeeks]);

        $teamsPivot = [];
        foreach ($data->teams as $team) {
            $teamsPivot[$team->id] = ['strength' => $team->strength];
        }
        $tournament->teams()->sync($teamsPivot);
        $tournament->matches()->delete();
        $this->standingService->createStandings($tournament);

        if ($tournament->teams()->count() >= 2) {
            $this->matchGenerationService->generateMatches($tournament);
        }
    }
}
