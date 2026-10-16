<?php

use App\Http\Controllers\MembershipApplicationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PublicRankingController;
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
});
Route::get('/matches', [PublicRankingController::class, 'matches'])->name('matches');
Route::get('/player/{user}', [PublicRankingController::class, 'playerProfile'])->name('player.profile');
Route::get('/head-to-head', [PublicRankingController::class, 'headToHead'])->name('head-to-head');
