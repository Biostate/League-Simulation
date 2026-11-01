<?php

namespace App\Data\Tournament;

use Spatie\LaravelData\Data;

class TeamSelectionData extends Data
{
    public function __construct(
        public int $id,
        public int $strength,
    ) {}
}
