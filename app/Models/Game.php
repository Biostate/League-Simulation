<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $tournament_id
 * @property int $home_team_id
 * @property int $away_team_id
 * @property int|null $home_score
 * @property int|null $away_score
 * @property int $week
 * @property bool $is_played
 */
class Game extends Model
{
    /** @use HasFactory<\Database\Factories\GameFactory> */
    use HasFactory;

    protected $table = 'matches';

    protected $fillable = [
        'tournament_id',
        'home_team_id',
        'away_team_id',
        'home_score',
        'away_score',
        'week',
        'is_played',
    ];

    protected $casts = [
        'is_played' => 'boolean',
        'home_score' => 'integer',
        'away_score' => 'integer',
        'week' => 'integer',
    ];

    public function tournament(): BelongsTo
    {
        return $this->belongsTo(Tournament::class);
    }

    public function homeTeam(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'home_team_id');
    }

    public function awayTeam(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'away_team_id');
    }
}
