<?php

namespace App\Data;

use App\Enums\TournamentStatus;
use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\DataCollection;

/** @typescript */
class TournamentData extends Data
{
    public function __construct(
        public int $id,
        public string $name,
        public TournamentStatus $status,
        public int $userId,
        public int $currentWeek,
        #[DataCollectionOf(TeamTournamentData::class)]
        public ?DataCollection $teams,
    ) {}
}
