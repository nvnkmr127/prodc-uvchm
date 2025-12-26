<?php

namespace App\Jobs\Attendance;

use App\Models\Attendance\AttendanceCache;
use App\Services\Attendance\AnalyticsService;
use App\Events\Attendance\AnalyticsEvent;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class ProcessAnalyticsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 600; // 10 minutes
    public $tries = 2;

    protected $analyticsType;
    protected $parameters;

    public function __construct(string $analyticsType, array $parameters = [])
    {
        $this->analyticsType = $analyticsType;
        $this->parameters = $parameters;
        $this->onQueue('analytics');
    }

    /**
     * Execute the job
     */
    public function handle(AnalyticsService $analyticsService): void
    {
        try {
            switch ($this->analyticsType) {
                case 'dashboard_refresh':
                    $this->refreshDashboardAnalytics($analyticsService);
                    break;
                case 'student_cache_update':
                    $this->updateStudentCache();
                    break;
                case 'batch_analytics':
                    $this->processBatchAnalytics($analyticsService);
                    break;
                case 'trends_calculation':
                    $this->calculateTrends($analyticsService);
                    break;
                case 'performance_analysis':
                    $this->analyzePerformance($analyticsService);
                    break;
                case 'predictive_analytics':
                    $this->runPredictiveAnalytics($analyticsService);
                    break;
                default:
                    throw new \InvalidArgumentException("Unknown analytics type: {$this->analyticsType}");
            }

        } catch (\Exception $e) {
            Log::error('Analytics processing job failed', [
                'type' => $this->analyticsType,
                'parameters' => $this->parameters,
                'error' => $e->getMessage()
            ]);
            
            throw $e;
        }
    }

    /**
     * Refresh dashboard analytics
     */
    private function refreshDashboardAnalytics(AnalyticsService $analyticsService): void
    {
        $filters = $this->parameters['filters'] ?? [];
        $analytics = $analyticsService->getDashboardAnalytics($filters);
        
        // Cache the results
        $cacheKey = 'dashboard_analytics_' . md5(serialize($filters));
        Cache::put($cacheKey, $analytics, now()->addMinutes(30));
        
        // Fire event for real-time updates
        event(new AnalyticsEvent('dashboard_refreshed', $analytics, $filters));
        
        Log::info('Dashboard analytics refreshed', ['cache_key' => $cacheKey]);
    }

    /**
     * Update student cache
     */
    private function updateStudentCache(): void
    {
        $studentIds = $this->parameters['student_ids'] ?? [];
        $batchIds = $this->parameters['batch_ids'] ?? [];
        
        if (!empty($studentIds)) {
            foreach ($studentIds as $studentId) {
                AttendanceCache::updateForStudent($studentId);
            }
        } elseif (!empty($batchIds)) {
            \App\Models\Student::whereIn('batch_id', $batchIds)
                ->chunk(50, function ($students) {
                    foreach ($students as $student) {
                        AttendanceCache::updateForStudent($student->id);
                    }
                });
        } else {
            // Update all students
            \App\Models\Student::chunk(100, function ($students) {
                foreach ($students as $student) {
                    AttendanceCache::updateForStudent($student->id);
                }
            });
        }
        
        Log::info('Student cache updated', [
            'student_ids' => !empty($studentIds) ? count($studentIds) : 'all',
            'batch_ids' => !empty($batchIds) ? count($batchIds) : 'none'
        ]);
    }

    /**
     * Process batch analytics
     */
    private function processBatchAnalytics(AnalyticsService $analyticsService): void
    {
        $batchIds = $this->parameters['batch_ids'] ?? [];
        $dateRange = $this->parameters['date_range'] ?? [];
        
        foreach ($batchIds as $batchId) {
            $analytics = $analyticsService->getBatchPerformance(['batch_id' => $batchId] + $dateRange);
            
            // Cache batch analytics
            $cacheKey = "batch_analytics_{$batchId}_" . md5(serialize($dateRange));
            Cache::put($cacheKey, $analytics, now()->addHours(6));
            
            // Fire event
            event(new AnalyticsEvent('batch_analyzed', $analytics, ['batch_id' => $batchId]));
        }
        
        Log::info('Batch analytics processed', ['batch_count' => count($batchIds)]);
    }

    /**
     * Calculate trends
     */
    private function calculateTrends(AnalyticsService $analyticsService): void
    {
        $period = $this->parameters['period'] ?? '30days';
        $filters = $this->parameters['filters'] ?? [];
        
        $trends = $analyticsService->getTrendAnalysis($filters);
        
        // Cache trends
        $cacheKey = "attendance_trends_{$period}_" . md5(serialize($filters));
        Cache::put($cacheKey, $trends, now()->addHours(12));
        
        // Fire event
        event(new AnalyticsEvent('trends_calculated', $trends, ['period' => $period]));
        
        Log::info('Attendance trends calculated', ['period' => $period]);
    }

    /**
     * Analyze performance
     */
    private function analyzePerformance(AnalyticsService $analyticsService): void
    {
        $analysisType = $this->parameters['analysis_type'] ?? 'overall';
        $filters = $this->parameters['filters'] ?? [];
        
        switch ($analysisType) {
            case 'low_performers':
                $threshold = $this->parameters['threshold'] ?? 75;
                $lowPerformers = $analyticsService->getLowAttendanceStudents(['threshold' => $threshold] + $filters);
                
                Cache::put('low_attendance_students', $lowPerformers, now()->addHours(1));
                event(new AnalyticsEvent('low_performers_identified', $lowPerformers->toArray()));
                break;
                
            case 'batch_comparison':
                $comparison = $analyticsService->getBatchPerformance($filters);
                Cache::put('batch_performance_comparison', $comparison, now()->addHours(6));
                event(new AnalyticsEvent('batch_comparison_completed', $comparison));
                break;
                
            case 'daily_patterns':
                $patterns = $analyticsService->getDailyPatterns($filters);
                Cache::put('daily_attendance_patterns', $patterns, now()->addHours(24));
                event(new AnalyticsEvent('patterns_analyzed', $patterns));
                break;
        }
        
        Log::info('Performance analysis completed', ['type' => $analysisType]);
    }

    /**
     * Run predictive analytics
     */
    private function runPredictiveAnalytics(AnalyticsService $analyticsService): void
    {
        $predictionType = $this->parameters['prediction_type'] ?? 'risk_assessment';
        
        switch ($predictionType) {
            case 'risk_assessment':
                $this->predictRiskStudents($analyticsService);
                break;
            case 'trend_forecasting':
                $this->forecastTrends($analyticsService);
                break;
            case 'intervention_recommendations':
                $this->generateInterventionRecommendations($analyticsService);
                break;
        }
        
        Log::info('Predictive analytics completed', ['type' => $predictionType]);
    }

    /**
     * Predict at-risk students
     */
    private function predictRiskStudents(AnalyticsService $analyticsService): void
    {
        // Get students with declining trends
        $filters = ['trend_direction' => 'declining'];
        $lowAttendanceStudents = $analyticsService->getLowAttendanceStudents($filters);
        
        $riskPredictions = $lowAttendanceStudents->map(function ($cache) {
            $riskScore = $this->calculateRiskScore($cache);
            return [
                'student_id' => $cache->student_id,
                'student_name' => $cache->student->name,
                'current_percentage' => $cache->attendance_percentage,
                'risk_score' => $riskScore,
                'predicted_outcome' => $this->predictOutcome($riskScore),
                'recommended_actions' => $this->getRecommendedActions($riskScore)
            ];
        });
        
        Cache::put('student_risk_predictions', $riskPredictions, now()->addDays(1));
        event(new AnalyticsEvent('risk_predictions_updated', $riskPredictions->toArray()));
    }

    /**
     * Forecast attendance trends
     */
    private function forecastTrends(AnalyticsService $analyticsService): void
    {
        // Get historical data for trend analysis
        $historicalData = $analyticsService->getTrendAnalysis(['period' => '90days']);
        
        // Simple trend forecasting (can be enhanced with ML algorithms)
        $forecast = $this->calculateTrendForecast($historicalData);
        
        Cache::put('attendance_forecast', $forecast, now()->addDays(7));
        event(new AnalyticsEvent('trends_forecasted', $forecast));
    }

    /**
     * Generate intervention recommendations
     */
    private function generateInterventionRecommendations(AnalyticsService $analyticsService): void
    {
        $lowAttendanceStudents = $analyticsService->getLowAttendanceStudents();
        
        $recommendations = $lowAttendanceStudents->groupBy('risk_level')->map(function ($students, $riskLevel) {
            return [
                'risk_level' => $riskLevel,
                'student_count' => $students->count(),
                'interventions' => $this->getInterventionsForRiskLevel($riskLevel),
                'priority' => $this->getPriorityForRiskLevel($riskLevel),
                'timeline' => $this->getTimelineForRiskLevel($riskLevel)
            ];
        });
        
        Cache::put('intervention_recommendations', $recommendations, now()->addDays(1));
        event(new AnalyticsEvent('interventions_recommended', $recommendations->toArray()));
    }

    /**
     * Helper methods for predictive analytics
     */
    private function calculateRiskScore($cache): float
    {
        $score = 0;
        
        // Attendance percentage factor
        if ($cache->attendance_percentage < 50) $score += 40;
        elseif ($cache->attendance_percentage < 65) $score += 30;
        elseif ($cache->attendance_percentage < 75) $score += 20;
        elseif ($cache->attendance_percentage < 85) $score += 10;
        
        // Consecutive absents factor
        if ($cache->consecutive_absents >= 7) $score += 25;
        elseif ($cache->consecutive_absents >= 5) $score += 20;
        elseif ($cache->consecutive_absents >= 3) $score += 15;
        elseif ($cache->consecutive_absents >= 2) $score += 10;
        
        // Trend direction factor
        if ($cache->trend_direction === 'declining') $score += 20;
        elseif ($cache->trend_direction === 'stable' && $cache->attendance_percentage < 75) $score += 10;
        elseif ($cache->trend_direction === 'improving') $score -= 5;
        
        // Recent pattern factor
        $recentAbsents = collect($cache->monthly_data ?? [])->last()['total'] ?? 0 - 
                        collect($cache->monthly_data ?? [])->last()['present'] ?? 0;
        if ($recentAbsents >= 5) $score += 15;
        elseif ($recentAbsents >= 3) $score += 10;
        
        return min(100, max(0, $score));
    }

    private function predictOutcome(float $riskScore): string
    {
        if ($riskScore >= 80) return 'high_dropout_risk';
        if ($riskScore >= 60) return 'academic_warning_risk';
        if ($riskScore >= 40) return 'intervention_needed';
        if ($riskScore >= 20) return 'monitor_closely';
        return 'low_risk';
    }

    private function getRecommendedActions(float $riskScore): array
    {
        if ($riskScore >= 80) {
            return [
                'immediate_parent_meeting',
                'counselor_intervention',
                'academic_support_plan',
                'daily_check_ins'
            ];
        }
        
        if ($riskScore >= 60) {
            return [
                'parent_notification',
                'teacher_consultation',
                'study_group_assignment',
                'weekly_progress_review'
            ];
        }
        
        if ($riskScore >= 40) {
            return [
                'attendance_reminder',
                'peer_buddy_system',
                'schedule_optimization'
            ];
        }
        
        return ['routine_monitoring'];
    }

    private function calculateTrendForecast(array $historicalData): array
    {
        // Simple linear regression for trend forecasting
        $trends = $historicalData['chart_data'] ?? [];
        
        if (count($trends) < 3) {
            return ['forecast' => 'insufficient_data'];
        }
        
        // Calculate slope of recent trend
        $recentTrends = array_slice($trends, -7); // Last 7 data points
        $slope = $this->calculateSlope($recentTrends);
        
        // Project next 30 days
        $lastValue = end($recentTrends)['percentage'] ?? 0;
        $forecast = [];
        
        for ($i = 1; $i <= 30; $i++) {
            $predictedValue = $lastValue + ($slope * $i);
            $forecast[] = [
                'day' => $i,
                'predicted_percentage' => round(max(0, min(100, $predictedValue)), 2)
            ];
        }
        
        return [
            'forecast' => $forecast,
            'trend_direction' => $slope > 0 ? 'improving' : ($slope < 0 ? 'declining' : 'stable'),
            'confidence' => $this->calculateConfidence($recentTrends)
        ];
    }

    private function calculateSlope(array $data): float
    {
        $n = count($data);
        if ($n < 2) return 0;
        
        $sumX = $sumY = $sumXY = $sumX2 = 0;
        
        foreach ($data as $i => $point) {
            $x = $i + 1;
            $y = $point['percentage'] ?? 0;
            
            $sumX += $x;
            $sumY += $y;
            $sumXY += $x * $y;
            $sumX2 += $x * $x;
        }
        
        $denominator = ($n * $sumX2) - ($sumX * $sumX);
        
        if ($denominator == 0) return 0;
        
        return (($n * $sumXY) - ($sumX * $sumY)) / $denominator;
    }

    private function calculateConfidence(array $data): float
    {
        // Simple confidence calculation based on data variance
        $values = array_column($data, 'percentage');
        $mean = array_sum($values) / count($values);
        $variance = array_sum(array_map(fn($x) => pow($x - $mean, 2), $values)) / count($values);
        
        // Lower variance = higher confidence
        $confidence = max(0, min(100, 100 - ($variance / 10)));
        
        return round($confidence, 2);
    }

    private function getInterventionsForRiskLevel(string $riskLevel): array
    {
        return match($riskLevel) {
            'critical' => [
                'immediate_intervention',
                'daily_monitoring',
                'parent_conference',
                'academic_probation',
                'counseling_referral'
            ],
            'high' => [
                'weekly_check_ins',
                'mentor_assignment',
                'study_skills_workshop',
                'parent_notification'
            ],
            'medium' => [
                'attendance_tracking',
                'peer_support_group',
                'schedule_review'
            ],
            default => ['routine_monitoring']
        };
    }

    private function getPriorityForRiskLevel(string $riskLevel): string
    {
        return match($riskLevel) {
            'critical' => 'urgent',
            'high' => 'high',
            'medium' => 'normal',
            default => 'low'
        };
    }

    private function getTimelineForRiskLevel(string $riskLevel): string
    {
        return match($riskLevel) {
            'critical' => 'immediate',
            'high' => 'within_week',
            'medium' => 'within_month',
            default => 'routine'
        };
    }
}