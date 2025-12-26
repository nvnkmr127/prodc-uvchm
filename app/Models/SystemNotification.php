<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class SystemNotification extends Model
{
    use HasFactory;

    protected $fillable = [
        'title', 'message', 'type', 'category', 'priority', 'data',
        'action_url', 'action_text', 'requires_action', 'play_sound',
        'sound_file', 'is_persistent', 'expires_at',
        'sent_to_roles', 'sent_to_users', 'read_by', 'user_id'
    ];

    protected $casts = [
        'data' => 'array',
        'sent_to_roles' => 'array',
        'sent_to_users' => 'array',
        'read_by' => 'array',
        'requires_action' => 'boolean',
        'play_sound' => 'boolean',
        'is_persistent' => 'boolean',
        'expires_at' => 'datetime',
    ];

    // --- SCOPES ---

    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where(function ($q) use ($userId) {
            $q->whereJsonContains('sent_to_users', $userId)
              ->orWhere(function ($subQ) use ($userId) {
                  $userRoles = \App\Models\User::find($userId)?->roles->pluck('name')->toArray() ?? [];
                  foreach ($userRoles as $role) {
                      $subQ->orWhereJsonContains('sent_to_roles', $role);
                  }
              });
        });
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where(function ($q) {
            $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
        });
    }

    public function scopeUnread(Builder $query, int $userId): Builder
    {
        return $query->where(function ($q) use ($userId) {
            $q->whereNull('read_by')->orWhereJsonDoesntContain('read_by', $userId);
        });
    }

    // --- METHODS ---

    public static function getUnreadCountForUser(int $userId): int
    {
        return self::forUser($userId)->active()->unread($userId)->count();
    }

    /**
     * Check if notification can be viewed by user
     */
    public function canBeViewedBy(int $userId): bool
    {
        // 1. Allow Super Admins to view everything
        $user = \App\Models\User::find($userId);
        if ($user && $user->hasRole(['super-admin', 'admin'])) {
            return true;
        }

        // 2. Check if sent to user directly (Handle string/int mismatch)
        $targetUsers = $this->sent_to_users ?? [];
        if (in_array($userId, $targetUsers) || in_array((string)$userId, $targetUsers)) {
            return true;
        }

        // 3. Check Roles
        $userRoles = $user?->roles->pluck('name')->toArray() ?? [];
        $notificationRoles = $this->sent_to_roles ?? [];
        
        return !empty(array_intersect($userRoles, $notificationRoles));
    }

    /**
     * Mark notification as read by a user
     */
    public function markAsReadBy(int $userId): bool
    {
        $readBy = $this->read_by ?? [];
        if (!in_array($userId, $readBy)) {
            $readBy[] = $userId;
            $this->read_by = $readBy;
            return $this->save();
        }
        return true;
    }

    public function getPriorityColorAttribute(): string
    {
        return match($this->priority) {
            'urgent' => 'danger',
            'high' => 'warning',
            'normal' => 'info',
            'low' => 'secondary',
            default => 'info'
        };
    }
}