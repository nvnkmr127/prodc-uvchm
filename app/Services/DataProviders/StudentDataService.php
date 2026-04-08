<?php

namespace App\Services\DataProviders;

use App\Contracts\WidgetDataProviderInterface;
use App\Models\Student;
use Carbon\Carbon;

class StudentDataService implements WidgetDataProviderInterface
{
    public function getData(array $params = []): array
    {
        $period = $params['period'] ?? '6_months';

        // Get enrollment trends
        $enrollmentData = Student::selectRaw('DATE_FORMAT(created_at, "%Y-%m") as month, COUNT(*) as count')
            ->where('created_at', '>=', Carbon::now()->subMonths(6))
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        // Current stats
        $totalStudents = Student::count();
        $newThisMonth = Student::whereMonth('created_at', Carbon::now()->month)->count();
        $activeStudents = Student::where('status', 'active')->count();

        return [
            'labels' => $enrollmentData->pluck('month')->toArray(),
            'datasets' => [
                [
                    'label' => 'New Enrollments',
                    'data' => $enrollmentData->pluck('count')->toArray(),
                    'borderColor' => '#3B82F6',
                    'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                    'fill' => true,
                ],
            ],
            'stats' => [
                'total' => $totalStudents,
                'new_this_month' => $newThisMonth,
                'active' => $activeStudents,
            ],
        ];
    }
}
