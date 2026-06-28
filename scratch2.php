<?php
function rglob($pattern, $flags = 0) {
    $files = glob($pattern, $flags);
    foreach (glob(dirname($pattern).'/*', GLOB_ONLYDIR|GLOB_NOSORT) as $dir) {
        $files = array_merge($files, rglob($dir.'/'.basename($pattern), $flags));
    }
    return $files;
}

$controllers = rglob('app/Http/Controllers/*.php');
foreach ($controllers as $controller) {
    $controllerName = basename($controller, '.php');
    $cmd = "grep -ri \"{$controllerName}\" routes/ 2>/dev/null";
    $output = shell_exec($cmd);
    if (empty(trim($output))) {
        echo "Potential unused controller: {$controllerName} ({$controller})\n";
    }
}
