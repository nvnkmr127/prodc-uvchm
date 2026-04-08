<?php

// database/seeders/DashboardTemplateSeeder.php

namespace Database\Seeders;

use App\Models\DashboardTemplate;
use Illuminate\Database\Seeder;

class DashboardTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            [
                'name' => 'Executive Dashboard',
                'description' => 'High-level overview with KPIs and revenue charts',
                'category' => 'executive',
                'layout' => [
                    ['type' => 'kpi', 'x' => 0, 'y' => 0, 'w' => 3, 'h' => 2, 'config' => ['title' => 'Total Students']],
                    ['type' => 'kpi', 'x' => 3, 'y' => 0, 'w' => 3, 'h' => 2, 'config' => ['title' => 'Monthly Revenue']],
                    ['type' => 'kpi', 'x' => 6, 'y' => 0, 'w' => 3, 'h' => 2, 'config' => ['title' => 'Fee Collection']],
                    ['type' => 'kpi', 'x' => 9, 'y' => 0, 'w' => 3, 'h' => 2, 'config' => ['title' => 'Active Staff']],
                    ['type' => 'chart', 'x' => 0, 'y' => 2, 'w' => 6, 'h' => 4, 'config' => ['title' => 'Enrollment Trends']],
                    ['type' => 'chart', 'x' => 6, 'y' => 2, 'w' => 6, 'h' => 4, 'config' => ['title' => 'Revenue Analytics']],
                ],
                'is_public' => true,
            ],
            [
                'name' => 'Academic Overview',
                'description' => 'Student-focused dashboard with enrollment and academic data',
                'category' => 'academic',
                'layout' => [
                    ['type' => 'students', 'x' => 0, 'y' => 0, 'w' => 4, 'h' => 3, 'config' => ['title' => 'Student Directory']],
                    ['type' => 'chart', 'x' => 4, 'y' => 0, 'w' => 4, 'h' => 3, 'config' => ['title' => 'Enrollment by Course']],
                    ['type' => 'calendar', 'x' => 8, 'y' => 0, 'w' => 4, 'h' => 3, 'config' => ['title' => 'Academic Calendar']],
                    ['type' => 'table', 'x' => 0, 'y' => 3, 'w' => 12, 'h' => 4, 'config' => ['title' => 'Recent Enrollments']],
                ],
                'is_public' => true,
            ],
            [
                'name' => 'Financial Dashboard',
                'description' => 'Complete financial overview with revenue and fee tracking',
                'category' => 'financial',
                'layout' => [
                    ['type' => 'kpi', 'x' => 0, 'y' => 0, 'w' => 2, 'h' => 2, 'config' => ['title' => 'Total Revenue', 'format' => 'currency']],
                    ['type' => 'kpi', 'x' => 2, 'y' => 0, 'w' => 2, 'h' => 2, 'config' => ['title' => 'Pending Fees', 'format' => 'currency']],
                    ['type' => 'kpi', 'x' => 4, 'y' => 0, 'w' => 2, 'h' => 2, 'config' => ['title' => 'Collection Rate', 'format' => 'percentage']],
                    ['type' => 'revenue', 'x' => 6, 'y' => 0, 'w' => 6, 'h' => 4, 'config' => ['title' => 'Monthly Revenue Trends']],
                    ['type' => 'fees', 'x' => 0, 'y' => 2, 'w' => 6, 'h' => 4, 'config' => ['title' => 'Fee Collection Status']],
                    ['type' => 'table', 'x' => 0, 'y' => 6, 'w' => 12, 'h' => 3, 'config' => ['title' => 'Recent Payments']],
                ],
                'is_public' => true,
            ],
            [
                'name' => 'Analytics Dashboard',
                'description' => 'Data-heavy dashboard with multiple charts and analytics',
                'category' => 'analytics',
                'layout' => [
                    ['type' => 'chart', 'x' => 0, 'y' => 0, 'w' => 6, 'h' => 3, 'config' => ['title' => 'Student Growth', 'chartType' => 'line']],
                    ['type' => 'chart', 'x' => 6, 'y' => 0, 'w' => 6, 'h' => 3, 'config' => ['title' => 'Course Popularity', 'chartType' => 'bar']],
                    ['type' => 'chart', 'x' => 0, 'y' => 3, 'w' => 4, 'h' => 3, 'config' => ['title' => 'Fee Distribution', 'chartType' => 'pie']],
                    ['type' => 'chart', 'x' => 4, 'y' => 3, 'w' => 4, 'h' => 3, 'config' => ['title' => 'Performance Metrics', 'chartType' => 'radar']],
                    ['type' => 'kpi', 'x' => 8, 'y' => 3, 'w' => 4, 'h' => 3, 'config' => ['title' => 'Key Metrics Summary']],
                ],
                'is_public' => true,
            ],
        ];

        foreach ($templates as $template) {
            DashboardTemplate::create($template);
        }
    }
}
