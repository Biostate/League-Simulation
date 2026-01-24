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
            'Manchester United' => 'manchester_united.svg',
            'Liverpool' => 'liverpool.svg',
            'Chelsea' => 'chelsea.svg',
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
