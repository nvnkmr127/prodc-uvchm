<?php

namespace App\Traits;

use App\Models\AcademicYear;
use Illuminate\Database\Eloquent\Builder;

trait HasAcademicYear
{
    /**
     * Boot the trait and apply global scope if session year is set
     */
    protected static function bootHasAcademicYear()
    {
        // DISABLED GLOBAL SCOPE - Causing issues with data not showing
        // Global scopes should be applied explicitly when needed, not automatically
        // Use ->forAcademicYear($yearId) or ->forCurrentYear() instead

        // Only apply auto-filtering if:
        // 1. Not running in console (to avoid issues with migrations/seeds)
        // 2. Not in API context (API should explicitly pass year)
        // 3. Explicitly enabled via config (disabled by default)
        if (config('app.enable_academic_year_global_scope', false) && !app()->runningInConsole() && !request()->is('api/*')) {

            static::addGlobalScope('academic_year', function (Builder $builder) {
                // Get selected year from session, or default to current year
                $selectedYearId = session('selected_academic_year_id');

                if (!$selectedYearId) {
                    $currentYear = AcademicYear::where('is_current', true)->first();
                    $selectedYearId = $currentYear?->id;
                }

                // Only apply filter if we have a year ID and column exists
                if ($selectedYearId && \Schema::hasColumn($builder->getModel()->getTable(), 'academic_year_id')) {
                    $builder->where(
                        $builder->getModel()->getTable() . '.academic_year_id',
                        $selectedYearId
                    );
                }
            });
        }
    }

    /**
     * Relationship to academic year
     */
    public function academicYear()
    {
        return $this->belongsTo(AcademicYear::class);
    }

    /**
     * Scope to filter by academic year
     */
    public function scopeForAcademicYear(Builder $query, $academicYearId)
    {
        return $query->where('academic_year_id', $academicYearId);
    }

    /**
     * Scope for current academic year
     */
    public function scopeForCurrentYear(Builder $query)
    {
        $currentYear = AcademicYear::where('is_current', true)->first();
        return $currentYear ? $query->where('academic_year_id', $currentYear->id) : $query;
    }

    /**
     * Disable academic year filtering for this query
     * Use when you need to query across all years
     */
    public function scopeWithoutAcademicYearFilter(Builder $query)
    {
        return $query->withoutGlobalScope('academic_year');
    }

    /**
     * Scope to get all years data (alias for withoutAcademicYearFilter)
     */
    public function scopeAllYears(Builder $query)
    {
        return $query->withoutGlobalScope('academic_year');
    }
}
