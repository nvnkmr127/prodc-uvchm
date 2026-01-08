<?php

namespace App\Providers;

use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;
use App\Models\AcademicYear;

class ViewServiceProvider extends ServiceProvider
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
        // Use a View Composer to share data with the 'layouts.theme' view.
        // Use a View Composer to share data with the 'layouts.theme' and 'layouts.notification-layout' views.
        View::composer(['layouts.theme', 'layouts.notification-layout'], \App\View\Composers\ThemeComposer::class);
    }
}
