<?php
// File: app/Events/Attendance/AttendanceEvent.php

namespace App\Events\Attendance;

use App\Models\Attendance\Attendance;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AttendanceEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Attendance $attendance;
    public string $action;
    public array $metadata;

    public function __construct(string $action, Attendance $attendance, array $metadata = [])
    {
        $this->action = $action;
        $this->attendance = $attendance;
        $this->metadata = $metadata;
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('attendance.' . $this->attendance->batch_id),
            new PrivateChannel('student.' . $this->attendance->student_id),
            new Channel('attendance.updates')
        ];
    }

    /**
     * Get the data to broadcast
     */
    public function broadcastWith(): array
    {
        return [
            'action' => $this->action,
            'attendance' => [
                'id' => $this->attendance->id,
                'student_id' => $this->attendance->student_id,
                'student_name' => $this->attendance->student->name,
                'batch_id' => $this->attendance->batch_id,
                'batch_name' => $this->attendance->batch->name,
                'status' => $this->attendance->status,
                'attendance_date' => $this->attendance->attendance_date->format('Y-m-d'),
                'marked_at' => $this->attendance->marked_at->toISOString()
            ],
            'metadata' => $this->metadata,
            'timestamp' => now()->toISOString()
        ];
    }

    /**
     * Get the broadcast event name
     */
    public function broadcastAs(): string
    {
        return 'attendance.' . $this->action;
    }
}