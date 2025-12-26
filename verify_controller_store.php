<?php

use App\Models\User;
use App\Models\Enquiry;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Http\Controllers\Admin\EnquiryController;
use App\Services\LeadDistributionService;

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->handle(Illuminate\Http\Request::capture());

echo "--- Verifying Auto-Assign via Controller ---\n";

// 1. Setup Users
$admin = User::whereHas('roles', fn($q) => $q->where('name', 'admin'))->first();
if (!$admin) {
    die("Admin not found. Run migrations/seeders.\n");
}
Auth::login($admin);

// 2. Prepare Request Data (Simulating Form Submission WITHOUT assigned_to_user_id)
$data = [
    'student_name' => 'Auto Assign Test',
    'phone_number' => '9988776655',
    'status' => 'New',
    'source' => 'Website',
];

// 3. Instantiate Controller & Service
$controller = new EnquiryController();
$service = new LeadDistributionService();

// 4. Mock Request
$request = Request::create('/admin/enquiries', 'POST', $data);
$request->setUserResolver(fn() => $admin);

// 5. Execute Store Method using Reflection to bypass Route/Middleware if needed, 
//    but strictly we want to call the method. 
//    However, calling controller method directly requires resolving dependencies.
//    Laravel's App::call is best.

echo "Submitting Enquiry without 'assigned_to_user_id'...\n";
try {
    // We capture the redirect response
    $response = app()->call([$controller, 'store'], ['request' => $request, 'leadDistribution' => $service]);

    // Check if Enquiry was created
    $enquiry = Enquiry::where('phone_number', '9988776655')->latest()->first();

    if ($enquiry) {
        echo "Enquiry Created ID: {$enquiry->id}\n";
        echo "Assigned To User ID: " . ($enquiry->assigned_to_user_id ?? 'NULL') . "\n";

        if ($enquiry->assigned_to_user_id) {
            echo "SUCCESS: Auto-assigned to User {$enquiry->assigned_to_user_id}.\n";
        } else {
            echo "FAILURE: Assigned to NULL.\n";
            echo "Note: If only 1 user exists and logic fails, it falls back to Auth::id() which is {$admin->id}.\n";
        }
    } else {
        echo "FAILURE: Enquiry not found in DB.\n";
    }

} catch (\Exception $e) {
    echo "EXCEPTION: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
}
