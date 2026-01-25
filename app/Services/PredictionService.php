<?php

namespace App\Services;

use App\Models\Standing;
use App\Models\Tournament;

class PredictionService
{
    private const MIN_WEEK_FOR_PREDICTION = 4;

    private const WEIGHT_CURRENT_PERFORMANCE = 0.4;

    private const WEIGHT_STRENGTH = 0.3;

    private const WEIGHT_FORM = 0.2;

    private const WEIGHT_SCHEDULE = 0.1;

    public function predict(Tournament $tournament): array
    {
        if (! $this->canPredict($tournament)) {
            return [];
        }

        $tournament->load(['standings.team', 'teams', 'matches']);

        $standings = $tournament->standings;
        $eligibleTeams = $this->getEligibleTeams($tournament, $standings);

        if (empty($eligibleTeams)) {
            return $this->createZeroPredictions($standings);
        }

        $scores = $this->calculateScores($tournament, $eligibleTeams);
        $predictions = $this->normalizeToPercentages($scores, $standings);

        return $predictions;
    }

    public function canPredict(Tournament $tournament): bool
    {
        return $tournament->current_week >= self::MIN_WEEK_FOR_PREDICTION;
    }

    private function getEligibleTeams(Tournament $tournament, $standings): array
    {
        $currentLeaderPoints = $standings->max('points');
        $eligibleTeamIds = [];

        foreach ($standings as $standing) {
            $remainingMatches = $this->getRemainingMatchesCount($tournament, $standing->team_id);
            $maxPossiblePoints = $this->calculateMaxPossiblePoints($standing, $remainingMatches);

            if ($maxPossiblePoints >= $currentLeaderPoints) {
                $eligibleTeamIds[] = $standing->team_id;
            }
        }

        return $eligibleTeamIds;
    }

    private function calculateMaxPossiblePoints(Standing $standing, int $remainingMatches): int
    {
        return $standing->points + ($remainingMatches * 3);
    }

    private function getRemainingMatchesCount(Tournament $tournament, int $teamId): int
    {
        return $tournament->matches()
            ->where(function ($query) use ($teamId) {
                $query->where('home_team_id', $teamId)
                    ->orWhere('away_team_id', $teamId);
            })
            ->where('is_played', false)
            ->count();
    }

    private function calculateScores(Tournament $tournament, array $eligibleTeamIds): array
    {
        $scores = [];
        $standings = $tournament->standings->keyBy('team_id');
        $teams = $tournament->teams->keyBy('id');

        foreach ($eligibleTeamIds as $teamId) {
            $standing = $standings->get($teamId);
            $team = $teams->get($teamId);

            if (! $standing || ! $team) {
                continue;
            }

            $remainingMatches = $this->getRemainingMatchesCount($tournament, $teamId);
            $maxPossiblePoints = $this->calculateMaxPossiblePoints($standing, $remainingMatches);

            $currentPerformanceScore = $this->calculateCurrentPerformanceScore($standing, $maxPossiblePoints);
            $strengthScore = $this->calculateStrengthScore($tournament, $teamId);
            $formScore = $this->calculateFormScore($tournament, $teamId, $standing);
            $scheduleScore = $this->calculateScheduleScore($tournament, $teamId);

            $totalScore = (
                $currentPerformanceScore * self::WEIGHT_CURRENT_PERFORMANCE +
                $strengthScore * self::WEIGHT_STRENGTH +
                $formScore * self::WEIGHT_FORM +
                $scheduleScore * self::WEIGHT_SCHEDULE
            );

            $scores[$teamId] = $totalScore;
        }

        return $scores;
    }

    private function calculateCurrentPerformanceScore(Standing $standing, int $maxPossiblePoints): float
    {
        if ($maxPossiblePoints === 0) {
            return 0.0;
        }

        $pointsRatio = $standing->points / $maxPossiblePoints;

        $maxGoalDifference = 50;
        $normalizedGoalDifference = max(-1, min(1, $standing->goal_difference / $maxGoalDifference));
        $goalDifferenceScore = ($normalizedGoalDifference + 1) / 2;

        return ($pointsRatio * 0.7) + ($goalDifferenceScore * 0.3);
    }

