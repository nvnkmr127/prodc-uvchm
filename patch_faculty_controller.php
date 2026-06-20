<?php

$path = '/Users/naveenadicharla/Documents/test/prodc-uvchm/app/Http/Controllers/Admin/FacultyController.php';
$content = file_get_contents($path);

// Update create method
$content = preg_replace(
    '/public function create\(\)\s*\{\s*return view\(\'admin\.faculty\.create\'\);\s*\}/s',
    'public function create()
    {
        $departments = \App\Models\User::role(\'staff\')->whereNotNull(\'department\')->pluck(\'department\')->unique()->sort();
        return view(\'admin.faculty.create\', compact(\'departments\'));
    }',
    $content
);

// Update edit method
$content = preg_replace(
    '/public function edit\(User \$faculty\)\s*\{\s*if \(! \$faculty->hasRole\(\'staff\'\)\) \{\s*abort\(404, \'Faculty member not found\'\);\s*\}\s*return view\(\'admin\.faculty\.edit\', compact\(\'faculty\'\)\);\s*\}/s',
    'public function edit(User $faculty)
    {
        if (! $faculty->hasRole(\'staff\')) {
            abort(404, \'Faculty member not found\');
        }

        $departments = \App\Models\User::role(\'staff\')->whereNotNull(\'department\')->pluck(\'department\')->unique()->sort();
        return view(\'admin.faculty.edit\', compact(\'faculty\', \'departments\'));
    }',
    $content
);

file_put_contents($path, $content);
echo "Patched FacultyController.php\n";
