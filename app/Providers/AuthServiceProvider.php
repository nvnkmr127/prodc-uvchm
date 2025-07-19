<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        //
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        // Register policies
        $this->registerPolicies();

        // Define custom gates
        $this->defineGates();
    }

    /**
     * Define custom authorization gates
     */
    protected function defineGates(): void
    {
        // Super admin gate - super admins can do anything
        Gate::before(function ($user, $ability) {
            if ($user->hasRole('super-admin')) {
                return true;
            }
        });

        // Backend access gate
        Gate::define('access-backend', function ($user) {
            return $user->can('view backend');
        });

        // Settings management gates
        Gate::define('manage-settings', function ($user) {
            return $user->can('manage settings');
        });

        Gate::define('view-settings', function ($user) {
            return $user->can('view settings') || $user->can('manage settings');
        });

        // Student management gates
        Gate::define('manage-students', function ($user) {
            return $user->can('manage students');
        });

        Gate::define('view-students', function ($user) {
            return $user->can('view students') || $user->can('manage students');
        });

        // Financial management gates
        Gate::define('manage-financials', function ($user) {
            return $user->can('manage financials');
        });

        Gate::define('view-financials', function ($user) {
            return $user->can('view financials') || $user->can('manage financials');
        });

        // HR management gates
        Gate::define('manage-hr', function ($user) {
            return $user->can('manage hr');
        });

        Gate::define('view-hr', function ($user) {
            return $user->can('view hr') || $user->can('manage hr');
        });

        // Reports access gates
        Gate::define('access-reports', function ($user) {
            return $user->can('view reports') || $user->can('manage reports');
        });

        // Admin panel access
        Gate::define('access-admin-panel', function ($user) {
            return $user->hasAnyRole(['super-admin', 'admin', 'college-admin']);
        });
    }
}