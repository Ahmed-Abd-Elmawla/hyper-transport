<?php

namespace App\Providers;

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
        \App\Models\Trip::observe(\App\Observers\TripObserver::class);

        // Place the indicator before the user account icon
        \Filament\Support\Facades\FilamentView::registerRenderHook(
            \Filament\View\PanelsRenderHook::GLOBAL_SEARCH_AFTER,
            fn () => \Livewire\Livewire::mount('active-trips-indicator')
        );
    }
}
