<?php
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('user.{userId}', function ($user, $userId) {
    return (int) $user->id === (int) $userId;
});

Broadcast::channel('role.{role}', function ($user, $role) {
    return $user->hasRole($role);
});

Broadcast::channel('notifications', function ($user) {
    return auth()->check();
});