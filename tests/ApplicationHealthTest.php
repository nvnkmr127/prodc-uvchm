<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class ApplicationHealthTest extends TestCase
{
    // Use this trait to reset the database for each test, but we'll disable it for a simple health check.
    // use RefreshDatabase; 

    /**
     * A basic test to check that all key admin routes are accessible.
     *
     * @return void
     */
    public function test_admin_routes_are_accessible()
    {
        // Find or create a super-admin user to run the tests
        $adminRole = Role::firstOrCreate(['name' => 'super-admin']);
        $adminUser = User::role('super-admin')->first();

        if (!$adminUser) {
            $this->fail("A 'super-admin' user is required to run the health check.");
        }
        
        // An array of all the key routes to test
        $routes = [
            '/admin/dashboard',
            '/admin/users',
            '/admin/roles',
            '/admin/permissions',
            '/admin/courses',
            '/admin/batches',
            '/admin/students',
            '/admin/students/import', // Test the import page
            '/admin/admissions',
            '/admin/settings',
            '/admin/configuration',
            '/admin/dashboard-builder',
            '/admin/widgets',
            '/admin/students/download-sample', // Test the download route
        ];

        echo "\n\n--- Starting Application Health Check ---\n";

        foreach ($routes as $route) {
            // Act as the admin user and visit the route
            $response = $this->actingAs($adminUser)->get($route);

            // Assert that the page returns a successful status code (not a 404 or 500)
            $response->assertStatus(200);
            
            echo "✅  Successfully accessed: {$route}\n";
        }

        echo "--- Health Check Complete ---\n";
    }
}
