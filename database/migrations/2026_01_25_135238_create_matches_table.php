<?php

use App\Models\Team;
use App\Models\Tournament;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('matches', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Tournament::class)
                ->constrained()
                ->cascadeOnDelete();
            $table->foreignIdFor(Team::class, 'home_team_id')
                ->constrained('teams')
                ->cascadeOnDelete();
            $table->foreignIdFor(Team::class, 'away_team_id')
                ->constrained('teams')
                ->cascadeOnDelete();
            $table->integer('home_score')->nullable();
            $table->integer('away_score')->nullable();
            $table->integer('week');
            $table->boolean('is_played')->default(false);
            $table->timestamps();

            $table->unique(['tournament_id', 'home_team_id', 'away_team_id']);
            $table->index(['tournament_id', 'week']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('matches');
    }
};
