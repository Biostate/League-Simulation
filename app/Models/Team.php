<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Team extends Model implements HasMedia
{
    /** @use HasFactory<\Database\Factories\TeamFactory> */
    use HasFactory;

    use InteractsWithMedia;

    protected $fillable = [
        'name',
    ];

    public function tournaments(): BelongsToMany
    {
        return $this->belongsToMany(Tournament::class)
            ->withPivot('strength')
            ->withTimestamps();
    }

    public function isUsedInTournaments(): bool
    {
        return $this->tournaments()->exists();
    }

    public function homeMatches(): HasMany
    {
        return $this->hasMany(Game::class, 'home_team_id');
    }

    public function awayMatches(): HasMany
    {
        return $this->hasMany(Game::class, 'away_team_id');
    }

    public function standings(): HasMany
    {
        return $this->hasMany(Standing::class);
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this
            ->addMediaConversion('logo')
            ->nonQueued();
    }
}
