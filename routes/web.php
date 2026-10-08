<?php

use App\Http\Controllers\PublicRankingController;
use Illuminate\Support\Facades\Route;

Route::get('/', [PublicRankingController::class, 'index'])->name('leaderboard');
Route::get('/matches', [PublicRankingController::class, 'matches'])->name('matches');
Route::get('/player/{user}', [PublicRankingController::class, 'playerProfile'])->name('player.profile');
Route::get('/head-to-head', [PublicRankingController::class, 'headToHead'])->name('head-to-head');
