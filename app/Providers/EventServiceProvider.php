<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     */
    protected $listen = [
        // Register the universal webhook listener for Eloquent events
        'App\Events\EloquentWebhookEvent' => [
            'App\Listeners\UniversalWebhookListener',
        ],

        // If you have a specific ReceiptGenerated event
        'App\Events\ReceiptGenerated' => [
            'App\Listeners\UniversalWebhookListener',
        ],

        // Add other event listeners here
    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        parent::boot();

        // You can also register listeners here if needed
        // This is useful for wildcard event listening

        Event::listen('eloquent.*', function ($eventName, array $data) {
            // This will catch all Eloquent model events
            // But be careful not to create infinite loops

            if (str_contains($eventName, 'payment') || str_contains($eventName, 'invoice')) {
                \Log::debug('Eloquent event detected', [
                    'event' => $eventName,
                    'data_count' => count($data),
                ]);
            }
        });
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