    private function calculateStrengthScore(Tournament $tournament, int $teamId): float
    {
        $teamPivot = $tournament->teams()->find($teamId);

        if (! $teamPivot || ! $teamPivot->pivot) {
            return 0.5;
        }

        $strength = $teamPivot->pivot->strength ?? 50;

        return $strength / 100;
    }

    private function calculateFormScore(Tournament $tournament, int $teamId, Standing $standing): float
    {
        $recentMatches = $tournament->matches()
            ->where(function ($query) use ($teamId) {
                $query->where('home_team_id', $teamId)
                    ->orWhere('away_team_id', $teamId);
            })
            ->where('is_played', true)
            ->orderByDesc('week')
            ->limit(5)
            ->get();

        if ($recentMatches->isEmpty()) {
            return 0.5;
        }

        $formPoints = 0;
        $formGoalDifference = 0;

        foreach ($recentMatches as $match) {
            $isHome = $match->home_team_id === $teamId;

            if ($isHome) {
                if ($match->home_score > $match->away_score) {
                    $formPoints += 3;
                } elseif ($match->home_score === $match->away_score) {
                    $formPoints += 1;
                }
                $formGoalDifference += $match->home_score - $match->away_score;
            } else {
                if ($match->away_score > $match->home_score) {
                    $formPoints += 3;
                } elseif ($match->away_score === $match->home_score) {
                    $formPoints += 1;
                }
                $formGoalDifference += $match->away_score - $match->home_score;
            }
        }

        $maxFormPoints = $recentMatches->count() * 3;
        $formPointsRatio = $maxFormPoints > 0 ? $formPoints / $maxFormPoints : 0.5;

        $maxFormGoalDifference = $recentMatches->count() * 5;
        $normalizedFormGoalDifference = max(-1, min(1, $formGoalDifference / $maxFormGoalDifference));
        $formGoalDifferenceScore = ($normalizedFormGoalDifference + 1) / 2;

        return ($formPointsRatio * 0.7) + ($formGoalDifferenceScore * 0.3);
    }

    private function calculateScheduleScore(Tournament $tournament, int $teamId): float
    {
        $remainingMatches = $tournament->matches()
            ->where(function ($query) use ($teamId) {
                $query->where('home_team_id', $teamId)
                    ->orWhere('away_team_id', $teamId);
            })
            ->where('is_played', false)
            ->with(['homeTeam', 'awayTeam'])
            ->get();

        if ($remainingMatches->isEmpty()) {
            return 0.5;
        }

        $totalOpponentStrength = 0;
        $homeMatches = 0;
        $matchCount = $remainingMatches->count();

        foreach ($remainingMatches as $match) {
            $isHome = $match->home_team_id === $teamId;
            $opponentId = $isHome ? $match->away_team_id : $match->home_team_id;

            if ($isHome) {
                $homeMatches++;
            }

            $opponentPivot = $tournament->teams()->find($opponentId);
            $opponentStrength = $opponentPivot?->pivot->strength ?? 50;
            $totalOpponentStrength += $opponentStrength;
        }

        $averageOpponentStrength = $totalOpponentStrength / $matchCount;
        $difficultyFactor = 1 - ($averageOpponentStrength / 100);

        $homeAdvantage = $homeMatches / $matchCount;
        $homeAdvantageFactor = 0.5 + ($homeAdvantage * 0.2);

        return $difficultyFactor * $homeAdvantageFactor;
    }

    private function normalizeToPercentages(array $scores, $standings): array
    {
        $totalScore = array_sum($scores);

        if ($totalScore === 0.0) {
            return $this->createZeroPredictions($standings);
        }

        $predictions = [];

        foreach ($standings as $standing) {
            $teamId = $standing->team_id;

            if (isset($scores[$teamId])) {
                $predictions[$teamId] = round(($scores[$teamId] / $totalScore) * 100, 1);
            } else {
                $predictions[$teamId] = 0.0;
            }
        }

        return $predictions;
    }

    private function createZeroPredictions($standings): array
    {
        $predictions = [];

        foreach ($standings as $standing) {
            $predictions[$standing->team_id] = 0.0;
        }

        return $predictions;
    }
}
