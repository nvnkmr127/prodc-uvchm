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
        if (config('app.enable_academic_year_global_scope', true) && !app()->runningInConsole() && !request()->is('api/*')) {

            static::addGlobalScope('academic_year', function (Builder $builder) {
                // Get selected year from session, or default to current year
                $selectedYearId = session('selected_academic_year_id');

                if (!$selectedYearId) {
                    $currentYear = AcademicYear::where('is_current', true)->first();
                    $selectedYearId = $currentYear?->id;
                }

                // Determine column name (default to academic_year_id)
                $columnName = $builder->getModel()->academic_year_column ?? 'academic_year_id';

                // Only apply filter if we have a year ID and column exists
                if ($selectedYearId && \Schema::hasColumn($builder->getModel()->getTable(), $columnName)) {

                    // If column is 'academic_year' (not ID), we need to find the NAME of the year
                    $valueToFilter = $selectedYearId;

                    if ($columnName !== 'academic_year_id') {
                        // Attempt to find the year name
                        // Cache this query to avoid hitting DB on every model boot if possible, 
                        // but here strict correctness is key.
                        $yearModel = AcademicYear::find($selectedYearId);
                        if ($yearModel) {
                            $valueToFilter = $yearModel->name;
                        }
                    }

                    $builder->where(
                        $builder->getModel()->getTable() . '.' . $columnName,
                        $valueToFilter
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
        $columnName = $this->academic_year_column ?? 'academic_year_id';

        $value = $academicYearId;
        if ($columnName !== 'academic_year_id' && is_numeric($academicYearId)) {
            $year = AcademicYear::find($academicYearId);
            if ($year)
                $value = $year->name;
        }

        return $query->where($columnName, $value);
    }

    /**
     * Scope for current academic year
     */
    public function scopeForCurrentYear(Builder $query)
    {
        $currentYear = AcademicYear::where('is_current', true)->first();
        $columnName = $this->academic_year_column ?? 'academic_year_id';

        if ($currentYear) {
            $value = ($columnName === 'academic_year_id') ? $currentYear->id : $currentYear->name;
            return $query->where($columnName, $value);
        }
        return $query;
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
