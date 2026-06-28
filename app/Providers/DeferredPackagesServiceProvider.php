<?php

namespace App\Providers;

use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;

class DeferredPackagesServiceProvider extends ServiceProvider implements DeferrableProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Register the actual underlying service providers on-demand
        $this->app->register(\Barryvdh\DomPDF\ServiceProvider::class);
        $this->app->register(\Maatwebsite\Excel\ExcelServiceProvider::class);
        $this->app->register(\Spatie\Backup\BackupServiceProvider::class);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array<int, string>
     */
    public function provides(): array
    {
        return [
            // PDF
            'dompdf',
            'dompdf.options',
            'dompdf.wrapper',
            
            // Excel
            'excel',
            
            // Backup commands
            'command.backup.run',
            'command.backup.clean',
            'command.backup.list',
            'command.backup.monitor',
        ];
    }
}
