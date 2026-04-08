<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class AddWebhooksToAllModels extends Command
{
    protected $signature = 'webhooks:add-to-models 
                          {--force : Overwrite existing webhook implementations}
                          {--dry-run : Show what would be changed without making changes}
                          {--exclude=* : Models to exclude from webhook addition}
                          {--models=* : Specific models to process (if empty, processes all)}';

    protected $description = 'Automatically add WebhookEnabled trait to all Eloquent models';

    public function handle(): int
    {
        $this->info('🔍 Scanning for Eloquent models...');

        $modelsPath = app_path('Models');
        if (! File::exists($modelsPath)) {
            $this->error('Models directory not found at: '.$modelsPath);

            return self::FAILURE;
        }

        // Check if WebhookEnabled trait exists
        $traitPath = app_path('Traits/WebhookEnabled.php');
        if (! File::exists($traitPath)) {
            $this->error('WebhookEnabled trait not found at: '.$traitPath);
            $this->info('Please create the WebhookEnabled trait first.');

            return self::FAILURE;
        }

        $models = $this->discoverModels($modelsPath);
        $excludedModels = $this->option('exclude');
        $specificModels = $this->option('models');

        // Filter by excluded models
        if (! empty($excludedModels)) {
            $models = array_filter($models, function ($model) use ($excludedModels) {
                return ! in_array($model['name'], $excludedModels);
            });
        }

        // Filter by specific models if provided
        if (! empty($specificModels)) {
            $models = array_filter($models, function ($model) use ($specificModels) {
                return in_array($model['name'], $specificModels);
            });
        }

        if (empty($models)) {
            $this->warn('No models found to process.');

            return self::SUCCESS;
        }

        $this->info('📋 Found '.count($models).' models to process:');
        $this->table(['Model', 'File Path', 'Has Webhooks'], array_map(function ($model) {
            return [
                $model['name'],
                $model['relative_path'],
                $model['has_webhooks'] ? '✅ Yes' : '❌ No',
            ];
        }, $models));

        if ($this->option('dry-run')) {
            $this->warn('🔍 DRY RUN MODE - No files will be modified');
            $this->showWhatWouldChange($models);

            return self::SUCCESS;
        }

        if (! $this->confirm('Do you want to add WebhookEnabled trait to these models?')) {
            $this->info('Operation cancelled.');

            return self::SUCCESS;
        }

        return $this->processModels($models);
    }

    protected function discoverModels(string $modelsPath): array
    {
        $models = [];
        $files = File::allFiles($modelsPath);

        foreach ($files as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            $relativePath = $file->getRelativePathname();
            $className = 'App\\Models\\'.str_replace(['/', '.php'], ['\\', ''], $relativePath);
            $modelName = str_replace('.php', '', $file->getFilename());

            // Skip if not a valid class
            if (! class_exists($className)) {
                continue;
            }

            try {
                $reflection = new \ReflectionClass($className);

                // Skip abstract classes and interfaces
                if ($reflection->isAbstract() || $reflection->isInterface()) {
                    continue;
                }

                // Check if it extends Model
                if (! $reflection->isSubclassOf(\Illuminate\Database\Eloquent\Model::class)) {
                    continue;
                }

                $fileContent = File::get($file->getPathname());
                $hasWebhooks = $this->hasWebhookTrait($fileContent);

                $models[] = [
                    'name' => $modelName,
                    'class' => $className,
                    'file_path' => $file->getPathname(),
                    'relative_path' => $relativePath,
                    'content' => $fileContent,
                    'has_webhooks' => $hasWebhooks,
                    'reflection' => $reflection,
                ];

            } catch (\Exception $e) {
                $this->warn("Skipped {$modelName}: ".$e->getMessage());

                continue;
            }
        }

        return $models;
    }

    protected function hasWebhookTrait(string $content): bool
    {
        return Str::contains($content, 'use App\Traits\WebhookEnabled') ||
               Str::contains($content, 'use WebhookEnabled') ||
               preg_match('/use\s+.*WebhookEnabled/', $content);
    }

    protected function showWhatWouldChange(array $models): void
    {
        $toUpdate = array_filter($models, fn ($model) => ! $model['has_webhooks']);
        $alreadyHave = array_filter($models, fn ($model) => $model['has_webhooks']);

        if (! empty($toUpdate)) {
            $this->info('➕ Models that would get WebhookEnabled trait:');
            foreach ($toUpdate as $model) {
                $this->line("   • {$model['name']} ({$model['relative_path']})");
            }
            $this->newLine();
        }

        if (! empty($alreadyHave)) {
            $this->info('✅ Models that already have webhooks:');
            foreach ($alreadyHave as $model) {
                $this->line("   • {$model['name']} ({$model['relative_path']})");
            }
            $this->newLine();
        }

        if (empty($toUpdate)) {
            $this->info('🎉 All models already have webhook functionality!');
        }
    }

    protected function processModels(array $models): int
    {
        $updated = 0;
        $skipped = 0;
        $errors = 0;

        foreach ($models as $model) {
            if ($model['has_webhooks'] && ! $this->option('force')) {
                $this->line("⏭️  Skipped {$model['name']} (already has webhooks)");
                $skipped++;

                continue;
            }

            try {
                if ($this->addWebhookToModel($model)) {
                    $this->info("✅ Updated {$model['name']}");
                    $updated++;
                } else {
                    $this->warn("⚠️  Could not update {$model['name']}");
                    $errors++;
                }
            } catch (\Exception $e) {
                $this->error("❌ Error updating {$model['name']}: ".$e->getMessage());
                $errors++;
            }
        }

        $this->newLine();
        $this->info('📊 Summary:');
        $this->info("   ✅ Updated: {$updated}");
        $this->info("   ⏭️  Skipped: {$skipped}");
        $this->info("   ❌ Errors: {$errors}");

        if ($updated > 0) {
            $this->newLine();
            $this->info("🎉 Successfully added webhook functionality to {$updated} models!");
            $this->info("💡 Run 'composer dump-autoload' to ensure all changes are loaded.");
        }

        return $errors > 0 ? self::FAILURE : self::SUCCESS;
    }

    protected function addWebhookToModel(array $model): bool
    {
        $content = $model['content'];
        $className = $model['name'];

        // Check if we need to add the import
        $needsImport = ! Str::contains($content, 'use App\Traits\WebhookEnabled');

        // Check if we need to add the trait usage
        $needsTraitUsage = ! preg_match('/use\s+.*WebhookEnabled/', $content);

        if (! $needsImport && ! $needsTraitUsage) {
            return true; // Already has everything
        }

        $lines = explode("\n", $content);
        $newLines = [];
        $importAdded = false;
        $traitAdded = false;
        $inClass = false;
        $classLineFound = false;

        foreach ($lines as $i => $line) {
            $trimmedLine = trim($line);

            // Add import after the last use statement
            if ($needsImport && ! $importAdded && Str::startsWith($trimmedLine, 'use ') &&
                ! Str::startsWith($trimmedLine, 'use App\Traits\WebhookEnabled')) {

                // Look ahead to see if this is the last use statement
                $isLastUse = true;
                for ($j = $i + 1; $j < count($lines); $j++) {
                    $nextTrimmed = trim($lines[$j]);
                    if (Str::startsWith($nextTrimmed, 'use ')) {
                        $isLastUse = false;
                        break;
                    }
                    if (! empty($nextTrimmed) && ! Str::startsWith($nextTrimmed, '//')) {
                        break;
                    }
                }

                $newLines[] = $line;
                if ($isLastUse) {
                    $newLines[] = 'use App\Traits\WebhookEnabled;';
                    $importAdded = true;
                }

                continue;
            }

            // Detect class declaration
            if (preg_match('/^class\s+'.preg_quote($className).'\s/', $trimmedLine)) {
                $inClass = true;
                $classLineFound = true;
                $newLines[] = $line;

                continue;
            }

            // Add trait usage after the opening brace of the class
            if ($needsTraitUsage && ! $traitAdded && $inClass && $trimmedLine === '{') {
                $newLines[] = $line;

                // Look for existing use statements in class
                $hasExistingTraits = false;
                for ($j = $i + 1; $j < count($lines); $j++) {
                    $nextTrimmed = trim($lines[$j]);
                    if (Str::startsWith($nextTrimmed, 'use ') && Str::endsWith($nextTrimmed, ';')) {
                        $hasExistingTraits = true;
                        break;
                    }
                    if (! empty($nextTrimmed) && ! Str::startsWith($nextTrimmed, '//')) {
                        break;
                    }
                }

                if ($hasExistingTraits) {
                    // Add after existing traits
                    $newLines[] = '    use WebhookEnabled;';
                } else {
                    // Add as first line in class
                    $newLines[] = '    use WebhookEnabled;';
                    $newLines[] = '';
                }
                $traitAdded = true;

                continue;
            }

            $newLines[] = $line;
        }

        // If we couldn't add the import (no existing use statements), add it after namespace
        if ($needsImport && ! $importAdded) {
            $newContent = [];
            foreach ($newLines as $line) {
                $newContent[] = $line;
                if (Str::startsWith(trim($line), 'namespace ')) {
                    $newContent[] = '';
                    $newContent[] = 'use App\Traits\WebhookEnabled;';
                    $importAdded = true;
                }
            }
            $newLines = $newContent;
        }

        $newContent = implode("\n", $newLines);

        // Create backup before modifying
        if (! $this->option('dry-run')) {
            $backupPath = $model['file_path'].'.backup.'.date('Y-m-d-H-i-s');
            File::copy($model['file_path'], $backupPath);
            $this->line('   📁 Created backup: '.basename($backupPath));
        }

        // Write new content
        File::put($model['file_path'], $newContent);

        return true;
    }

    /**
     * Get list of available models for autocomplete
     */
    public function getAvailableModels(): array
    {
        $modelsPath = app_path('Models');
        $models = [];

        if (File::exists($modelsPath)) {
            $files = File::allFiles($modelsPath);
            foreach ($files as $file) {
                if ($file->getExtension() === 'php') {
                    $models[] = str_replace('.php', '', $file->getFilename());
                }
            }
        }

        return $models;
    }
}
