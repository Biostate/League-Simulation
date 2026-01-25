<?php

namespace App\Services;

use App\Enums\TournamentStatus;
use App\Models\Game;
use App\Models\Tournament;

class SimulationService
{
    private const HOME_ADVANTAGE = 10;

    private const RANDOM_FACTOR = 15;

    public function playAllWeeks(Tournament $tournament): void
    {
        $finalWeek = $tournament->total_weeks;

        while ($tournament->current_week < $finalWeek) {
            $this->playNextWeek($tournament, false);

            $tournament->current_week++;
        }

        $tournament->status = $this->determineTournamentStatus($tournament, $finalWeek);
        $tournament->save();
    }

    public function playNextWeek(Tournament $tournament, bool $updateTournament = true): void
    {
        $targetWeek = $tournament->current_week + 1;

        if ($targetWeek > $tournament->total_weeks) {
            throw new \RuntimeException('All weeks have been played.');
        }

        $matchesForWeek = $this->getMatchesForWeek($tournament, $targetWeek);

        $this->processWeekMatches($tournament, $matchesForWeek);

        if ($updateTournament) {
            $tournament->current_week = $targetWeek;
            $tournament->status = $this->determineTournamentStatus($tournament, $targetWeek);
            $tournament->save();
        }
    }

    private function getMatchesForWeek(Tournament $tournament, int $week)
    {
        $matches = $tournament->matches()
            ->where('is_played', false)
            ->where('week', $week)
            ->with(['homeTeam', 'awayTeam'])
            ->get();

        if ($matches->isEmpty()) {
            throw new \RuntimeException("No matches found for week {$week}.");
        }

        return $matches;
    }

    private function processWeekMatches(Tournament $tournament, $matches): void
    {
        foreach ($matches as $match) {
            $this->simulateMatch($match, $tournament);
        }
    }

    private function determineTournamentStatus(Tournament $tournament, int $week): TournamentStatus
    {
        if ($week >= $tournament->total_weeks) {
            return TournamentStatus::COMPLETED;
        }

        return TournamentStatus::IN_PROGRESS;
    }

    protected function simulateMatch(Game $match, Tournament $tournament): void
    {
        $homeTeam = $match->homeTeam;
        $awayTeam = $match->awayTeam;

        $homeTeamPivot = $tournament->teams()->find($homeTeam->id);
        $awayTeamPivot = $tournament->teams()->find($awayTeam->id);

        $baseHomeStrength = $homeTeamPivot?->pivot->strength ?? 50;
        $baseAwayStrength = $awayTeamPivot?->pivot->strength ?? 50;

        $adjustedHomeStrength = $this->adjustTeamStrength($baseHomeStrength, true);
        $adjustedAwayStrength = $this->adjustTeamStrength($baseAwayStrength, false);

        $homeGoals = $this->calculateGoals($adjustedHomeStrength);
        $awayGoals = $this->calculateGoals($adjustedAwayStrength);

        $match->update([
            'home_score' => $homeGoals,
            'away_score' => $awayGoals,
            'is_played' => true,
        ]);
    }

    private function adjustTeamStrength(int $baseStrength, bool $isHome): int
    {
        $adjustment = $isHome ? self::HOME_ADVANTAGE : 0;
        $randomVariation = mt_rand(-self::RANDOM_FACTOR, self::RANDOM_FACTOR);

        return min(100, max(0, $baseStrength + $adjustment + $randomVariation));
    }

    private function calculateGoals(int $strength): int
    {
        $lambda = 0.5 + ($strength / 100 * 2);

        $threshold = exp(-$lambda);
        $probability = 1.0;
        $goalCount = 0;

        while ($probability > $threshold) {
            $goalCount++;
            $randomValue = mt_rand() / mt_getrandmax();
            $probability *= $randomValue;
        }

        return $goalCount - 1;
    }
}
