<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware to apply dynamic settings to the application
 * This middleware should be registered in the global middleware stack
 */
class ApplyDynamicSettings
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Skip if in console (artisan commands)
        if (app()->runningInConsole()) {
            return $next($request);
        }

        try {
            // Apply dynamic settings
            $this->applyAppSettings();
            $this->applyTimezoneSettings();
            $this->applyMailSettings();
            $this->applySessionSettings();
            $this->applyMaintenanceMode();
            
        } catch (\Exception $e) {
            // Log error but don't break the application
            \Log::warning('Failed to apply dynamic settings: ' . $e->getMessage());
        }

        return $next($request);
    }

    /**
     * Apply general application settings
     */
    private function applyAppSettings()
    {
        // Set application name dynamically
        $appName = setting('app_name');
    if ($appName) {
        config(['app.name' => $appName]);
    }

        // Set application URL if different
        $appUrl = setting('app_url');
        if ($appUrl && $appUrl !== config('app.url')) {
            config(['app.url' => $appUrl]);
        }

        // Set debug mode from settings (be careful with this)
        $debugMode = setting('debug_mode', null, 'bool');
        if ($debugMode !== null && config('app.env') !== 'production') {
            config(['app.debug' => $debugMode]);
        }
    }

    /**
     * Apply timezone settings
     */
    private function applyTimezoneSettings()
    {
        $timezone = setting('timezone');
        if ($timezone) {
            config(['app.timezone' => $timezone]);
            date_default_timezone_set($timezone);
        }
    }

    /**
     * Apply mail configuration settings
     */
    private function applyMailSettings()
    {
        $mailDriver = setting('mail_driver');
        $mailHost = setting('mail_host');
        $mailPort = setting('mail_port');
        $mailUsername = setting('mail_username');
        $mailPassword = setting('mail_password');
        $mailEncryption = setting('mail_encryption');
        $mailFromAddress = setting('notification_sender_email');
        $mailFromName = setting('notification_sender_name');

        if ($mailDriver) {
            config(['mail.default' => $mailDriver]);
        }

        if ($mailHost) {
            config(['mail.mailers.smtp.host' => $mailHost]);
        }

        if ($mailPort) {
            config(['mail.mailers.smtp.port' => (int) $mailPort]);
        }

        if ($mailUsername) {
            config(['mail.mailers.smtp.username' => $mailUsername]);
        }

        if ($mailPassword) {
            config(['mail.mailers.smtp.password' => $mailPassword]);
        }

        if ($mailEncryption) {
            config(['mail.mailers.smtp.encryption' => $mailEncryption]);
        }

        if ($mailFromAddress) {
            config(['mail.from.address' => $mailFromAddress]);
        }

        if ($mailFromName) {
            config(['mail.from.name' => $mailFromName]);
        }
    }

    /**
     * Apply session configuration
     */
    private function applySessionSettings()
    {
        $sessionTimeout = setting('session_timeout');
        if ($sessionTimeout) {
            // Convert minutes to seconds
            config(['session.lifetime' => (int) $sessionTimeout]);
        }

        $sessionDriver = setting('session_driver');
        if ($sessionDriver) {
            config(['session.driver' => $sessionDriver]);
        }
    }

    /**
     * Apply maintenance mode if enabled
     */
    private function applyMaintenanceMode()
    {
        $maintenanceMode = setting('maintenance_mode', false, 'bool');
        
        if ($maintenanceMode && !app()->isDownForMaintenance()) {
            // Only apply if not already in maintenance mode
            // This is handled by the toggle-maintenance route instead
        }
    }
}

// Add this to app/Http/Kernel.php in the $middleware array:
// \App\Http\Middleware\ApplyDynamicSettings::class,