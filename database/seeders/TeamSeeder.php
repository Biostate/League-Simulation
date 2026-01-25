<?php

namespace Database\Seeders;

use App\Models\Team;
use Illuminate\Database\Seeder;

class TeamSeeder extends Seeder
{
    public function run(): void
    {
        $teams = [
            'Arsenal' => 'arsenal.svg',
            'Aston Villa' => 'aston_villa.svg',
            'Bournemouth' => 'bournemouth.svg',
            'Brentford' => 'brentford.svg',
            'Brighton & Hove Albion' => 'brighton_and_hove_albion.svg',
            'Burnley' => 'burnley.svg',
            'Chelsea' => 'chelsea.svg',
            'Crystal Palace' => 'crystal_palace.svg',
            'Everton' => 'everton.svg',
            'Fulham' => 'fulham.svg',
            'Leeds United' => 'leeds_united.svg',
            'Liverpool' => 'liverpool.svg',
            'Manchester City' => 'manchester_city.svg',
            'Manchester United' => 'manchester_united.svg',
            'Newcastle United' => 'newcastle_united.svg',
            'Sunderland' => 'sunderland.svg',
            'Tottenham Hotspur' => 'tottenham_hotspur.svg',
            'West Ham United' => 'west_ham_united.svg',
            'Wolverhampton Wanderers' => 'wolverhampton_wanderers.svg',
            'Nottingham Forest' => 'nottingham_forest.svg',
        ];

        foreach ($teams as $teamName => $logoFile) {
            $team = Team::firstOrCreate(
                ['name' => $teamName],
                ['name' => $teamName]
            );

            if (! $team->hasMedia('logo')) {
                $logoPath = resource_path("images/{$logoFile}");
                if (file_exists($logoPath)) {
                    $team->addMedia($logoPath)
                        ->preservingOriginal()
                        ->toMediaCollection('logo');
                }
            }
        }
    }
}
