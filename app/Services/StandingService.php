<?php

namespace App\Services;

use App\Models\Game;
use App\Models\Standing;
use App\Models\Tournament;

class StandingService
{
    /**
     * Create standings for all teams in a tournament.
     */
    public function createStandings(Tournament $tournament): void
    {
        $teams = $tournament->teams;

        // Delete existing standings for this tournament
        Standing::where('tournament_id', $tournament->id)->delete();

        // Initialize standings for all teams
        foreach ($teams as $team) {
            Standing::create([
                'tournament_id' => $tournament->id,
                'team_id' => $team->id,
                'played' => 0,
                'won' => 0,
                'drawn' => 0,
                'lost' => 0,
                'goals_for' => 0,
                'goals_against' => 0,
                'goal_difference' => 0,
                'points' => 0,
            ]);
        }
    }

    /**
     * Update standings for teams involved in an updated game.
     * Recalculates standings from scratch for both teams.
     */
    public function updateStandings(Game $game): void
    {
        if (! $game->is_played || $game->home_score === null || $game->away_score === null) {
            return;
        }

        $teamIds = [$game->home_team_id, $game->away_team_id];

        // Fetch all played games for both teams in this tournament in a single query
        $playedGames = Game::where('tournament_id', $game->tournament_id)
            ->where('is_played', true)
            ->whereNotNull('home_score')
            ->whereNotNull('away_score')
            ->where(function ($query) use ($teamIds) {
                $query->whereIn('home_team_id', $teamIds)
                    ->orWhereIn('away_team_id', $teamIds);
            })
            ->get();

        // Fetch standings for both teams in a single query
        $standings = Standing::where('tournament_id', $game->tournament_id)
            ->whereIn('team_id', $teamIds)
            ->get()
            ->keyBy('team_id');

        // Recalculate standings from scratch for both teams
        foreach ($teamIds as $teamId) {
            $standing = $standings->get($teamId);

            if (! $standing) {
                continue;
            }

            // Reset to zero
            $standing->played = 0;
            $standing->won = 0;
            $standing->drawn = 0;
            $standing->lost = 0;
            $standing->goals_for = 0;
            $standing->goals_against = 0;
            $standing->points = 0;

            // Calculate from all played games
            foreach ($playedGames as $match) {
                $isHome = $match->home_team_id === $teamId;
                $isAway = $match->away_team_id === $teamId;

                if (! $isHome && ! $isAway) {
                    continue;
                }

                $standing->played++;
                $standing->goals_for += $isHome ? $match->home_score : $match->away_score;
                $standing->goals_against += $isHome ? $match->away_score : $match->home_score;

                if ($match->home_score > $match->away_score) {
                    if ($isHome) {
                        $standing->won++;
                        $standing->points += 3;
                    } else {
                        $standing->lost++;
                    }
                } elseif ($match->home_score < $match->away_score) {
                    if ($isAway) {
                        $standing->won++;
                        $standing->points += 3;
                    } else {
                        $standing->lost++;
                    }
                } else {
                    $standing->drawn++;
                    $standing->points += 1;
                }
            }

            $standing->goal_difference = $standing->goals_for - $standing->goals_against;
            $standing->save();
        }
    }
}
