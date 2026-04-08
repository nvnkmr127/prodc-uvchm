<?php

namespace App\Services;

use App\Models\StudentPortalActivityLog;
use Carbon\Carbon;

class SuspiciousActivityDetector
{
    /**
     * Detect and flag suspicious activities
     */
    public static function detect($studentId, $action, $ipAddress, $locationData)
    {
        $suspicious = false;
        $reasons = [];

        // Rule 1: Multiple failed logins (3+ in 15 minutes)
        if ($action === 'login_failed') {
            $recentFailures = StudentPortalActivityLog::where('student_id', $studentId)
                ->where('action', 'login_failed')
                ->where('created_at', '>=', Carbon::now()->subMinutes(15))
                ->count();

            if ($recentFailures >= 2) { // Current attempt is 3rd
                $suspicious = true;
                $reasons[] = 'Multiple failed login attempts (3+ in 15 minutes)';
            }
        }

        // Rule 2: Unusual location (different city/country in short time)
        if ($action === 'login_success' && $locationData) {
            $recentLogin = StudentPortalActivityLog::where('student_id', $studentId)
                ->where('action', 'login_success')
                ->where('created_at', '>=', Carbon::now()->subHours(2))
                ->orderBy('created_at', 'desc')
                ->skip(1) // Skip current login
                ->first();

            if ($recentLogin && $recentLogin->location_data) {
                $prevLocation = $recentLogin->location_data;
                $currentLocation = $locationData;

                // Check if city or country changed
                if (isset($prevLocation['city']) && isset($currentLocation['city'])) {
                    if (
                        $prevLocation['city'] !== $currentLocation['city'] ||
                        $prevLocation['country'] !== $currentLocation['country']
                    ) {
                        $suspicious = true;
                        $reasons[] = "Login from different location ({$prevLocation['city']}, {$prevLocation['country']} → {$currentLocation['city']}, {$currentLocation['country']})";
                    }
                }
            }
        }

        // Rule 3: Multiple IPs (same student, different IPs in 1 hour)
        if ($action === 'login_success') {
            $recentIPs = StudentPortalActivityLog::where('student_id', $studentId)
                ->where('action', 'login_success')
                ->where('created_at', '>=', Carbon::now()->subHour())
                ->distinct()
                ->pluck('ip_address')
                ->filter()
                ->toArray();

            if (count($recentIPs) >= 2 && ! in_array($ipAddress, $recentIPs)) {
                $suspicious = true;
                $reasons[] = 'Multiple IP addresses detected within 1 hour';
            }
        }

        return [
            'is_suspicious' => $suspicious,
            'flagged_reason' => $suspicious ? implode('; ', $reasons) : null,
        ];
    }

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
