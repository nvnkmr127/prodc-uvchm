<?php

namespace App\Services\DataProviders;

use App\Contracts\WidgetDataProviderInterface;
use App\Models\Student;

class KPIDataService implements WidgetDataProviderInterface
{
    public function getData(array $params = []): array
    {
        $metric = $params['metric'] ?? 'total_students';

        switch ($metric) {
            case 'total_students':
                $current = Student::count();
                $previous = Student::where('created_at', '<', now()->subMonth())->count();

                return [
                    'value' => $current,
                    'previousValue' => $previous,
                    'target' => 1000,
                    'historical' => $this->getHistoricalData(),
                ];

            default:
                return ['value' => 0, 'previousValue' => 0];
        }
    }

    private function getHistoricalData(): array
    {
        return Student::selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->where('created_at', '>=', now()->subDays(30))
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('count')
            ->toArray();
    }
}
