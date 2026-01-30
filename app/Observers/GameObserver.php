<?php

namespace App\Observers;

use App\Models\Game;
use App\Services\StandingService;

class GameObserver
{
    public function __construct(
        private StandingService $standingService
    ) {}

    /**
     * Handle the Game "created" event.
     */
    public function created(Game $game): void
    {
        //
    }

    /**
     * Handle the Game "updated" event.
     */
    public function updated(Game $game): void
    {
        $this->standingService->updateStandings($game);
    }

    /**
     * Handle the Game "deleted" event.
     */
    public function deleted(Game $game): void
    {
        //
    }

    /**
     * Handle the Game "restored" event.
     */
    public function restored(Game $game): void
    {
        //
    }

    /**
     * Handle the Game "force deleted" event.
     */
    public function forceDeleted(Game $game): void
    {
        //
    }
}
