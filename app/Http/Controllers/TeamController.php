<?php

namespace App\Http\Controllers;

use App\Data\TeamData;
use App\Http\Requests\TeamStoreRequest;
use App\Http\Requests\TeamUpdateRequest;
use App\Models\Team;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class TeamController extends Controller
{
    public function index()
    {
        $this->authorize('viewAny', Team::class);

        $teams = Team::query()
            ->with('media')
            ->latest()
            ->paginate(15);

        $teamsData = TeamData::collect($teams);

        if (
            (request()->ajax() || request()->wantsJson())
            && ! request()->header('X-Inertia')
        ) {
            return response()->json([
                'data' => $teamsData->items(),
                'pagination' => [
                    'current_page' => $teamsData->currentPage(),
                    'last_page' => $teamsData->lastPage(),
                    'per_page' => $teamsData->perPage(),
                    'total' => $teamsData->total(),
                ],
            ]);
        }

        return Inertia::render('Teams/Index', [
            'teams' => $teamsData->items(),
            'pagination' => [
                'current_page' => $teamsData->currentPage(),
                'last_page' => $teamsData->lastPage(),
                'per_page' => $teamsData->perPage(),
                'total' => $teamsData->total(),
            ],
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Team::class);

        return Inertia::render('Teams/Create');
    }

    public function store(TeamStoreRequest $request)
    {
        $this->authorize('create', Team::class);

        $team = Team::create($request->validated());
        $team->load('media');

        if ($request->header('X-Inertia')) {
            return back()->with('team', TeamData::fromModel($team)->toArray());
        }

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'data' => TeamData::fromModel($team)->toArray(),
            ], 201);
        }

        return redirect()->route('teams.index')
            ->with('success', 'Team created successfully.');
    }

    public function edit(Team $team): Response
    {
        $this->authorize('update', $team);

        $team->load('media');

        return Inertia::render('Teams/Edit', [
            'team' => TeamData::fromModel($team)->toArray(),
        ]);
    }

    public function update(TeamUpdateRequest $request, Team $team): RedirectResponse
    {
        $this->authorize('update', $team);

        $team->update($request->validated());

        return redirect()->route('teams.index')
            ->with('success', 'Team updated successfully.');
    }

    public function destroy(Team $team): RedirectResponse
    {
        $this->authorize('delete', $team);

        $team->delete();

        return redirect()->route('teams.index')
            ->with('success', 'Team deleted successfully.');
    }
}
