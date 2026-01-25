<?php

namespace App\Services;

use App\Models\Game;
use App\Models\Tournament;

class MatchGenerationService
{
    /**
     * Generate all possible round-robin matches for a tournament and distribute them across weeks.
     */
    public function generateMatches(Tournament $tournament): void
    {
        $teams = $tournament->teams;

        if ($teams->count() < 2) {
            return;
        }

        $teamIds = $teams->pluck('id')->toArray();
        $matches = $this->generateRoundRobinPairs($teamIds);
        $weekSchedule = $this->distributeMatchesAcrossWeeks($teamIds, $matches);

        foreach ($weekSchedule as $week => $weekMatches) {
            foreach ($weekMatches as $match) {
                Game::create([
                    'tournament_id' => $tournament->id,
                    'home_team_id' => $match['home'],
                    'away_team_id' => $match['away'],
                    'week' => $week,
                    'is_played' => false,
                ]);
            }
        }
    }

    /**
     * Generate all unique pairs for round-robin tournament.
     *
     * @param  array<int>  $teamIds
     * @return array<array{home: int, away: int}>
     */
    private function generateRoundRobinPairs(array $teamIds): array
    {
        $matches = [];
        $count = count($teamIds);

        for ($i = 0; $i < $count; $i++) {
            for ($j = $i + 1; $j < $count; $j++) {
                $matches[] = [
                    'home' => $teamIds[$i],
                    'away' => $teamIds[$j],
                ];
            }
        }

        return $matches;
    }

    /**
     * Distribute matches across weeks ensuring no team plays twice in the same week.
     * Uses circular rotation algorithm for round-robin scheduling.
     *
     * @param  array<int>  $teamIds
     * @param  array<array{home: int, away: int}>  $matches
     * @return array<int, array<int, array{home: int, away: int}>>
     */
    private function distributeMatchesAcrossWeeks(array $teamIds, array $matches): array
    {
        $teamCount = count($teamIds);
        $weeks = $teamCount % 2 === 0 ? $teamCount - 1 : $teamCount;
        $weekSchedule = [];

        for ($week = 1; $week <= $weeks; $week++) {
            $weekSchedule[$week] = [];
        }

        $teamUsage = [];
        foreach ($teamIds as $teamId) {
            $teamUsage[$teamId] = [];
        }

        foreach ($matches as $match) {
            $placed = false;
            $startWeek = 1;

            while (! $placed) {
                for ($week = $startWeek; $week <= $weeks; $week++) {
                    if ($this->canPlaceMatchInWeek($match, $weekSchedule[$week], $teamUsage, $week)) {
                        $weekSchedule[$week][] = $match;
                        $teamUsage[$match['home']][$week] = true;
                        $teamUsage[$match['away']][$week] = true;
                        $placed = true;
                        break;
                    }
                }

                if (! $placed) {
                    $startWeek = ($startWeek % $weeks) + 1;
                }
            }
        }

        return $weekSchedule;
    }

    /**
     * Check if a match can be placed in a specific week without conflicts.
     *
     * @param  array{home: int, away: int}  $match
     * @param  array<int, array{home: int, away: int}>  $weekMatches
     * @param  array<int, array<int, bool>>  $teamUsage
     */
    private function canPlaceMatchInWeek(array $match, array $weekMatches, array $teamUsage, int $week): bool
    {
        if (isset($teamUsage[$match['home']][$week]) || isset($teamUsage[$match['away']][$week])) {
            return false;
        }

        foreach ($weekMatches as $existingMatch) {
            if ($existingMatch['home'] === $match['home'] || $existingMatch['home'] === $match['away'] ||
                $existingMatch['away'] === $match['home'] || $existingMatch['away'] === $match['away']) {
                return false;
            }
        }

        return true;
    }
}
