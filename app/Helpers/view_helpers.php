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
    function getEventTypeIcon($eventName) {
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

if (!function_exists('formatNumberWithSuffix')) {
    function formatNumberWithSuffix($number): string
    {
        if (!is_numeric($number)) {
            return '0';
        }
        
        $number = (float)$number;
        if ($number >= 1000000000) {
            return round($number / 1000000000, 1) . 'B';
        } elseif ($number >= 1000000) {
            return round($number / 1000000, 1) . 'M';
        } elseif ($number >= 1000) {
            return round($number / 1000, 1) . 'K';
        }
        
        return (string)$number;
    }
}

if (!function_exists('getProgressBarClass')) {
    function getProgressBarClass($percentage): string
    {
        if (!is_numeric($percentage)) {
            return 'bg-secondary';
        }
        
        $percentage = (float)$percentage;
        if ($percentage >= 80) {
            return 'bg-success';
        } elseif ($percentage >= 60) {
            return 'bg-info';
        } elseif ($percentage >= 40) {
            return 'bg-warning';
        } else {
            return 'bg-danger';
        }
    }
}

if (!function_exists('isToday')) {
    function isToday($date): bool
    {
        if (!$date) {
            return false;
        }
        try {
            return \Carbon\Carbon::parse($date)->isToday();
        } catch (\Exception $e) {
            return false;
        }
    }
}

if (!function_exists('getMonthName')) {
    function getMonthName($month): string
    {
        $months = [
            1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
            5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
            9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'
        ];
        
        return $months[(int)$month] ?? 'Unknown';
    }
}

if (!function_exists('formatCurrency')) {
    function formatCurrency($amount, $currency = '₹'): string
    {
        if (is_null($amount) || !is_numeric($amount)) {
            return $currency . ' 0.00';
        }
        return $currency . ' ' . number_format((float)$amount, 2);
    }
}

if (!function_exists('formatDate')) {
    function formatDate($date, $format = 'd M Y'): string
    {
        if (!$date) {
            return 'N/A';
        }
        try {
            return \Carbon\Carbon::parse($date)->format($format);
        } catch (\Exception $e) {
            return 'N/A';
        }
    }
}

if (!function_exists('formatDateTime')) {
    function formatDateTime($datetime, $format = 'd M Y, h:i A'): string
    {
        if (!$datetime) {
            return 'N/A';
        }
        try {
            return \Carbon\Carbon::parse($datetime)->format($format);
        } catch (\Exception $e) {
            return 'N/A';
        }
    }
}

if (!function_exists('timeAgo')) {
    function timeAgo($datetime): string
    {
        if (!$datetime) {
            return 'N/A';
        }
        try {
            return \Carbon\Carbon::parse($datetime)->diffForHumans();
        } catch (\Exception $e) {
            return 'N/A';
        }
    }
}

if (!function_exists('getUserAvatar')) {
    function getUserAvatar($user, $size = 40): string
    {
        if ($user && isset($user->avatar) && $user->avatar) {
            return asset('storage/' . $user->avatar);
        }
        
        $name = ($user && isset($user->name)) ? $user->name : 'User';
        $initials = strtoupper(substr($name, 0, 1));
        
        return "https://ui-avatars.com/api/?name={$initials}&size={$size}&background=random";
    }
}

if (!function_exists('getPaymentStatusBadge')) {
    function getPaymentStatusBadge($status): string
    {
        if (is_array($status) || is_object($status) || is_null($status)) {
            return 'badge-secondary';
        }
        
        $status = strtolower((string)$status);
        $badges = [
            'paid' => 'badge-success',
            'unpaid' => 'badge-danger',
            'partial' => 'badge-warning',
            'overdue' => 'badge-danger',
            'pending' => 'badge-info',
            'cancelled' => 'badge-secondary',
            'refunded' => 'badge-dark'
        ];
        
        return $badges[$status] ?? 'badge-secondary';
    }
}

if (!function_exists('formatFileSize')) {
    function formatFileSize($bytes): string
    {
        if (!is_numeric($bytes) || $bytes == 0) {
            return '0 Bytes';
        }
        
        $k = 1024;
        $sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
        $i = floor(log($bytes) / log($k));
        
        return round($bytes / pow($k, $i), 2) . ' ' . $sizes[$i];
    }
}

if (!function_exists('getPercentageClass')) {
    function getPercentageClass($percentage): string
    {
        if (!is_numeric($percentage)) {
            return 'text-muted';
        }
        
        $percentage = (float)$percentage;
        if ($percentage >= 80) {
            return 'text-success';
        } elseif ($percentage >= 60) {
            return 'text-warning';
        } else {
            return 'text-danger';
        }
    }
}

if (!function_exists('hasPermission')) {
    function hasPermission($permission): bool
    {
        return auth()->check() && auth()->user()->can($permission);
    }
}

if (!function_exists('hasRole')) {
    function hasRole($role): bool
    {
        return auth()->check() && auth()->user()->hasRole($role);
    }
}

if (!function_exists('formatPhone')) {
    function formatPhone($phone): string
    {
        if (!$phone) {
            return 'N/A';
        }
        
        // Remove all non-numeric characters
        $phone = preg_replace('/[^0-9]/', '', (string)$phone);
        
        // Format Indian phone numbers
        if (strlen($phone) == 10) {
            return '+91 ' . substr($phone, 0, 5) . ' ' . substr($phone, 5);
        }
        
        return $phone;
    }
}

if (!function_exists('getAcademicYear')) {
    function getAcademicYear($date = null): string
    {
        try {
            $date = $date ? \Carbon\Carbon::parse($date) : now();
            $year = $date->year;
            
            // Academic year starts from April
            if ($date->month >= 4) {
                return $year . '-' . ($year + 1);
            } else {
                return ($year - 1) . '-' . $year;
            }
        } catch (\Exception $e) {
            $year = date('Y');
            return $year . '-' . ($year + 1);
        }
    }
}

if (!function_exists('isActiveRoute')) {
    function isActiveRoute($route, $output = 'active'): string
    {
        return request()->routeIs($route) ? $output : '';
    }
}

if (!function_exists('getInitials')) {
    function getInitials($name): string
    {
        if (!$name || is_array($name) || is_object($name)) {
            return 'U';
        }
        
        $name = (string)$name;
        $words = explode(' ', trim($name));
        $initials = '';
        
        foreach ($words as $word) {
            if (!empty($word)) {
                $initials .= strtoupper(substr($word, 0, 1));
            }
        }
        
        return substr($initials, 0, 2) ?: 'U';
    }
}