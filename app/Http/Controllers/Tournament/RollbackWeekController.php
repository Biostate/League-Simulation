<?php

namespace App\Http\Controllers\Tournament;

use App\Enums\TournamentStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tournament\RollbackWeekRequest;
use App\Models\Game;
use App\Models\Tournament;
use App\Services\StandingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

class RollbackWeekController extends Controller
{
    public function __invoke(RollbackWeekRequest $request, StandingService $standingService, Tournament $tournament, int $week): RedirectResponse
    {
        $this->authorize('update', $tournament);

        try {
            DB::transaction(function () use ($standingService, $tournament, $week) {
                // Reset all matches in later weeks
                Game::where('tournament_id', $tournament->id)
                    ->where('week', '>', $week)
                    ->update([
                        'is_played' => false,
                        'home_score' => null,
                        'away_score' => null,
                    ]);

                // Recalculate all standings
                $standingService->recalculateAllStandings($tournament);

                // Update tournament current week and status
                $tournament->current_week = $week;
                $tournament->status = $this->determineTournamentStatus($tournament, $week);
                $tournament->save();
            });

            return back()->with('success', 'Tournament rolled back successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to rollback tournament: '.$e->getMessage());
        }
    }

    private function determineTournamentStatus(Tournament $tournament, int $week): TournamentStatus
    {
        if ($week >= $tournament->total_weeks) {
            return TournamentStatus::COMPLETED;
        }

        if ($week === 0) {
            return TournamentStatus::CREATED;
        }

        return TournamentStatus::IN_PROGRESS;
    }
}
