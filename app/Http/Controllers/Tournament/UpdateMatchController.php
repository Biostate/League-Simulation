<?php

namespace App\Http\Controllers\Tournament;

use App\Http\Controllers\Controller;
use App\Http\Requests\MatchUpdateRequest;
use App\Models\Game;
use App\Models\Tournament;
use Illuminate\Http\RedirectResponse;

class UpdateMatchController extends Controller
{
    public function __invoke(MatchUpdateRequest $request, Tournament $tournament, Game $game): RedirectResponse
    {
        $this->authorize('update', $tournament);

        if ($game->tournament_id !== $tournament->id) {
            return back()->with('error', 'Match does not belong to this tournament.');
        }

        // Prevent editing matches in future weeks
        if ($game->week > $tournament->current_week) {
            return back()->with('error', 'Cannot edit matches in future weeks.');
        }

        $validated = $request->validated();

        $isPlayed = $validated['home_score'] !== null && $validated['away_score'] !== null;

        $game->update([
            'home_score' => $validated['home_score'],
            'away_score' => $validated['away_score'],
            'is_played' => $isPlayed,
        ]);

        return back()->with('success', 'Match updated successfully.');
    }
}
