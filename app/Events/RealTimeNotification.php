<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class RealTimeNotification implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $notification;

    public $targetUsers;

    public $targetRoles;

    public function __construct($notification, $targetUsers = [], $targetRoles = [])
    {
        $this->notification = $notification;
        $this->targetUsers = $targetUsers;
        $this->targetRoles = $targetRoles;
    }

    public function broadcastOn()
    {
        $channels = [new Channel('notifications')];

        // Add user-specific channels
        foreach ($this->targetUsers as $userId) {
            $channels[] = new PrivateChannel("user.{$userId}");
        }

        // Add role-based channels
        foreach ($this->targetRoles as $role) {
            $channels[] = new PrivateChannel("role.{$role}");
        }

        return $channels;
    }

    public function broadcastWith()
    {
        return [
            'id' => $this->notification->id,
            'title' => $this->notification->title,
            'message' => $this->notification->message,
            'type' => $this->notification->type,
            'category' => $this->notification->category,
            'priority' => $this->notification->priority,
            'play_sound' => $this->notification->play_sound,
            'sound_file' => $this->notification->sound_file,
            'action_url' => $this->notification->action_url,
            'action_text' => $this->notification->action_text,
            'requires_action' => $this->notification->requires_action,
            'created_at' => $this->notification->created_at->toISOString(),
            'data' => $this->notification->data,
        ];
    }

    public function broadcastAs()
    {
        return 'notification.new';
    }
}
