<?php

namespace App\Services\DataProviders;

use App\Contracts\WidgetDataProviderInterface;
use App\Models\Student;

class StudentEnrollmentDataService implements WidgetDataProviderInterface
{
    public function getData(array $params = []): array
    {
        $period = $params['period'] ?? '12_months';

        $data = Student::selectRaw('DATE_FORMAT(created_at, "%Y-%m") as month, COUNT(*) as count')
            ->where('created_at', '>=', now()->subMonths(12))
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        return [
            'labels' => $data->pluck('month')->toArray(),
            'datasets' => [
                [
                    'label' => 'New Enrollments',
                    'data' => $data->pluck('count')->toArray(),
                    'borderColor' => '#3B82F6',
                    'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                ],
            ],
        ];
    }
}
