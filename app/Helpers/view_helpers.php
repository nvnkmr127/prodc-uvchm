<?php

// COMPLETE app/Helpers/view_helpers.php file with ALL missing functions

if (!function_exists('safeDisplay')) {
    function safeDisplay($value): string
    {
        if ($value === null || $value === '') {
            return '';
        }
        if (is_array($value) || is_object($value)) {
            return '';
        }
        try {
            return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
        } catch (\Exception $e) {
            return '';
        }
    }
}

if (!function_exists('safeDisplayUrl')) {
    function safeDisplayUrl($url): string
    {
        if ($url === null || $url === '') {
            return '';
        }
        if (is_array($url) || is_object($url)) {
            return '';
        }
        try {
            $urlString = htmlspecialchars((string)$url, ENT_QUOTES, 'UTF-8');
            return strlen($urlString) > 50 ? substr($urlString, 0, 47) . '...' : $urlString;
        } catch (\Exception $e) {
            return '';
        }
    }
}

if (!function_exists('getEventTypeIcon')) {
    function getEventTypeIcon($eventName): string
    {
        if (is_array($eventName) || is_object($eventName) || is_null($eventName)) {
            return 'fas fa-bell';
        }
        
        $eventName = (string)$eventName;
        $eventLower = strtolower($eventName);
        
        if (str_contains($eventLower, 'payment')) {
            return 'fas fa-credit-card';
        } elseif (str_contains($eventLower, 'student')) {
            return 'fas fa-user-graduate';
        } elseif (str_contains($eventLower, 'invoice')) {
            return 'fas fa-file-invoice';
        } elseif (str_contains($eventLower, 'enquiry')) {
            return 'fas fa-question-circle';
        } elseif (str_contains($eventLower, 'admission')) {
            return 'fas fa-user-plus';
        } elseif (str_contains($eventLower, 'attendance')) {
            return 'fas fa-calendar-check';
        } elseif (str_contains($eventLower, 'exam')) {
            return 'fas fa-clipboard-list';
        } elseif (str_contains($eventLower, 'certificate')) {
            return 'fas fa-certificate';
        } else {
            return 'fas fa-bell';
        }
    }
}

if (!function_exists('getStatusBadgeClass')) {
    function getStatusBadgeClass($status): string
    {
        if (is_array($status) || is_object($status) || is_null($status)) {
            return 'badge-secondary';
        }
        
        $status = (string)$status;
        $statusLower = strtolower($status);
        
        switch ($statusLower) {
            case 'active':
            case 'success':
            case 'completed':
            case 'paid':
            case 'approved':
                return 'badge-success';
                
            case 'inactive':
            case 'disabled':
            case 'cancelled':
            case 'rejected':
                return 'badge-danger';
                
            case 'pending':
            case 'processing':
            case 'partial':
            case 'partially_paid':
                return 'badge-warning';
                
            case 'draft':
            case 'unpaid':
            case 'new':
                return 'badge-info';
                
            default:
                return 'badge-secondary';
        }
    }
}

if (!function_exists('getHealthStatusIcon')) {
    function getHealthStatusIcon($status): string
    {
        if (is_array($status) || is_object($status) || is_null($status)) {
            return 'fas fa-question-circle text-muted';
        }
        
        $status = (string)$status;
        $statusLower = strtolower($status);
        
        switch ($statusLower) {
            case 'healthy':
            case 'good':
            case 'online':
                return 'fas fa-check-circle text-success';
                
            case 'warning':
            case 'degraded':
                return 'fas fa-exclamation-triangle text-warning';
                
            case 'error':
            case 'failed':
            case 'offline':
            case 'unhealthy':
                return 'fas fa-times-circle text-danger';
                
            default:
                return 'fas fa-question-circle text-muted';
        }
    }
}

if (!function_exists('formatEventName')) {
    function formatEventName($eventName): string
    {
        if (is_array($eventName) || is_object($eventName) || is_null($eventName)) {
            return 'Unknown Event';
        }
        
        $eventName = (string)$eventName;
        
        // Convert snake_case or kebab-case to Title Case
        $formatted = str_replace(['.', '_', '-'], ' ', $eventName);
        $formatted = ucwords($formatted);
        
        return $formatted ?: 'Unknown Event';
    }
}

if (!function_exists('truncateText')) {
    function truncateText($text, $length = 50): string
    {
        if (is_array($text) || is_object($text) || is_null($text)) {
            return '';
        }
        
        $text = (string)$text;
        
        if (strlen($text) <= $length) {
            return $text;
        }
        
        return substr($text, 0, $length - 3) . '...';
    }
}