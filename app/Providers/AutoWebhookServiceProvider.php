<?php

// App/Providers/AutoWebhookServiceProvider.php

namespace App\Providers;

use App\Listeners\UniversalWebhookListener;
use App\Services\WebhookEventDiscoveryService;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\File;
use Illuminate\Support\ServiceProvider;

class AutoWebhookServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(WebhookEventDiscoveryService::class);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Auto-register webhook listeners for all events
        $this->autoRegisterWebhookListeners();

        // Register console commands
        if ($this->app->runningInConsole()) {
            $this->commands([
                \App\Console\Commands\SyncWebhookEvents::class,
                \App\Console\Commands\DiscoverEvents::class,
            ]);
        }
    }

    /**
     * Automatically register webhook listeners for all events
     */
    protected function autoRegisterWebhookListeners(): void
    {
        // This provider will no longer register listeners automatically.
        // All event listening will be explicitly defined in EventServiceProvider.
    }

    /**
     * Register listeners for all events in App/Events directory
     */
    protected function registerEventDirectoryListeners(): void
    {
        $eventsPath = app_path('Events');

        if (! File::exists($eventsPath)) {
            return;
        }

        $files = File::allFiles($eventsPath);

        foreach ($files as $file) {
            $className = 'App\\Events\\'.str_replace(['/', '.php'], ['\\', ''], $file->getRelativePathname());

            if (class_exists($className)) {
                Event::listen($className, UniversalWebhookListener::class);
            }
        }
    }

    /**
     * Register listeners for Eloquent model events
     */
    protected function registerEloquentEventListeners(): void
    {
        $modelsPath = app_path('Models');

        if (! File::exists($modelsPath)) {
            return;
        }

        $files = File::allFiles($modelsPath);
        $eloquentEvents = ['creating', 'created', 'updating', 'updated', 'deleting', 'deleted', 'saving', 'saved'];

        foreach ($files as $file) {
            $className = 'App\\Models\\'.str_replace(['/', '.php'], ['\\', ''], $file->getRelativePathname());

            if (class_exists($className)) {
                $modelName = class_basename($className);

                foreach ($eloquentEvents as $eventType) {
                    $eventName = "eloquent.{$eventType}: {$className}";
                    Event::listen($eventName, function ($event, $models) use ($eventType, $modelName) {
                        $model = $models[0] ?? null;
                        if ($model) {
                            // Create a synthetic event object for the webhook listener
                            $syntheticEvent = new \App\Events\EloquentWebhookEvent($model, $eventType, $modelName);
                            (new UniversalWebhookListener)->handle($syntheticEvent);
                        }
                    });
                }
            }
        }
    }
}
