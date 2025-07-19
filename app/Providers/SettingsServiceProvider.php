<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Config;
use App\Models\Setting;

class SettingsServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Load settings after database is available
        if ($this->app->runningInConsole() && !$this->app->runningUnitTests()) {
            return;
        }

        try {
            // Check if settings table exists
            if (\Schema::hasTable('settings')) {
                $this->loadSettings();
                $this->shareSettingsWithViews();
            }
        } catch (\Exception $e) {
            // Silently fail during migrations or when database is not ready
        }

        // Register settings commands
        if ($this->app->runningInConsole()) {
            $this->commands([
                \App\Console\Commands\BackupSettings::class,
            ]);
        }
    }

    /**
     * Load settings into config
     */
    protected function loadSettings(): void
    {
        $settings = Setting::all();

        foreach ($settings as $setting) {
            $value = $setting->getTypedValue();
            
            // Update config values
            Config::set("settings.{$setting->key}", $value);
            
            // Also set some key settings in their respective config files
            switch ($setting->key) {
                case 'app_name':
                    Config::set('app.name', $value);
                    break;
                case 'timezone':
                    Config::set('app.timezone', $value);
                    break;
                case 'mail_host':
                    Config::set('mail.mailers.smtp.host', $value);
                    break;
                case 'mail_port':
                    Config::set('mail.mailers.smtp.port', $value);
                    break;
                case 'mail_username':
                    Config::set('mail.mailers.smtp.username', $value);
                    break;
                case 'mail_password':
                    Config::set('mail.mailers.smtp.password', $value);
                    break;
                case 'mail_encryption':
                    Config::set('mail.mailers.smtp.encryption', $value);
                    break;
            }
        }
    }

    /**
     * Share common settings with all views
     */
    protected function shareSettingsWithViews(): void
    {
        // Get public settings for frontend use
        $publicSettings = Setting::where('is_public', true)
                                ->pluck('value', 'key')
                                ->toArray();

        // Share with all views
        View::share('publicSettings', $publicSettings);
        
        // Share common app settings
        View::share('appSettings', [
            'app_name' => setting('app_name', config('app.name')),
            'college_name' => setting('college_name'),
            'college_logo' => setting('college_logo'),
            'currency_symbol' => setting('currency_symbol', '₹'),
            'date_format' => setting('date_format', 'd-m-Y'),
            'timezone' => setting('timezone', 'Asia/Kolkata'),
        ]);
    }
}