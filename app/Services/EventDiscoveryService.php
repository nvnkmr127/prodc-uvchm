<?php

// App/Services/EventDiscoveryService.php
namespace App\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use ReflectionClass;
use ReflectionProperty;

class EventDiscoveryService
{
    protected array $eventCategories = [
        'payment' => ['name' => 'Financial Events', 'icon' => 'fas fa-money-bill-wave', 'emoji' => '💰'],
        'invoice' => ['name' => 'Financial Events', 'icon' => 'fas fa-file-invoice', 'emoji' => '💰'],
        'receipt' => ['name' => 'Financial Events', 'icon' => 'fas fa-receipt', 'emoji' => '💰'],
        'fee' => ['name' => 'Financial Events', 'icon' => 'fas fa-bell', 'emoji' => '💰'],
        
        'student' => ['name' => 'Student Management', 'icon' => 'fas fa-user-graduate', 'emoji' => '👨‍🎓'],
        'admission' => ['name' => 'Student Management', 'icon' => 'fas fa-user-plus', 'emoji' => '👨‍🎓'],
        'certificate' => ['name' => 'Student Management', 'icon' => 'fas fa-certificate', 'emoji' => '👨‍🎓'],
        'batch' => ['name' => 'Student Management', 'icon' => 'fas fa-users', 'emoji' => '👨‍🎓'],
        
        'enquiry' => ['name' => 'Lead Management', 'icon' => 'fas fa-question-circle', 'emoji' => '📞'],
        'lead' => ['name' => 'Lead Management', 'icon' => 'fas fa-user-tag', 'emoji' => '📞'],
        'visitor' => ['name' => 'Lead Management', 'icon' => 'fas fa-walking', 'emoji' => '📞'],
        
        'attendance' => ['name' => 'Academic Events', 'icon' => 'fas fa-calendar-check', 'emoji' => '📚'],
        'timetable' => ['name' => 'Academic Events', 'icon' => 'fas fa-calendar-alt', 'emoji' => '📚'],
        'exam' => ['name' => 'Academic Events', 'icon' => 'fas fa-file-alt', 'emoji' => '📚'],
        'grade' => ['name' => 'Academic Events', 'icon' => 'fas fa-award', 'emoji' => '📚'],
        
        'leave' => ['name' => 'HR Management', 'icon' => 'fas fa-calendar-minus', 'emoji' => '👥'],
        'payroll' => ['name' => 'HR Management', 'icon' => 'fas fa-money-check', 'emoji' => '👥'],
        'staff' => ['name' => 'HR Management', 'icon' => 'fas fa-user-tie', 'emoji' => '👥'],
        'faculty' => ['name' => 'HR Management', 'icon' => 'fas fa-chalkboard-teacher', 'emoji' => '👥'],
        
        'asset' => ['name' => 'Inventory Management', 'icon' => 'fas fa-boxes', 'emoji' => '📦'],
        'audit' => ['name' => 'Inventory Management', 'icon' => 'fas fa-clipboard-check', 'emoji' => '📦'],
        'maintenance' => ['name' => 'Inventory Management', 'icon' => 'fas fa-tools', 'emoji' => '📦'],
        
        'notification' => ['name' => 'Communication', 'icon' => 'fas fa-bell', 'emoji' => '📱'],
        'sms' => ['name' => 'Communication', 'icon' => 'fas fa-sms', 'emoji' => '📱'],
        'email' => ['name' => 'Communication', 'icon' => 'fas fa-envelope', 'emoji' => '📱'],
        'announcement' => ['name' => 'Communication', 'icon' => 'fas fa-bullhorn', 'emoji' => '📱'],
        
        'backup' => ['name' => 'System Events', 'icon' => 'fas fa-database', 'emoji' => '⚙️'],
        'maintenance' => ['name' => 'System Events', 'icon' => 'fas fa-cog', 'emoji' => '⚙️'],
        'security' => ['name' => 'System Events', 'icon' => 'fas fa-shield-alt', 'emoji' => '⚙️'],
        'login' => ['name' => 'System Events', 'icon' => 'fas fa-sign-in-alt', 'emoji' => '⚙️'],
    ];

