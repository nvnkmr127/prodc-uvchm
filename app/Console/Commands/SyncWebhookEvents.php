<?php

namespace App\Console\Commands;

use App\Services\WebhookEventDiscoveryService;
use App\Models\Webhook;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SyncWebhookEvents extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'webhooks:sync 
                          {--force : Force sync even if no changes detected}
                          {--dry-run : Show what would be synced without making changes}
                          {--clean : Remove obsolete event references}
                          {--stats : Show detailed statistics}';

    /**
     * The console command description.
     */
    protected $description = 'Sync discovered events with the webhook system';

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
        $this->info('🔍 Starting webhook event synchronization...');
        $this->newLine();

        // Show current stats if requested
        if ($this->option('stats')) {
            $this->showCurrentStats();
        }

        // Discover all events
        $this->info('📡 Discovering events in your application...');
        $discovered = $this->eventDiscovery->discoverAllEvents();

        $this->info("✅ Found {$discovered['total_count']} events across " . count($discovered['categories']) . " categories");
        $this->newLine();

        // Show discovered events summary
        $this->showDiscoveredSummary($discovered);

        // Check for dry run
        if ($this->option('dry-run')) {
            $this->warn('🔍 DRY RUN MODE - No changes will be made');
            $this->showWhatWouldBeChanged($discovered);
            return self::SUCCESS;
        }

        // Sync with webhook system
        $this->info('🔄 Syncing with webhook system...');
        $result = $this->eventDiscovery->syncWithWebhookSystem();

        // Show sync results
        $this->showSyncResults($result);

        // Clean obsolete references if requested
        if ($this->option('clean')) {
            $this->cleanObsoleteReferences($discovered['events']);
        }

        // Clear caches
        $this->clearEventCaches();

        // Show final statistics
        $this->showFinalStats();

        $this->newLine();
        $this->info('🎉 Webhook event synchronization completed successfully!');

        return self::SUCCESS;
    }

    /**
     * Show current system statistics
     */
    protected function showCurrentStats(): void
    {
        $this->info('📊 Current System Statistics:');

        try {
            $totalWebhooks = Webhook::count();
            $activeWebhooks = Webhook::where('is_active', true)->count();
            $failingWebhooks = Webhook::where('consecutive_failures', '>=', 3)->count();

            $this->table([
                'Metric',
                'Value'
            ], [
                ['Total Webhooks', $totalWebhooks],
                ['Active Webhooks', $activeWebhooks],
                ['Failing Webhooks', $failingWebhooks],
            ]);
        } catch (\Exception $e) {
            $this->warn('Could not fetch webhook statistics: ' . $e->getMessage());
        }

        $this->newLine();
    }

    /**
     * Show summary of discovered events
     */
    protected function showDiscoveredSummary(array $discovered): void
    {
        $this->info('📋 Discovered Events by Category:');

        $tableData = [];
        foreach ($discovered['categories'] as $categoryName => $categoryData) {
            $tableData[] = [
                ($categoryData['emoji'] ?? '📋') . ' ' . $categoryName,
                count($categoryData['events']),
                $this->getEventTypes($categoryData['events'])
            ];
        }

        $this->table([
            'Category',
            'Count',
            'Sample Events'
        ], $tableData);

        $this->newLine();
    }

    /**
     * Get sample event types for display
     */
    protected function getEventTypes(array $events): string
    {
        $samples = array_slice(array_keys($events), 0, 3);
        $remaining = count($events) - 3;

        $result = implode(', ', $samples);
        if ($remaining > 0) {
            $result .= " (+ {$remaining} more)";
        }

        return $result;
    }

    /**
     * Show what would be changed in dry run mode
     */
    protected function showWhatWouldBeChanged(array $discovered): void
    {
        $this->info('🔍 Changes that would be made:');

        $previousEvents = cache()->get('webhook_available_events', []);
        $newEvents = array_diff_key($discovered['events'], $previousEvents);
        $removedEvents = array_diff_key($previousEvents, $discovered['events']);
        $updatedEvents = $this->findUpdatedEvents($discovered['events'], $previousEvents);

        if (!empty($newEvents)) {
            $this->info('➕ New Events (' . count($newEvents) . '):');
            foreach ($newEvents as $eventKey => $eventData) {
                $this->line("   • {$eventData['name']} ({$eventKey})");
            }
            $this->newLine();
        }

        if (!empty($removedEvents)) {
            $this->warn('➖ Removed Events (' . count($removedEvents) . '):');
            foreach ($removedEvents as $eventKey => $eventData) {
                $this->line("   • {$eventData['name']} ({$eventKey})");
            }
            $this->newLine();
        }

        if (!empty($updatedEvents)) {
            $this->info('🔄 Updated Events (' . count($updatedEvents) . '):');
            foreach ($updatedEvents as $eventKey => $changes) {
                $this->line("   • {$eventKey}: " . implode(', ', $changes));
            }
            $this->newLine();
        }

        if (empty($newEvents) && empty($removedEvents) && empty($updatedEvents)) {
            $this->info('✨ No changes detected - all events are up to date!');
        }
    }

    /**
     * Find events that have been updated
     */
    protected function findUpdatedEvents(array $currentEvents, array $previousEvents): array
    {
        $updated = [];

        foreach ($currentEvents as $eventKey => $currentData) {
            if (isset($previousEvents[$eventKey])) {
                $previousData = $previousEvents[$eventKey];
                $changes = [];

                if (($currentData['description'] ?? '') !== ($previousData['description'] ?? '')) {
                    $changes[] = 'description';
                }

                if (($currentData['category']['name'] ?? '') !== ($previousData['category']['name'] ?? '')) {
                    $changes[] = 'category';
                }

                if (!empty($changes)) {
                    $updated[$eventKey] = $changes;
                }
            }
        }

        return $updated;
    }

    /**
     * Show synchronization results
     */
    protected function showSyncResults(array $result): void
    {
        $this->info("✅ Synced {$result['synced_events']} events across {$result['categories']} categories");

        if (count($result['new_events']) > 0) {
            $this->info("🆕 Found " . count($result['new_events']) . " new events:");
            foreach ($result['new_events'] as $eventKey => $eventData) {
                $categoryEmoji = $eventData['category']['emoji'] ?? '📋';
                $this->line("   {$categoryEmoji} {$eventData['name']} ({$eventKey})");
                $this->line("      📝 {$eventData['description']}");
            }
            $this->newLine();
        } else {
            $this->info("ℹ️  No new events discovered");
        }
    }

    /**
     * Clean obsolete event references
     */
    protected function cleanObsoleteReferences(array $currentEvents): void
    {
        $this->info('🧹 Cleaning obsolete event references...');

        try {
            // Find webhooks that reference non-existent events
            $obsoleteWebhooks = Webhook::whereNotIn('event_name', array_merge(
                array_keys($currentEvents),
                ['*'] // Keep wildcard webhooks
            ))->get();

            if ($obsoleteWebhooks->count() > 0) {
                $this->warn("Found {$obsoleteWebhooks->count()} webhooks with obsolete event references:");

                foreach ($obsoleteWebhooks as $webhook) {
                    $this->line("   • {$webhook->url} -> {$webhook->event_name}");
                }

                if ($this->confirm('Do you want to disable these webhooks?')) {
                    $obsoleteWebhooks->each(function ($webhook) {
                        $webhook->update(['is_active' => false]);
                    });

                    $this->info("✅ Disabled {$obsoleteWebhooks->count()} obsolete webhooks");
                }
            } else {
                $this->info("✅ No obsolete webhook references found");
            }
        } catch (\Exception $e) {
            $this->warn('Could not clean obsolete references: ' . $e->getMessage());
        }

        $this->newLine();
    }

    /**
     * Clear event-related caches
     */
    protected function clearEventCaches(): void
    {
        $this->info('🧹 Clearing event discovery caches...');

        $cacheKeys = [
            'discovered_events',
            'webhook_available_events',
            'webhook_events_by_category',
            'event_discovery_stats'
        ];

        foreach ($cacheKeys as $key) {
            cache()->forget($key);
        }

        $this->info("✅ Cleared " . count($cacheKeys) . " cache keys");
    }

    /**
     * Show final statistics
     */
    protected function showFinalStats(): void
    {
        try {
            $this->info('📊 Final Statistics:');
            $totalWebhooks = Webhook::count();
            $activeWebhooks = Webhook::where('is_active', true)->count();

            $this->line("   📊 Total Webhooks: {$totalWebhooks}");
            $this->line("   ✅ Active Webhooks: {$activeWebhooks}");
        } catch (\Exception $e) {
            $this->warn('Could not display final statistics: ' . $e->getMessage());
        }
    }
}