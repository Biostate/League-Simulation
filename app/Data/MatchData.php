<?php

namespace App\Data;

use Spatie\LaravelData\Data;

/** @typescript */
class MatchData extends Data
{
    public function __construct(
        public int $id,
        public int $tournamentId,
        public int $homeTeamId,
        public int $awayTeamId,
        public ?int $homeScore,
        public ?int $awayScore,
        public int $week,
        public bool $isPlayed,
        public ?TeamData $homeTeam = null,
        public ?TeamData $awayTeam = null,
    ) {}
}
