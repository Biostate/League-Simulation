<?php

use App\Http\Controllers\TeamController;
use App\Http\Controllers\Tournament\PlayAllWeeksController;
use App\Http\Controllers\Tournament\PlayNextWeekController;
use App\Http\Controllers\TournamentController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canRegister' => Features::enabled(Features::registration()),
    ]);
})->name('home');

Route::get('dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('teams', TeamController::class);
    Route::resource('tournaments', TournamentController::class)->except(['show']);
    Route::get('tournaments/{tournament}/simulate', [TournamentController::class, 'simulate'])->name('tournaments.simulate');
    Route::post('tournaments/{tournament}/play-next-week', PlayNextWeekController::class)->name('tournaments.play-next-week');
    Route::post('tournaments/{tournament}/play-all-weeks', PlayAllWeeksController::class)->name('tournaments.play-all-weeks');
});

require __DIR__.'/settings.php';
