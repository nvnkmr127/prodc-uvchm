<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Widget;

class AdvancedWidgetSeeder extends Seeder
{
    public function run(): void
    {
        $widgets = [
            [
                'name' => 'Advanced Chart Widget',
                'type' => 'advanced_chart',
                'component' => 'AdvancedChartWidget',
                'view_path' => 'widgets.advanced-chart', // Add view_path
                'category' => 'analytics',
                'icon' => 'ChartLineIcon',
                'description' => 'Advanced Chart.js visualizations with multiple chart types',
                'default_config' => [
                    'chartType' => 'line',
                    'title' => 'Advanced Chart',
                    'showLegend' => true,
                    'showGridLines' => true,
                    'showStats' => true,
                    'smooth' => true,
                    'fillArea' => false
                ],
                'data_source' => 'App\\Services\\DataProviders\\ChartDataService',
                'default_width' => 6,
                'default_height' => 4,
                'is_resizable' => true,
                'is_active' => true
            ],
            [
                'name' => 'Calendar Widget',
                'type' => 'calendar',
                'component' => 'CalendarWidget',
                'view_path' => 'widgets.calendar',
                'category' => 'academic',
                'icon' => 'CalendarIcon',
                'description' => 'Academic calendar with events and scheduling',
                'default_config' => [
                    'title' => 'Academic Calendar',
                    'view' => 'month',
                    'showWeekends' => true
                ],
                'data_source' => 'App\\Services\\DataProviders\\CalendarDataService',
                'default_width' => 8,
                'default_height' => 6,
                'is_resizable' => true,
                'is_active' => true
            ],
            [
                'name' => 'Campus Map Widget',
                'type' => 'map',
                'component' => 'MapWidget',
                'view_path' => 'widgets.map',
                'category' => 'system',
                'icon' => 'MapIcon',
                'description' => 'Interactive campus map with locations',
                'default_config' => [
                    'title' => 'Campus Map',
                    'defaultView' => 'street',
                    'showControls' => true
                ],
                'data_source' => 'App\\Services\\DataProviders\\MapDataService',
                'default_width' => 6,
                'default_height' => 5,
                'is_resizable' => true,
                'is_active' => true
            ],
            [
                'name' => 'File Manager Widget',
                'type' => 'files',
                'component' => 'FileManagerWidget',
                'view_path' => 'widgets.file-manager',
                'category' => 'system',
                'icon' => 'FolderIcon',
                'description' => 'File and document management system',
                'default_config' => [
                    'title' => 'File Manager',
                    'allowUpload' => true,
                    'maxFileSize' => '10MB'
                ],
                'data_source' => 'App\\Services\\DataProviders\\FileDataService',
                'default_width' => 8,
                'default_height' => 6,
                'is_resizable' => true,
                'is_active' => true
            ]
        ];

        foreach ($widgets as $widget) {
            // Check if widget already exists
            $existingWidget = Widget::where('type', $widget['type'])->first();
            
            if (!$existingWidget) {
                Widget::create($widget);
                echo "Created widget: " . $widget['name'] . "\n";
            } else {
                echo "Widget already exists: " . $widget['name'] . "\n";
            }
        }
    }
}