    /**
     * Discover all events in the application
     */
    public function discoverAllEvents(): array
    {
        $events = [];
        
        // Discover from Events directory
        $events = array_merge($events, $this->discoverFromEventsDirectory());
        
        // Discover from Model events (Eloquent events)
        $events = array_merge($events, $this->discoverFromModelEvents());
        
        // Discover from registered event listeners
        $events = array_merge($events, $this->discoverFromEventListeners());
        
        // Discover from custom event annotations
        $events = array_merge($events, $this->discoverFromAnnotations());
        
        return $this->organizeAndFormatEvents($events);
    }

    /**
     * Discover events from App/Events directory
     */
    protected function discoverFromEventsDirectory(): array
    {
        $events = [];
        $eventsPath = app_path('Events');
        
        if (!File::exists($eventsPath)) {
            return $events;
        }

        $files = File::allFiles($eventsPath);
        
        foreach ($files as $file) {
            $className = 'App\\Events\\' . str_replace(['/', '.php'], ['\\', ''], $file->getRelativePathname());
            
            if (class_exists($className)) {
                $eventInfo = $this->analyzeEventClass($className);
                if ($eventInfo) {
                    $events[] = $eventInfo;
                }
            }
        }
        
        return $events;
    }

    /**
     * Discover Eloquent model events
     */
    protected function discoverFromModelEvents(): array
    {
        $events = [];
        $modelsPath = app_path('Models');
        
        if (!File::exists($modelsPath)) {
            return $events;
        }

        $files = File::allFiles($modelsPath);
        $eloquentEvents = ['creating', 'created', 'updating', 'updated', 'deleting', 'deleted', 'saving', 'saved'];
        
        foreach ($files as $file) {
            $className = 'App\\Models\\' . str_replace(['/', '.php'], ['\\', ''], $file->getRelativePathname());
            
            if (class_exists($className)) {
                $modelName = class_basename($className);
                
                foreach ($eloquentEvents as $eventType) {
                    $events[] = [
                        'event_key' => strtolower($modelName) . '.' . $eventType,
                        'name' => $modelName . ' ' . ucfirst($eventType),
                        'description' => "Triggered when {$modelName} is {$eventType}",
                        'class' => "eloquent:{$className}:{$eventType}",
                        'category' => $this->categorizeEvent(strtolower($modelName)),
                        'auto_discovered' => true,
                        'model' => $modelName,
                        'eloquent_event' => $eventType
                    ];
                }
            }
        }
        
        return $events;
    }

    /**
     * Discover from registered event listeners in EventServiceProvider
     */
    protected function discoverFromEventListeners(): array
    {
        $events = [];
        
        try {
            $eventServiceProvider = app(\App\Providers\EventServiceProvider::class);
            $reflection = new ReflectionClass($eventServiceProvider);
            $listenProperty = $reflection->getProperty('listen');
            $listenProperty->setAccessible(true);
            $listeners = $listenProperty->getValue($eventServiceProvider);
            
            foreach ($listeners as $eventClass => $listenerClasses) {
                if (class_exists($eventClass)) {
                    $eventInfo = $this->analyzeEventClass($eventClass);
                    if ($eventInfo) {
                        $eventInfo['has_listeners'] = true;
                        $eventInfo['listeners'] = $listenerClasses;
                        $events[] = $eventInfo;
                    }
                }
            }
        } catch (\Exception $e) {
            // Silently fail if we can't access the EventServiceProvider
        }
        
        return $events;
    }

    /**
     * Discover events from annotations/docblocks
     */
    protected function discoverFromAnnotations(): array
    {
        $events = [];
        
        // Search for @webhook or @event annotations in controllers and services
        $searchPaths = [
            app_path('Http/Controllers'),
            app_path('Services'),
            app_path('Jobs'),
        ];
        
        foreach ($searchPaths as $path) {
            if (File::exists($path)) {
                $events = array_merge($events, $this->scanForAnnotations($path));
            }
        }
        
        return $events;
    }

