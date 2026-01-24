<?php

namespace App\Enums;

enum TournamentStatus: string
{
    case CREATED = 'created';
    case IN_PROGRESS = 'in_progress';
    case COMPLETED = 'completed';
}
