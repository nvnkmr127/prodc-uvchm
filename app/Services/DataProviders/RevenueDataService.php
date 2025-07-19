<?php

namespace App\Services\DataProviders;

use App\Contracts\WidgetDataProviderInterface;
use App\Models\Payment;
use Carbon\Carbon;

class RevenueDataService implements WidgetDataProviderInterface
{
    public function getData(array $params = []): array
    {
        // Monthly revenue data
        $revenueData = Payment::selectRaw('DATE_FORMAT(created_at, "%Y-%m") as month, SUM(amount) as revenue')
            ->where('status', 'completed')
            ->where('created_at', '>=', Carbon::now()->subMonths(12))
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        // Current month stats
        $currentMonth = Payment::where('status', 'completed')
            ->whereMonth('created_at', Carbon::now()->month)
            ->sum('amount');
            
        $lastMonth = Payment::where('status', 'completed')
            ->whereMonth('created_at', Carbon::now()->subMonth()->month)
            ->sum('amount');

        return [
            'labels' => $revenueData->pluck('month')->toArray(),
            'datasets' => [
                [
                    'label' => 'Monthly Revenue',
                    'data' => $revenueData->pluck('revenue')->toArray(),
                    'borderColor' => '#10B981',
                    'backgroundColor' => 'rgba(16, 185, 129, 0.1)',
                    'fill' => true
                ]
            ],
            'current_month' => $currentMonth,
            'last_month' => $lastMonth,
            'growth' => $lastMonth > 0 ? (($currentMonth - $lastMonth) / $lastMonth) * 100 : 0
        ];
    }
}