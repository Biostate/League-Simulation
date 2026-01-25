<?php

namespace App\Data;

use Spatie\LaravelData\Data;

/** @typescript */
class StandingData extends Data
{
    public function __construct(
        public int $id,
        public int $tournamentId,
        public int $teamId,
        public int $played,
        public int $won,
        public int $drawn,
        public int $lost,
        public int $goalsFor,
        public int $goalsAgainst,
        public int $goalDifference,
        public int $points,
        public ?TeamData $team = null,
    ) {}

    public static function fromModel($model): self
    {
        return new self(
            id: $model->id,
            tournamentId: $model->tournament_id,
            teamId: $model->team_id,
            played: $model->played,
            won: $model->won,
            drawn: $model->drawn,
            lost: $model->lost,
            goalsFor: $model->goals_for,
            goalsAgainst: $model->goals_against,
            goalDifference: $model->goal_difference,
            points: $model->points,
            team: $model->relationLoaded('team') && $model->team ? TeamData::fromModel($model->team) : null,
        );
    }
}
