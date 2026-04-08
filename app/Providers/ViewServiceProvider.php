<?php

namespace App\Providers;

use App\Models\AcademicYear;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

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
        // This code will only run if the 'academic_years' table exists.
        View::composer('layouts.theme', function ($view) {
            if (Schema::hasTable('academic_years')) {
                // Get all academic years for the dropdown, ordered by newest first
                $allAcademicYears = AcademicYear::orderBy('start_date', 'desc')->get();

                // Get the currently selected year from the session, or default to the one marked 'is_current'
                $selectedAcademicYearId = session('selected_academic_year_id', AcademicYear::where('is_current', true)->value('id'));

                // Share these variables with the view
                $view->with('allAcademicYears', $allAcademicYears)
                    ->with('selectedAcademicYearId', $selectedAcademicYearId);
            }
        });
    }
}
