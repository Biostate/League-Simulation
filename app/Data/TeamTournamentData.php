<?php

namespace App\Data;

use Spatie\LaravelData\Data;

/** @typescript */
class TeamTournamentData extends Data
{
    public function __construct(
        public int $id,
        public string $name,
        public int $strength,
        public ?string $logoUrl = null,
    ) {}

    public static function fromModel($model): self
    {
        return new self(
            id: $model->id,
            name: $model->name,
            strength: $model->pivot->strength,
            logoUrl: $model->hasMedia('logo') ? $model->getFirstMediaUrl('logo') : null,
        );
    }
}
