<?php

use Carbon\Carbon;

// COMPLETE app/Helpers/view_helpers.php file with ALL missing functions

if (! function_exists('getEventTypeIcon')) {
    function getEventTypeIcon($eventName)
    {
        $icons = [
            'payment.created' => 'fas fa-credit-card',
            'student.created' => 'fas fa-user-graduate',
            'admission.approved' => 'fas fa-check-circle',
            'enquiry.created' => 'fas fa-question-circle',
            'daily.summary' => 'fas fa-calendar-alt',
            'invoice.generated' => 'fas fa-file-invoice',
            'receipt.generated' => 'fas fa-receipt',
            'fee.reminder.sent' => 'fas fa-bell',
            'attendance.marked' => 'fas fa-clock',
            'leave.application.created' => 'fas fa-calendar-times',
            'certificate.generated' => 'fas fa-certificate',
        ];

        return $icons[$eventName] ?? 'fas fa-bolt';
    }
}

if (! function_exists('formatEventName')) {
    function formatEventName($eventName): string
    {
        if (is_array($eventName) || is_object($eventName) || is_null($eventName)) {
            return 'Unknown Event';
        }

        $eventName = (string) $eventName;

        // Convert snake_case or kebab-case to Title Case
        $formatted = str_replace(['.', '_', '-'], ' ', $eventName);
        $formatted = ucwords($formatted);

        return $formatted ?: 'Unknown Event';
    }
}

if (! function_exists('isToday')) {
    function isToday($date): bool
    {
        if (! $date) {
            return false;
        }
        try {
            return Carbon::parse($date)->isToday();
        } catch (Exception $e) {
            return false;
        }
    }
}

if (! function_exists('formatDate')) {
    function formatDate($date, $format = 'd M Y'): string
    {
        if (! $date) {
            return 'N/A';
        }
        try {
            return Carbon::parse($date)->format($format);
        } catch (Exception $e) {
            return 'N/A';
        }
    }
}

if (! function_exists('formatDateTime')) {
    function formatDateTime($datetime, $format = 'd M Y, h:i A'): string
    {
        if (! $datetime) {
            return 'N/A';
        }
        try {
            return Carbon::parse($datetime)->format($format);
        } catch (Exception $e) {
            return 'N/A';
        }
    }
}

if (! function_exists('timeAgo')) {
    function timeAgo($datetime): string
    {
        if (! $datetime) {
            return 'N/A';
        }
        try {
            return Carbon::parse($datetime)->diffForHumans();
        } catch (Exception $e) {
            return 'N/A';
        }
    }
}

if (! function_exists('formatFileSize')) {
    function formatFileSize($bytes): string
    {
        if (! is_numeric($bytes) || $bytes == 0) {
            return '0 Bytes';
        }

        $k = 1024;
        $sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
        $i = floor(log($bytes) / log($k));

        return round($bytes / pow($k, $i), 2).' '.$sizes[$i];
    }
}

if (! function_exists('hasRole')) {
    function hasRole($role): bool
    {
        return auth()->check() && auth()->user()->hasRole($role);
    }
}
