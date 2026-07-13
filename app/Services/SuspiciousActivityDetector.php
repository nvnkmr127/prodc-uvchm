<?php

namespace App\Services;

use App\Models\StudentPortalActivityLog;
use Carbon\Carbon;

class SuspiciousActivityDetector
{
    /**
     * Get suspicious activities count
     */
    public static function getSuspiciousCount($hours = 24)
    {
        return StudentPortalActivityLog::where('is_suspicious', true)
            ->where('created_at', '>=', Carbon::now()->subHours($hours))
            ->count();
    }

    /**
     * Get recent suspicious activities
     */
    public static function getRecentSuspicious($limit = 10)
    {
        return StudentPortalActivityLog::with('student')
            ->where('is_suspicious', true)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }
}
