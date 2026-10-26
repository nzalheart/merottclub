<?php

use App\Http\Controllers\MembershipApplicationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PublicRankingController;
use App\Http\Controllers\RankedMatchController;
use Illuminate\Support\Facades\Route;

Route::get('/', [PublicRankingController::class, 'index'])->name('leaderboard');
Route::middleware('guest')->group(function () {
    Route::get('/apply', [MembershipApplicationController::class, 'create'])->name('apply');
    Route::post('/apply', [MembershipApplicationController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('apply.store');
});
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::post('/logout', [ProfileController::class, 'logout'])->name('logout');

    Route::get('/matches/start', [RankedMatchController::class, 'create'])->name('matches.start');
    Route::post('/matches/start', [RankedMatchController::class, 'store'])->name('matches.start.store');
    Route::get('/matches/requests', [RankedMatchController::class, 'index'])->name('matches.requests');
    Route::get('/matches/challenges/{challenge}', [RankedMatchController::class, 'show'])->name('matches.challenge');
    Route::post('/matches/challenges/{challenge}/accept', [RankedMatchController::class, 'acceptMatch'])->name('matches.challenge.accept');
    Route::post('/matches/challenges/{challenge}/scores', [RankedMatchController::class, 'saveScores'])->name('matches.challenge.scores');
    Route::post('/matches/challenges/{challenge}/accept-score', [RankedMatchController::class, 'acceptScore'])->name('matches.challenge.accept-score');
    Route::post('/matches/challenges/{challenge}/decline', [RankedMatchController::class, 'decline'])->name('matches.challenge.decline');
    Route::post('/matches/challenges/{challenge}/cancel', [RankedMatchController::class, 'cancel'])->name('matches.challenge.cancel');
});
Route::get('/matches', [PublicRankingController::class, 'matches'])->name('matches');
Route::get('/player/{user}', [PublicRankingController::class, 'playerProfile'])->name('player.profile');
Route::get('/head-to-head', [PublicRankingController::class, 'headToHead'])->name('head-to-head');
