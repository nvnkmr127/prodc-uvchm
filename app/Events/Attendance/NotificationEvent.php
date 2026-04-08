<?php

namespace App\Events\Attendance;

use App\Models\Attendance\NotificationLog;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NotificationEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public string $action;

    public ?NotificationLog $notification;

    public array $data;

    public function __construct(string $action, array $data = [], ?NotificationLog $notification = null)
    {
        $this->action = $action;
        $this->data = $data;
        $this->notification = $notification;
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        $channels = [new Channel('notifications.updates')];

        if ($this->notification) {
            if ($this->notification->student_id) {
                $channels[] = new PrivateChannel('student.'.$this->notification->student_id);
            }

            if ($this->notification->recipient_id) {
                $channels[] = new PrivateChannel('user.'.$this->notification->recipient_id);
            }
        }

        return $channels;
    }

    /**
     * Get the data to broadcast
     */
    public function broadcastWith(): array
    {
        $broadcastData = [
            'action' => $this->action,
            'data' => $this->data,
            'timestamp' => now()->toISOString(),
        ];

        if ($this->notification) {
            $broadcastData['notification'] = [
                'id' => $this->notification->id,
                'type' => $this->notification->notification_type,
                'title' => $this->notification->title,
                'message' => $this->notification->message,
                'status' => $this->notification->status,
                'priority' => $this->notification->priority,
                'channel' => $this->notification->channel,
                'created_at' => $this->notification->created_at->toISOString(),
            ];
        }

        return $broadcastData;
    }

    /**
     * Get the broadcast event name
     */
    public function broadcastAs(): string
    {
        return 'notification.'.$this->action;
    }
}
