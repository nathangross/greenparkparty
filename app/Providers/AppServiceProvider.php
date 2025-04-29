<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use App\Models\Party;

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
        View::composer('*', function ($view) {
            $party = Party::where('is_active', true)->first();
            if (!$party) {
                $party = Party::latest('primary_date_start')->first();
            }
            $view->with('party', $party);
        });
    }
}
