<?php

namespace App\Console\Commands;

use App\Services\WebhookEventDiscoveryService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class DiscoverEvents extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'events:discover 
                          {--json : Output results as JSON}
                          {--category= : Filter by specific category}
                          {--search= : Search events by name or description}
                          {--type= : Filter by event type (eloquent, custom, annotation)}
                          {--export= : Export results to file}
                          {--stats : Show detailed statistics only}
                          {--validate : Validate discovered events}';

    /**
     * The console command description.
     */
    protected $description = 'Discover and analyze all events in the application';

    /**
     * The event discovery service
     */
    protected WebhookEventDiscoveryService $eventDiscovery;

    /**
     * Create a new command instance
     */
    public function __construct(WebhookEventDiscoveryService $eventDiscovery)
    {
        parent::__construct();
        $this->eventDiscovery = $eventDiscovery;
    }

    /**
     * Execute the console command
     */
    public function handle(): int
    {
        $this->info('🔍 Discovering events in your application...');
        $this->newLine();

        // Discover all events
        $discovered = $this->eventDiscovery->discoverAllEvents();

        // Apply filters
        $filtered = $this->applyFilters($discovered);

        // Handle different output modes
        if ($this->option('json')) {
            $this->outputJson($filtered);

            return self::SUCCESS;
        }

        if ($this->option('stats')) {
            $this->showStatisticsOnly($filtered);

            return self::SUCCESS;
        }

        if ($this->option('validate')) {
            $this->validateEvents($filtered);

            return self::SUCCESS;
        }

        // Default: show formatted results
        $this->displayFormattedResults($filtered);

        // Handle export option
        if ($this->option('export')) {
            $this->exportResults($filtered);
        }

        return self::SUCCESS;
    }

    /**
     * Apply filters based on command options
     */
    protected function applyFilters(array $discovered): array
    {
        $filtered = $discovered;

        // Filter by category
        if ($categoryFilter = $this->option('category')) {
            $filtered['categories'] = array_filter(
                $filtered['categories'],
                fn ($name) => Str::contains(strtolower($name), strtolower($categoryFilter)),
                ARRAY_FILTER_USE_KEY
            );

            // Recalculate events
            $filtered['events'] = [];
            foreach ($filtered['categories'] as $categoryData) {
                $filtered['events'] = array_merge($filtered['events'], $categoryData['events']);
            }
            $filtered['total_count'] = count($filtered['events']);
        }

        // Filter by search term
        if ($searchTerm = $this->option('search')) {
            $filtered['events'] = array_filter(
                $filtered['events'],
                function ($eventData, $eventKey) use ($searchTerm) {
                    return Str::contains(strtolower($eventKey), strtolower($searchTerm)) ||
                        Str::contains(strtolower($eventData['name'] ?? ''), strtolower($searchTerm)) ||
                        Str::contains(strtolower($eventData['description'] ?? ''), strtolower($searchTerm));
                },
                ARRAY_FILTER_USE_BOTH
            );
            $filtered['total_count'] = count($filtered['events']);
        }

        // Filter by type
        if ($typeFilter = $this->option('type')) {
            $filtered['events'] = array_filter(
                $filtered['events'],
                fn ($eventData) => ($eventData['type'] ?? '') === $typeFilter
            );
            $filtered['total_count'] = count($filtered['events']);
        }

        return $filtered;
    }

    /**
     * Output results as JSON
     */
    protected function outputJson(array $data): void
    {
        $this->line(json_encode($data, JSON_PRETTY_PRINT));
    }

    /**
     * Show only statistics
     */
    protected function showStatisticsOnly(array $data): void
    {
        $this->info('📊 Event Discovery Statistics:');
        $this->table([
            'Metric',
            'Value',
        ], [
            ['Total Events', $data['total_count']],
            ['Categories', count($data['categories'])],
            ['Auto-discovered', count(array_filter($data['events'], fn ($e) => $e['auto_discovered'] ?? false))],
            ['Manual Events', count(array_filter($data['events'], fn ($e) => ! ($e['auto_discovered'] ?? false)))],
        ]);
    }

    /**
     * Validate discovered events
     */
    protected function validateEvents(array $data): void
    {
        $this->info('🔍 Validating discovered events...');

        $errors = [];
        $warnings = [];

        foreach ($data['events'] as $eventKey => $eventData) {
            // Check for missing descriptions
            if (empty($eventData['description'])) {
                $warnings[] = "Event '{$eventKey}' has no description";
            }

            // Check for valid event names
            if (! preg_match('/^[a-z0-9._-]+$/', $eventKey)) {
                $errors[] = "Event '{$eventKey}' has invalid name format";
            }

            // Check for missing categories
            if (empty($eventData['category']['name'])) {
                $warnings[] = "Event '{$eventKey}' has no category";
            }
        }

        if (empty($errors) && empty($warnings)) {
            $this->info('✅ All events passed validation!');
        } else {
            if (! empty($errors)) {
                $this->error('❌ Validation errors found:');
                foreach ($errors as $error) {
                    $this->line("   • {$error}");
                }
            }

            if (! empty($warnings)) {
                $this->warn('⚠️  Validation warnings:');
                foreach ($warnings as $warning) {
                    $this->line("   • {$warning}");
                }
            }
        }
    }

    /**
     * Display formatted results
     */
    protected function displayFormattedResults(array $discovered): void
    {
        $this->info('📊 Discovery Results:');
        $this->info("   Total Events: {$discovered['total_count']}");
        $this->info('   Categories: '.count($discovered['categories']));
        $this->newLine();

        $categoryFilter = $this->option('category');

        foreach ($discovered['categories'] as $categoryName => $categoryData) {
            if ($categoryFilter && ! Str::contains(strtolower($categoryName), strtolower($categoryFilter))) {
                continue;
            }

            $emoji = $categoryData['emoji'] ?? '📋';
            $this->info("{$emoji} {$categoryName} (".count($categoryData['events']).' events)');

            foreach ($categoryData['events'] as $eventKey => $eventData) {
                $autoFlag = ($eventData['auto_discovered'] ?? false) ? '🤖' : '👤';
                $this->line("   {$autoFlag} {$eventKey}");

                if (! empty($eventData['description'])) {
                    $this->line("      📝 {$eventData['description']}");
                }
            }
            $this->newLine();
        }
    }

    /**
     * Export results to file
     */
    protected function exportResults(array $data): void
    {
        $filename = $this->option('export');
        $path = storage_path("exports/{$filename}");

        // Ensure directory exists
        if (! File::exists(dirname($path))) {
            File::makeDirectory(dirname($path), 0755, true);
        }

        File::put($path, json_encode($data, JSON_PRETTY_PRINT));

        $this->info("📁 Results exported to: {$path}");
    }
}
