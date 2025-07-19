<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Widget;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class WidgetSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Path to the directory where your widget blade files are stored
        $widgetPath = resource_path('views/admin/dashboard/widgets');
        
        if (!File::isDirectory($widgetPath)) {
            File::makeDirectory($widgetPath, 0755, true);
        }

        $widgetFiles = File::files($widgetPath);

        foreach ($widgetFiles as $file) {
            // e.g., 'total_students_card.blade.php'
            $fileName = $file->getFilenameWithoutExtension(); 
            // e.g., 'admin.dashboard.widgets.total_students_card'
            $viewPath = 'admin.dashboard.widgets.' . $fileName; 
            // e.g., "Total Students Card"
            $widgetName = Str::title(str_replace('_', ' ', $fileName));

            // Use updateOrCreate to add new widgets or update existing ones without creating duplicates
            Widget::updateOrCreate(
                ['view_path' => $viewPath],
                [
                    'name' => $widgetName,
                    'description' => "Displays the {$widgetName}.",
                ]
            );
        }
    }
}
