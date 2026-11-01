<?php

namespace App\Http\Controllers;

use App\Data\MatchData;
use App\Data\StandingData;
use App\Data\TeamData;
use App\Data\Tournament\StoreTournamentData;
use App\Data\Tournament\UpdateTournamentData;
use App\Data\TournamentData;
use App\Enums\TournamentStatus;
use App\Http\Requests\TournamentStoreRequest;
use App\Http\Requests\TournamentUpdateRequest;
use App\Models\Team;
use App\Models\Tournament;
use App\Services\PredictionService;
use App\Services\TournamentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class TournamentController extends Controller
{
    public function __construct(
        private TournamentService $tournamentService,
        private PredictionService $predictionService
    ) {}

    public function index(): Response
    {
        $this->authorize('viewAny', Tournament::class);

        $tournaments = Tournament::query()
            ->where('user_id', Auth::id())
            ->with(['teams.media'])
            ->latest()
            ->paginate(15);

        $tournamentsData = TournamentData::collect($tournaments);

        return Inertia::render('Tournaments/Index', [
            'tournaments' => $tournamentsData->items(),
            'pagination' => [
                'current_page' => $tournaments->currentPage(),
                'last_page' => $tournaments->lastPage(),
                'per_page' => $tournaments->perPage(),
                'total' => $tournaments->total(),
            ],
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Tournament::class);

        $teams = Team::query()
            ->with('media')
            ->latest()
            ->get();

        $teamsData = TeamData::collect($teams);

        return Inertia::render('Tournaments/Create', [
            'teams' => $teamsData->toArray(),
        ]);
    }

    public function store(TournamentStoreRequest $request): RedirectResponse
    {
        $this->authorize('create', Tournament::class);

        $validated = $request->validated();
        $data = StoreTournamentData::from([
            'name' => $validated['name'],
            'userId' => Auth::id(),
            'teams' => $validated['teams'] ?? [],
        ]);

        $this->tournamentService->create($data);

        return redirect()->route('tournaments.index')
            ->with('success', 'Tournament created successfully.');
    }

    public function edit(Tournament $tournament): Response
    {
        $this->authorize('update', $tournament);

        $tournament->load('teams.media');

        $allTeams = Team::query()
            ->with('media')
            ->latest()
            ->get();

        $allTeamsData = TeamData::collect($allTeams);

        return Inertia::render('Tournaments/Edit', [
            'tournament' => TournamentData::from($tournament)->toArray(),
            'teams' => $allTeamsData->toArray(),
        ]);
    }

    public function update(TournamentUpdateRequest $request, Tournament $tournament): RedirectResponse
    {
        $this->authorize('update', $tournament);

        if ($tournament->status !== TournamentStatus::CREATED) {
            return redirect()->route('tournaments.index')
                ->with('error', 'Only tournaments with "created" status can be updated.');
        }

        $validated = $request->validated();
        $data = UpdateTournamentData::from([
            'name' => $validated['name'],
            'teams' => $validated['teams'] ?? [],
        ]);

        $this->tournamentService->update($data, $tournament);

        return redirect()->route('tournaments.index')
            ->with('success', 'Tournament updated successfully.');
    }

    public function destroy(Tournament $tournament): RedirectResponse
    {
        $this->authorize('delete', $tournament);

        $tournament->delete();

        return redirect()->route('tournaments.index')
            ->with('success', 'Tournament deleted successfully.');
    }

    public function simulate(Tournament $tournament): Response
    {
        $this->authorize('view', $tournament);

        $tournament->load([
            'teams.media',
            'matches.homeTeam.media',
            'matches.awayTeam.media',
            'standings.team.media',
        ]);

        $matchesData = MatchData::collect($tournament->matches);

        $standings = $tournament->standings()
            ->with('team.media')
            ->orderByDesc('points')
            ->orderByDesc('goal_difference')
            ->orderByDesc('goals_for')
            ->get();

        $standingsData = StandingData::collect($standings);

        $predictions = [];
        if ($this->predictionService->canPredict($tournament)) {
            $predictions = $this->predictionService->predict($tournament);
        }

        return Inertia::render('Tournaments/Simulate', [
            'tournament' => TournamentData::from($tournament)->toArray(),
            'matches' => $matchesData->toArray(),
            'standings' => $standingsData->toArray(),
            'predictions' => $predictions,
        ]);
    }
}
