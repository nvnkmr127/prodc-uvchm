<?php
$models = glob('app/Models/*.php');
foreach ($models as $model) {
    $modelName = basename($model, '.php');
    $cmd = "grep -ri \"{$modelName}\" app/ routes/ resources/views/ 2>/dev/null | grep -v \"app/Models/{$modelName}.php\"";
    $output = shell_exec($cmd);
    if (empty(trim($output))) {
        echo "Potential unused model: {$modelName}\n";
    }
}
