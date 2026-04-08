<?php

namespace App\Events\Attendance;

use App\Models\Attendance\BiometricLog;
use App\Models\BiometricDevice;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BiometricEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public string $action;

    public ?BiometricLog $log;

    public ?BiometricDevice $device;

    public array $data;

    public function __construct(string $action, array $data = [], ?BiometricLog $log = null, ?BiometricDevice $device = null)
    {
        $this->action = $action;
        $this->data = $data;
        $this->log = $log;
        $this->device = $device;
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        $channels = [new Channel('biometric.updates')];

        if ($this->device) {
            $channels[] = new PrivateChannel('biometric.device.'.$this->device->id);
        }

        if ($this->log && $this->log->student_id) {
            $channels[] = new PrivateChannel('student.'.$this->log->student_id);
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

        if ($this->log) {
            $broadcastData['log'] = [
                'id' => $this->log->id,
                'device_id' => $this->log->device_id,
                'employee_code' => $this->log->employee_code,
                'scan_datetime' => $this->log->scan_datetime->toISOString(),
                'status' => $this->log->status,
                'student_name' => $this->log->student->name ?? 'Unknown',
            ];
        }

        if ($this->device) {
            $broadcastData['device'] = [
                'id' => $this->device->id,
                'device_name' => $this->device->device_name,
                'device_id' => $this->device->device_id,
                'status' => $this->device->status,
                'location' => $this->device->location,
            ];
        }

        return $broadcastData;
    }

    /**
     * Get the broadcast event name
     */
    public function broadcastAs(): string
    {
        return 'biometric.'.$this->action;
    }
}
