<?php

$path = '/Users/naveenadicharla/Documents/test/prodc-uvchm/app/Http/Controllers/Admin/FacultyController.php';
$content = file_get_contents($path);

$content = str_replace(
    'public function destroy(User $faculty)',
    'public function destroy(User $faculty)
    {
        \Log::info("DESTROY HIT for faculty ID: " . $faculty->id);',
    $content
);

// We need to fix the duplicate `{` that was just introduced
$content = preg_replace(
    '/public function destroy\(User \$faculty\)\s*\{\s*\\\\Log::info\("DESTROY HIT for faculty ID: " \. \$faculty->id\);\s*\{/s',
    'public function destroy(User $faculty)
    {
        \Log::info("DESTROY HIT for faculty ID: " . $faculty->id);',
    $content
);


file_put_contents($path, $content);
echo "Patched destroy\n";
