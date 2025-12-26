<?php

namespace App\Events\Attendance;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AnalyticsEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public string $type;
    public array $analytics;
    public array $metadata;

    public function __construct(string $type, array $analytics, array $metadata = [])
    {
        $this->type = $type;
        $this->analytics = $analytics;
        $this->metadata = $metadata;
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('analytics.updates'),
            new Channel('dashboard.updates')
        ];
    }

    /**
     * Get the data to broadcast
     */
    public function broadcastWith(): array
    {
        return [
            'type' => $this->type,
            'analytics' => $this->analytics,
            'metadata' => $this->metadata,
            'timestamp' => now()->toISOString()
        ];
    }

    /**
     * Get the broadcast event name
     */
    public function broadcastAs(): string
    {
        return 'analytics.' . $this->type;
    }
}
