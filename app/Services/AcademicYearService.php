<?php

namespace App\Services;

use App\Models\AcademicYear;
use App\Exceptions\MissingAcademicYearException;
use Illuminate\Support\Facades\Cache;

class AcademicYearService
{
    private const CACHE_KEY_CURRENT = 'academic_year_current';
    private const CACHE_TTL = 3600; // 1 hour

    /**
     * Get the current academic year model.
     * Uses cache to reduce DB queries.
     * 
     * @return AcademicYear
     * @throws MissingAcademicYearException
     */
    public function getCurrentAcademicYear(): AcademicYear
    {
        $year = Cache::remember(self::CACHE_KEY_CURRENT, self::CACHE_TTL, function () {
            return AcademicYear::where('is_current', true)->first();
        });

        if (!$year) {
            throw new MissingAcademicYearException();
        }

        return $year;
    }

    /**
     * Get the currently active academic year ID.
     * Checks session first, then falls back to the current academic year.
     * 
     * @return int
     * @throws MissingAcademicYearException
     */
    public function getActiveAcademicYearId(): int
    {
        $selectedId = session('selected_academic_year_id');

        if ($selectedId) {
            return (int) $selectedId;
        }

        return $this->getCurrentAcademicYear()->id;
    }

    /**
     * Resolve an academic year ID to its name.
     * 
     * @param int|string $id
     * @return string|null
     */
    public function resolveAcademicYearName($id): ?string
    {
        if (is_numeric($id)) {
            $year = Cache::remember("academic_year_name_{$id}", self::CACHE_TTL, function () use ($id) {
                return AcademicYear::find($id)?->name;
            });
            return $year;
        }

        return $id;
    }

    /**
     * Clear all academic year related caches.
     */
    public function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY_CURRENT);
        // We could also loop and clear individual name caches if needed,
        // but typically academic years don't change names.
    }
}
