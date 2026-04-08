<?php

// app/Events/DashboardDataUpdated.php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DashboardDataUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $eventType;

    public $data;

    public $targetRoles;

    public function __construct(string $eventType, array $data, array $targetRoles = [])
    {
        $this->eventType = $eventType;
        $this->data = $data;
        $this->targetRoles = $targetRoles;
    }

    public function broadcastOn()
    {
        if (empty($this->targetRoles)) {
            return new Channel('dashboard');
        }

        return collect($this->targetRoles)->map(function ($role) {
            return new PrivateChannel("dashboard.{$role}");
        })->toArray();
    }

    public function broadcastAs()
    {
        return 'dashboard.updated';
    }

    public function broadcastWith()
    {
        return [
            'event_type' => $this->eventType,
            'data' => $this->data,
            'timestamp' => now()->toISOString(),
        ];
    }
}

// app/Events/WidgetDataUpdated.php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class WidgetDataUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $widgetType;

    public $data;

    public $targetUsers;

    public function __construct(string $widgetType, array $data, array $targetUsers = [])
    {
        $this->widgetType = $widgetType;
        $this->data = $data;
        $this->targetUsers = $targetUsers;
    }

    public function broadcastOn()
    {
        if (empty($this->targetUsers)) {
            return new Channel('widgets');
        }

        return collect($this->targetUsers)->map(function ($role) {
            return new PrivateChannel("widgets.{$role}");
        })->toArray();
    }

    public function broadcastAs()
    {
        return 'widget.updated';
    }

    public function broadcastWith()
    {
        return [
            'widget_type' => $this->widgetType,
            'data' => $this->data,
            'timestamp' => now()->toISOString(),
        ];
    }
}

// app/Events/SystemAlertCreated.php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SystemAlertCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $alert;

    public $targetRoles;

    public function __construct(array $alert, array $targetRoles = ['super-admin'])
    {
        $this->alert = $alert;
        $this->targetRoles = $targetRoles;
    }

    public function broadcastOn()
    {
        return collect($this->targetRoles)->map(function ($role) {
            return new PrivateChannel("alerts.{$role}");
        })->toArray();
    }

    public function broadcastAs()
    {
        return 'alert.created';
    }

    public function broadcastWith()
    {
        return [
            'alert' => $this->alert,
        ];
    }
}

// app/Events/UserDashboardActivity.php

namespace App\Events;

use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UserDashboardActivity implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $user;

    public $activity;

    public $metadata;

    public function __construct(User $user, string $activity, array $metadata = [])
    {
        $this->user = $user;
        $this->activity = $activity;
        $this->metadata = $metadata;
    }

    public function broadcastOn()
    {
        return new PresenceChannel('dashboard.activity');
    }

    public function broadcastAs()
    {
        return 'user.activity';
    }

    public function broadcastWith()
    {
        return [
            'user_id' => $this->user->id,
            'user_name' => $this->user->name,
            'user_role' => $this->user->getRoleNames()->first(),
            'activity' => $this->activity,
            'metadata' => $this->metadata,
            'timestamp' => now()->toISOString(),
        ];
    }
}
