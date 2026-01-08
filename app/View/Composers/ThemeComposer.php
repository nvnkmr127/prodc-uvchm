<?php

namespace App\View\Composers;

use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use App\Models\AcademicYear;
use App\Models\Student;

class ThemeComposer
{
    /**
     * Bind data to the view.
     *
     * @param  \Illuminate\View\View  $view
     * @return void
     */
    public function compose(View $view)
    {
        // 1. Academic Years Logic - Cached for 60 minutes
        if (Schema::hasTable('academic_years')) {
            $allAcademicYears = Cache::remember('global_academic_years', 3600, function () {
                return AcademicYear::orderBy('start_date', 'desc')->get();
            });

            // Session value cannot be cached globally, but looking up the default current year can be
            $currentYearId = Cache::remember('current_academic_year_id', 3600, function () {
                return AcademicYear::where('is_current', true)->value('id');
            });

            $selectedAcademicYearId = session('selected_academic_year_id', $currentYearId);

            $view->with('allAcademicYears', $allAcademicYears)
                ->with('selectedAcademicYearId', $selectedAcademicYearId);
        }

        // 2. Pending Student Profile Requests - Cached for 10 minutes
        // CACHE KEY: pending_student_profile_requests_count
        if (Schema::hasTable('student_profile_requests')) {
            $pendingReqCount = Cache::remember('pending_student_profile_requests_count', 600, function () {
                return DB::table('student_profile_requests')->where('status', 'pending')->count();
            });
            $view->with('pendingReqCount', $pendingReqCount);
        } else {
            $view->with('pendingReqCount', 0);
        }

        // 3. Unmapped Biometric Students - Cached for 10 minutes
        // CACHE KEY: unmapped_biometric_students_count
        if (Schema::hasTable('students')) {
            $unmappedCount = Cache::remember('unmapped_biometric_students_count', 600, function () {
                return Student::where('status', 'active')
                    ->whereNull('biometric_employee_code')
                    ->count();
            });
            $view->with('unmappedCount', $unmappedCount);
        } else {
            $view->with('unmappedCount', 0);
        }

        // 4. Unread Notification Count
        // User specific, keeping short cache (1 minute) to avoid spamming DB on page refreshing
        $userId = auth()->id();
        $unreadNotificationCount = 0;

        if ($userId && Schema::hasTable('system_notifications')) {
            $unreadCacheKey = "user_{$userId}_unread_notifications";
            $unreadNotificationCount = Cache::remember($unreadCacheKey, 60, function () use ($userId) {
                return \App\Models\SystemNotification::getUnreadCountForUser($userId);
            });
        }
        $view->with('unreadNotificationCount', $unreadNotificationCount);
    }
}
