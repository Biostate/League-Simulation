<?php

namespace App\Services;

use App\Models\Game;
use App\Models\Tournament;

class MatchGenerationService
{
    /**
     * Generate all possible round-robin matches for a tournament and distribute them across weeks.
     * Creates a double round-robin (home and away) format.
     */
    public function generateMatches(Tournament $tournament): void
    {
        $teams = $tournament->teams;

        if ($teams->count() < 2) {
            $tournament->total_weeks = 0;
            $tournament->save();

            return;
        }

        $teamIds = $teams->pluck('id')->toArray();
        $matches = $this->generateRoundRobinPairs($teamIds);
        $weekSchedule = $this->distributeMatchesAcrossWeeks($teamIds, $matches);

        $totalWeeks = count($weekSchedule) > 0 ? max(array_keys($weekSchedule)) : 0;

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

        $tournament->total_weeks = $totalWeeks;
        $tournament->save();
    }

    public static function calculateTotalWeeks(int $teamCount): int
    {
        if ($teamCount < 2) {
            return 0;
        }

        $firstHalfWeeks = $teamCount % 2 === 0 ? $teamCount - 1 : $teamCount;

        return 2 * $firstHalfWeeks;
    }

    /**
     * Generate all pairs for double round-robin tournament (home and away).
     *
     * @param  array<int>  $teamIds
     * @return array<array{home: int, away: int}>
     */
    private function generateRoundRobinPairs(array $teamIds): array
    {
        $matches = [];
        $count = count($teamIds);

        // Generate home and away matches for each pair
        for ($i = 0; $i < $count; $i++) {
            for ($j = 0; $j < $count; $j++) {
                if ($i !== $j) {
                    $matches[] = [
                        'home' => $teamIds[$i],
                        'away' => $teamIds[$j],
                    ];
                }
            }
        }

        return $matches;
    }

    /**
     * Distribute matches across weeks using circle method for double round-robin scheduling.
     * First half (weeks 1 to teamCount-1): first round-robin
     * Second half (weeks teamCount to 2*(teamCount-1)): return fixtures with reversed home/away.
     *
     * @param  array<int>  $teamIds
     * @param  array<array{home: int, away: int}>  $matches
     * @return array<int, array<int, array{home: int, away: int}>>
     */
    private function distributeMatchesAcrossWeeks(array $teamIds, array $matches): array
    {
        $teamCount = count($teamIds);
        $firstHalfWeeks = $teamCount % 2 === 0 ? $teamCount - 1 : $teamCount;
        $totalWeeks = 2 * $firstHalfWeeks;
        $weekSchedule = [];

        // Create match lookup map
        $matchMap = [];
        foreach ($matches as $match) {
            $key = $match['home'].'-'.$match['away'];
            $matchMap[$key] = $match;
        }

        // Circle method: for even teams, fix first and rotate others
        // For odd teams, rotate all teams
        $isEven = $teamCount % 2 === 0;
        $fixedTeam = $teamIds[0];
        $rotatingTeams = array_slice($teamIds, 1);
        $allTeams = $teamIds;

        // Generate first half (weeks 1 to firstHalfWeeks)
        for ($week = 1; $week <= $firstHalfWeeks; $week++) {
            $weekSchedule[$week] = [];

            if ($isEven) {
                // Pair fixed team with first rotating team
                $key = $fixedTeam.'-'.$rotatingTeams[0];
                if (isset($matchMap[$key])) {
                    $weekSchedule[$week][] = $matchMap[$key];
                }

                // Pair remaining teams (opposite pairs in circle)
                $pairCount = (int) floor((count($rotatingTeams) - 1) / 2);
                for ($i = 1; $i <= $pairCount; $i++) {
                    $team1 = $rotatingTeams[$i];
                    $team2 = $rotatingTeams[count($rotatingTeams) - $i];
                    $key = $team1.'-'.$team2;
                    if (isset($matchMap[$key])) {
                        $weekSchedule[$week][] = $matchMap[$key];
                    }
                }

                // Rotate rotating teams
                $last = array_pop($rotatingTeams);
                array_unshift($rotatingTeams, $last);
            } else {
                // For odd teams, pair all teams in circle (no fixed team)
                $pairCount = (int) floor($teamCount / 2);
                for ($i = 0; $i < $pairCount; $i++) {
                    $team1 = $allTeams[$i];
                    $team2 = $allTeams[$teamCount - 1 - $i];
                    $key = $team1.'-'.$team2;
                    if (isset($matchMap[$key])) {
                        $weekSchedule[$week][] = $matchMap[$key];
                    }
                }

                // Rotate all teams
                $last = array_pop($allTeams);
                array_unshift($allTeams, $last);
            }
        }

        // Generate second half (return fixtures with reversed home/away)
        for ($week = 1; $week <= $firstHalfWeeks; $week++) {
            $returnWeek = $firstHalfWeeks + $week;
            $weekSchedule[$returnWeek] = [];

            foreach ($weekSchedule[$week] as $match) {
                // Reverse home and away for return fixture
                $weekSchedule[$returnWeek][] = [
                    'home' => $match['away'],
                    'away' => $match['home'],
                ];
            }
        }

        return $weekSchedule;
    }
}
