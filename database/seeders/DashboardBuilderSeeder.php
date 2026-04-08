<?php

namespace Database\Seeders;

use App\Models\Widget;
use App\Models\WidgetCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class DashboardBuilderSeeder extends Seeder
{
    public function run(): void
    {
        if (! Schema::hasTable('widget_categories')) {
            $this->command->error('widget_categories table does not exist. Please run migrations first.');

            return;
        }

        if (! Schema::hasTable('widgets')) {
            $this->command->error('widgets table does not exist. Please run migrations first.');

            return;
        }

        $this->seedWidgetCategories();
        $this->seedDefaultWidgets();
    }

    private function seedWidgetCategories(): void
    {
        $categories = [
            [
                'name' => 'Analytics',
                'slug' => 'analytics',
                'description' => 'Data visualization and analytics widgets',
                'icon' => 'ChartBarIcon',
                'color' => '#3B82F6',
                'order' => 1,
            ],
            [
                'name' => 'Academic',
                'slug' => 'academic',
                'description' => 'Student and course management widgets',
                'icon' => 'AcademicCapIcon',
                'color' => '#10B981',
                'order' => 2,
            ],
            [
                'name' => 'Financial',
                'slug' => 'financial',
                'description' => 'Fee and financial management widgets',
                'icon' => 'CurrencyDollarIcon',
                'color' => '#F59E0B',
                'order' => 3,
            ],
            [
                'name' => 'HR',
                'slug' => 'hr',
                'description' => 'Staff and faculty management widgets',
                'icon' => 'UsersIcon',
                'color' => '#8B5CF6',
                'order' => 4,
            ],
            [
                'name' => 'System',
                'slug' => 'system',
                'description' => 'System monitoring and administration widgets',
                'icon' => 'ServerIcon',
                'color' => '#EF4444',
                'order' => 5,
            ],
        ];

        foreach ($categories as $category) {
            WidgetCategory::updateOrCreate(
                ['slug' => $category['slug']],
                $category
            );
        }

        $this->command->info('Widget categories seeded successfully.');
    }

    private function seedDefaultWidgets(): void
    {
        // Check if widgets table has all required columns
        $requiredColumns = [
            'type', 'component', 'category', 'icon', 'description', 'default_config',
            'data_source', 'default_width', 'default_height', 'is_resizable', 'is_active',
        ];

        $existingColumns = Schema::getColumnListing('widgets');
        $missingColumns = array_diff($requiredColumns, $existingColumns);

        if (! empty($missingColumns)) {
            $this->command->error('Some widget columns are missing: '.implode(', ', $missingColumns));
            $this->command->error('Please run the widget enhancement migration first.');

            return;
        }

        $widgets = [
            [
                'name' => 'Student Enrollment Chart',
                'type' => 'enrollment_chart',
                'component' => 'ChartWidget',
                'view_path' => 'dashboard.widgets.chart',
                'category' => 'analytics',
                'icon' => 'ChartBarIcon',
                'description' => 'Shows student enrollment trends over time',
                'default_config' => [
                    'chartType' => 'line',
                    'title' => 'Student Enrollment Trends',
                    'showLegend' => true,
                    'colors' => ['#3B82F6', '#10B981'],
                ],
                'data_source' => 'App\\Services\\DataProviders\\StudentEnrollmentDataService',
                'default_width' => 6,
                'default_height' => 4,
                'is_resizable' => true,
                'is_active' => true,
            ],
            [
                'name' => 'Total Students KPI',
                'type' => 'total_students_kpi',
                'component' => 'KPIWidget',
                'view_path' => 'dashboard.widgets.kpi',
                'category' => 'analytics',
                'icon' => 'UsersIcon',
                'description' => 'Total number of students with trend analysis',
                'default_config' => [
                    'title' => 'Total Students',
                    'format' => 'number',
                    'showTrend' => true,
                    'showSparkline' => true,
                    'variant' => 'default',
                ],
                'data_source' => 'App\\Services\\DataProviders\\KPIDataService',
                'default_width' => 3,
                'default_height' => 2,
                'is_resizable' => true,
                'is_active' => true,
            ],
            [
                'name' => 'New Enrollments This Month',
                'type' => 'new_enrollments_kpi',
                'component' => 'KPIWidget',
                'view_path' => 'dashboard.widgets.kpi',
                'category' => 'academic',
                'icon' => 'AcademicCapIcon',
                'description' => 'New student enrollments for current month',
                'default_config' => [
                    'title' => 'New Enrollments',
                    'subtitle' => 'This Month',
                    'format' => 'number',
                    'showTrend' => true,
                    'variant' => 'success',
                ],
                'data_source' => 'App\\Services\\DataProviders\\KPIDataService',
                'default_width' => 3,
                'default_height' => 2,
                'is_resizable' => true,
                'is_active' => true,
            ],
            [
                'name' => 'Student List',
                'type' => 'student_table',
                'component' => 'DataTableWidget',
                'view_path' => 'dashboard.widgets.table',
                'category' => 'academic',
                'icon' => 'TableCellsIcon',
                'description' => 'Searchable and filterable student directory',
                'default_config' => [
                    'title' => 'Students',
                    'pageSize' => 10,
                    'searchable' => true,
                    'exportable' => true,
                    'columns' => [
                        ['key' => 'name', 'label' => 'Name', 'sortable' => true],
                        ['key' => 'email', 'label' => 'Email', 'sortable' => true],
                        ['key' => 'status', 'label' => 'Status', 'sortable' => true],
                        ['key' => 'created_at', 'label' => 'Enrolled', 'type' => 'date', 'sortable' => true],
                    ],
                ],
                'data_source' => 'App\\Services\\DataProviders\\StudentDataService',
                'default_width' => 8,
                'default_height' => 6,
                'is_resizable' => true,
                'is_active' => true,
            ],
        ];

        foreach ($widgets as $widgetData) {
            try {
                Widget::updateOrCreate(
                    ['type' => $widgetData['type']],
                    $widgetData
                );
                $this->command->info("Widget '{$widgetData['name']}' created/updated successfully.");
            } catch (\Exception $e) {
                $this->command->error("Failed to create widget '{$widgetData['name']}': ".$e->getMessage());
            }
        }

        $this->command->info('Default widgets seeded successfully.');
    }
}