    /**
     * Analyze an event class to extract information
     */
    protected function analyzeEventClass(string $className): ?array
    {
        try {
            $reflection = new ReflectionClass($className);
            $eventName = class_basename($className);
            
            // Convert CamelCase to snake_case
            $eventKey = Str::snake($eventName);
            
            // Try to extract description from docblock
            $docComment = $reflection->getDocComment();
            $description = $this->extractDescriptionFromDocblock($docComment) ?: 
                          $this->generateDescriptionFromClassName($eventName);
            
            // Analyze properties to understand the event better
            $properties = $this->analyzeEventProperties($reflection);
            
            return [
                'event_key' => $eventKey,
                'name' => $this->formatEventName($eventName),
                'description' => $description,
                'class' => $className,
                'category' => $this->categorizeEvent($eventKey),
                'auto_discovered' => true,
                'properties' => $properties,
                'file_path' => $reflection->getFileName(),
            ];
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Analyze event properties to understand what data it carries
     */
    protected function analyzeEventProperties(ReflectionClass $reflection): array
    {
        $properties = [];
        
        foreach ($reflection->getProperties(ReflectionProperty::IS_PUBLIC) as $property) {
            $propertyName = $property->getName();
            $propertyType = $property->getType()?->getName() ?? 'mixed';
            
            $properties[$propertyName] = [
                'type' => $propertyType,
                'description' => $this->guessPropertyDescription($propertyName, $propertyType)
            ];
        }
        
        return $properties;
    }

    /**
     * Categorize an event based on its name
     */
    protected function categorizeEvent(string $eventKey): array
    {
        foreach ($this->eventCategories as $keyword => $category) {
            if (Str::contains($eventKey, $keyword)) {
                return $category;
            }
        }
        
        // Default category
        return ['name' => 'Other Events', 'icon' => 'fas fa-bell', 'emoji' => '📋'];
    }

    /**
     * Scan directory for webhook/event annotations
     */
    protected function scanForAnnotations(string $path): array
    {
        $events = [];
        $files = File::allFiles($path);
        
        foreach ($files as $file) {
            $content = File::get($file->getPathname());
            
            // Look for @webhook or @event annotations
            if (preg_match_all('/@(webhook|event)\s+([^\n\r]+)/i', $content, $matches)) {
                foreach ($matches[2] as $index => $eventDefinition) {
                    $eventInfo = $this->parseAnnotationDefinition($eventDefinition, $file->getPathname());
                    if ($eventInfo) {
                        $events[] = $eventInfo;
                    }
                }
            }
        }
        
        return $events;
    }

    /**
     * Parse annotation definition
     */
    protected function parseAnnotationDefinition(string $definition, string $filePath): ?array
    {
        // Simple parsing for annotations like:
        // @webhook payment.created "Payment was created" category="financial"
        $parts = str_getcsv($definition, ' ');
        
        if (count($parts) < 2) {
            return null;
        }
        
        $eventKey = $parts[0];
        $description = isset($parts[1]) ? trim($parts[1], '"') : '';
        
        return [
            'event_key' => $eventKey,
            'name' => $this->formatEventName($eventKey),
            'description' => $description,
            'class' => "annotation:{$filePath}",
            'category' => $this->categorizeEvent($eventKey),
            'auto_discovered' => true,
            'source' => 'annotation',
            'file_path' => $filePath
        ];
    }

    /**
     * Extract description from docblock
     */
    protected function extractDescriptionFromDocblock($docComment): ?string
    {
        if (!$docComment) {
            return null;
        }
        
        $lines = explode("\n", $docComment);
        foreach ($lines as $line) {
            $line = trim($line, "/* \t");
            if ($line && !Str::startsWith($line, '@') && $line !== '/**' && $line !== '*/') {
                return $line;
            }
        }
        
        return null;
    }

    /**
     * Generate description from class name
     */
    protected function generateDescriptionFromClassName(string $className): string
    {
        $words = preg_split('/(?=[A-Z])/', $className, -1, PREG_SPLIT_NO_EMPTY);
        $words = array_map('strtolower', $words);
        
        if (count($words) >= 2) {
            $action = array_pop($words);
            $subject = implode(' ', $words);
            return "Triggered when {$subject} is {$action}";
        }
        
        return "Event triggered: " . Str::title(Str::snake($className, ' '));
    }

    /**
     * Format event name for display
     */
    protected function formatEventName(string $eventName): string
    {
        if (Str::contains($eventName, '.')) {
            return Str::title(str_replace(['.', '_'], ' ', $eventName));
        }
        
        return Str::title(Str::snake($eventName, ' '));
    }

    /**
     * Guess property description based on name and type
     */
    protected function guessPropertyDescription(string $propertyName, string $propertyType): string
    {
        $descriptions = [
            'user' => 'The user who triggered this event',
            'student' => 'The student involved in this event',
            'payment' => 'The payment that was processed',
            'invoice' => 'The invoice that was generated',
            'amount' => 'The monetary amount involved',
            'model' => 'The model instance that triggered this event',
            'data' => 'Additional data for this event',
            'timestamp' => 'When this event occurred',
            'id' => 'The unique identifier for this event',
        ];
        
        return $descriptions[$propertyName] ?? "The {$propertyName} ({$propertyType})";
    }

    /**
     * Organize and format all discovered events
     */
    protected function organizeAndFormatEvents(array $events): array
    {
        $organized = [];
        $categories = [];
        
        foreach ($events as $event) {
            $categoryName = $event['category']['name'];
            
            if (!isset($categories[$categoryName])) {
                $categories[$categoryName] = [
                    'name' => $categoryName,
                    'icon' => $event['category']['icon'],
                    'emoji' => $event['category']['emoji'],
                    'events' => []
                ];
            }
            
            $categories[$categoryName]['events'][$event['event_key']] = $event;
            $organized[$event['event_key']] = $event;
        }
        
        return [
            'events' => $organized,
            'categories' => $categories,
            'total_count' => count($organized),
            'discovered_at' => now()
        ];
    }

    /**
     * Sync discovered events with webhook system
     */
    public function syncWithWebhookSystem(): array
    {
        $discovered = $this->discoverAllEvents();
        
        // Update the webhook model's available events
        $this->updateWebhookModel($discovered['events']);
        
        // Cache the discovered events
        cache()->put('discovered_events', $discovered, now()->addHours(24));
        
        return [
            'synced_events' => count($discovered['events']),
            'categories' => count($discovered['categories']),
            'new_events' => $this->findNewEvents($discovered['events']),
            'discovered_at' => $discovered['discovered_at']
        ];
    }

    /**
     * Update the Webhook model with discovered events
     */
    protected function updateWebhookModel(array $events): void
    {
        // We'll use a dynamic approach instead of hardcoding in the model
        cache()->put('webhook_available_events', $events, now()->addDays(7));
    }

    /**
     * Find new events that weren't previously known
     */
    protected function findNewEvents(array $currentEvents): array
    {
        $previousEvents = cache()->get('discovered_events.events', []);
        
        return array_diff_key($currentEvents, $previousEvents);
    }

    /**
     * Get cached events or discover them
     */
    public static function getAvailableEvents(): array
    {
        return cache()->remember('webhook_available_events', now()->addHours(24), function () {
            $service = new self();
            $discovered = $service->discoverAllEvents();
            return $discovered['events'];
        });
    }

    /**
     * Get events grouped by category
     */
    public static function getEventsByCategory(): array
    {
        return cache()->remember('webhook_events_by_category', now()->addHours(24), function () {
            $service = new self();
            $discovered = $service->discoverAllEvents();
            return $discovered['categories'];
        });
    }
}