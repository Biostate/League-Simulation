<?php

namespace App\Data\Tournament;

use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\DataCollection;

class UpdateTournamentData extends Data
{
    public function __construct(
        public string $name,
        /** @var DataCollection<int, TeamSelectionData> */
        #[DataCollectionOf(TeamSelectionData::class)]
        public DataCollection $teams,
    ) {}
}
