<?php

namespace App\Http\Controllers\Tournament;

use App\Enums\TournamentStatus;
use App\Http\Controllers\Controller;
use App\Models\Tournament;
use App\Services\SimulationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

class PlayAllWeeksController extends Controller
{
    public function __construct(
        private SimulationService $simulationService
    ) {}

    public function __invoke(Tournament $tournament): RedirectResponse
    {
        $this->authorize('update', $tournament);

        if ($tournament->status === TournamentStatus::SIMULATING) {
            return back()->with('error', 'Tournament is already simulating. Use "Play Next Week" to continue.');
        }

        if ($tournament->status === TournamentStatus::COMPLETED) {
            return back()->with('error', 'Tournament is already completed.');
        }

        try {
            DB::transaction(function () use ($tournament) {
                $tournament->status = TournamentStatus::SIMULATING;
                $tournament->save();

                $this->simulationService->playAllWeeks($tournament);
            });

            return back()->with('success', 'All weeks played successfully.');
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
