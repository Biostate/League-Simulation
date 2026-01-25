<?php

namespace App\Enums;

enum TournamentStatus: string
{
    case CREATED = 'created'; // Tournament is created, but not started
    case IN_PROGRESS = 'in_progress'; // Tournament is in progress, but not simulating
    case SIMULATING = 'simulating'; // Tournament is simulating, playing next week
    case COMPLETED = 'completed'; // Tournament is completed, all weeks have been played
}
