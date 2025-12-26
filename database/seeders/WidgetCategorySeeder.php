<?php
// database/seeders/WidgetCategorySeeder.php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\WidgetCategory;

class WidgetCategorySeeder extends Seeder
{
    public function run()
    {
        $categories = [
            [
                'name' => 'Analytics & Charts',
                'slug' => 'analytics-charts',
                'icon' => 'fas fa-chart-line',
                'description' => 'Data visualization and analytics widgets',
                'order' => 1
            ],
            [
                'name' => 'Academic',
                'slug' => 'academic',
                'icon' => 'fas fa-graduation-cap',
                'description' => 'Academic and educational widgets',
                'order' => 2
            ],
            [
                'name' => 'Financial',
                'slug' => 'financial',
                'icon' => 'fas fa-rupee-sign',
                'description' => 'Financial and accounting widgets',
                'order' => 3
            ],
            [
                'name' => 'User Management',
                'slug' => 'user-management',
                'icon' => 'fas fa-users',
                'description' => 'User and staff management widgets',
                'order' => 4
            ],
            [
                'name' => 'Quick Actions',
                'slug' => 'quick-actions',
                'icon' => 'fas fa-bolt',
                'description' => 'Quick action and shortcut widgets',
                'order' => 5
            ],
            [
                'name' => 'System',
                'slug' => 'system',
                'icon' => 'fas fa-cogs',
                'description' => 'System monitoring and administration widgets',
                'order' => 6
            ]
        ];

        foreach ($categories as $category) {
            WidgetCategory::updateOrCreate(
                ['slug' => $category['slug']],
                $category
            );
        }
    }
}