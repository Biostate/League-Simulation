<?php

namespace App\Data;

use Carbon\Carbon;
use Spatie\LaravelData\Data;

/** @typescript */
class TeamData extends Data
{
    public function __construct(
        public int $id,
        public string $name,
        public ?string $createdAt = null,
        public ?string $updatedAt = null,
        public ?string $logoUrl = null,
    ) {}

    public static function fromModel($model): self
    {
        $logoUrl = null;
        if ($model->hasMedia('logo')) {
            $logoUrl = $model->getFirstMediaUrl('logo');
        }

        return new self(
            id: $model->id,
            name: $model->name,
            createdAt: $model->created_at ? Carbon::parse($model->created_at)->toIso8601String() : null,
            updatedAt: $model->updated_at ? Carbon::parse($model->updated_at)->toIso8601String() : null,
            logoUrl: $logoUrl,
        );
    }
}
