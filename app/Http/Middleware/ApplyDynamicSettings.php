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
            // Cache settings to prevent DB queries on every HTTP request
            $settings = cache()->remember('app_dynamic_settings', now()->addMinutes(60), function () {
                return [
                    'app_name' => setting('app_name'),
                    'app_url' => setting('app_url'),
                    'debug_mode' => setting('debug_mode', null, 'bool'),
                    'timezone' => setting('timezone'),
                    'mail_driver' => setting('mail_driver'),
                    'mail_host' => setting('mail_host'),
                    'mail_port' => setting('mail_port'),
                    'mail_username' => setting('mail_username'),
                    'mail_password' => setting('mail_password'),
                    'mail_encryption' => setting('mail_encryption'),
                    'notification_sender_email' => setting('notification_sender_email'),
                    'notification_sender_name' => setting('notification_sender_name'),
                    'session_timeout' => setting('session_timeout'),
                    'session_driver' => setting('session_driver'),
                    'maintenance_mode' => setting('maintenance_mode', false, 'bool'),
                ];
            });

            // Apply dynamic settings
            $this->applyAppSettings($settings);
            $this->applyTimezoneSettings($settings);
            $this->applyMailSettings($settings);
            $this->applySessionSettings($settings);
            $this->applyMaintenanceMode($settings);

        } catch (\Exception $e) {
            // Log error but don't break the application
            \Log::warning('Failed to apply dynamic settings: '.$e->getMessage());
        }

        return $next($request);
    }

    /**
     * Apply general application settings
     */
    private function applyAppSettings(array $settings)
    {
        // Set application name dynamically
        $appName = $settings['app_name'];
        if ($appName) {
            config(['app.name' => $appName]);
        }

        // Set application URL if different
        $appUrl = $settings['app_url'];
        if ($appUrl && $appUrl !== config('app.url')) {
            config(['app.url' => $appUrl]);
        }

        // Set debug mode from settings (be careful with this)
        $debugMode = $settings['debug_mode'];
        if ($debugMode !== null && config('app.env') !== 'production') {
            config(['app.debug' => $debugMode]);
        }
    }

    /**
     * Apply timezone settings
     */
    private function applyTimezoneSettings(array $settings)
    {
        $timezone = $settings['timezone'];
        if ($timezone) {
            config(['app.timezone' => $timezone]);
            date_default_timezone_set($timezone);
        }
    }

    /**
     * Apply mail configuration settings
     */
    private function applyMailSettings(array $settings)
    {
        $mailDriver = $settings['mail_driver'];
        $mailHost = $settings['mail_host'];
        $mailPort = $settings['mail_port'];
        $mailUsername = $settings['mail_username'];
        $mailPassword = $settings['mail_password'];
        $mailEncryption = $settings['mail_encryption'];
        $mailFromAddress = $settings['notification_sender_email'];
        $mailFromName = $settings['notification_sender_name'];

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
    private function applySessionSettings(array $settings)
    {
        $sessionTimeout = $settings['session_timeout'];
        if ($sessionTimeout) {
            // Convert minutes to seconds
            config(['session.lifetime' => (int) $sessionTimeout]);
        }

        $sessionDriver = $settings['session_driver'];
        if ($sessionDriver) {
            config(['session.driver' => $sessionDriver]);
        }
    }

    /**
     * Apply maintenance mode if enabled
     */
    private function applyMaintenanceMode(array $settings)
    {
        $maintenanceMode = $settings['maintenance_mode'];

        if ($maintenanceMode && ! app()->isDownForMaintenance()) {
            // Only apply if not already in maintenance mode
            // This is handled by the toggle-maintenance route instead
        }
    }
}
