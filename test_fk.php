<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$fks = \DB::select("SELECT TABLE_NAME, COLUMN_NAME, DELETE_RULE FROM INFORMATION_SCHEMA.REFERENTIAL_CONSTRAINTS WHERE REFERENCED_TABLE_NAME = 'users'");
foreach($fks as $fk) {
    echo "{$fk->TABLE_NAME}.{$fk->COLUMN_NAME} -> {$fk->DELETE_RULE}\n";
}
