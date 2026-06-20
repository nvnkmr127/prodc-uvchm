<?php

$path = '/Users/naveenadicharla/Documents/test/prodc-uvchm/app/Http/Controllers/Admin/FacultyController.php';
$content = file_get_contents($path);

$content = str_replace(
    '\Log::info("DESTROY HIT for faculty ID: " . $faculty->id);',
    'dd("DESTROY HIT", $faculty->id, $faculty->subjects()->exists(), (method_exists($faculty, \'timetableEntries\') ? $faculty->timetableEntries()->exists() : false));',
    $content
);

file_put_contents($path, $content);
echo "Added dd to destroy\n";
