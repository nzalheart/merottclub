<?php

namespace App\Providers;

use App\Models\MatchChallenge;
use App\Models\ThemeSetting;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        View::composer('layouts.app', function ($view) {
            $openMatchCount = 0;

            if (auth()->check() && Schema::hasTable('match_challenges')) {
                $openMatchCount = MatchChallenge::query()
                    ->visibleTo((int) auth()->id())
                    ->count();
            }

            $view->with('theme', ThemeSetting::current());
            $view->with('openMatchCount', $openMatchCount);
        });
    }
}
