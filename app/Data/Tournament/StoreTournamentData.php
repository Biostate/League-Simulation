<?php

namespace App\Data\Tournament;

use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\DataCollection;

class StoreTournamentData extends Data
{
    public function __construct(
        public string $name,
        public int $userId,
        /** @var DataCollection<int, TeamSelectionData> */
        #[DataCollectionOf(TeamSelectionData::class)]
        public DataCollection $teams,
    ) {}
}
