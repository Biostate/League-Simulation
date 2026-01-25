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
        Schema::create('standings', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Tournament::class)
                ->constrained()
                ->cascadeOnDelete();
            $table->foreignIdFor(Team::class)
                ->constrained()
                ->cascadeOnDelete();
            $table->integer('played')->default(0);
            $table->integer('won')->default(0);
            $table->integer('drawn')->default(0);
            $table->integer('lost')->default(0);
            $table->integer('goals_for')->default(0);
            $table->integer('goals_against')->default(0);
            $table->integer('goal_difference')->default(0);
            $table->integer('points')->default(0);
            $table->timestamps();

            $table->unique(['tournament_id', 'team_id']);
            $table->index(['tournament_id', 'points', 'goal_difference']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('standings');
    }
};
