<?php

use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\Enquiry;
use App\Http\Controllers\Admin\EnquiryController;
use Illuminate\Http\Request;

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->handle(Request::capture());

echo "\n--- Debugging 403 Access Issue ---\n";

// 1. Setup Admin User (Role: admin)
$admin = User::firstOrCreate(['email' => 'admin@example.com'], [
    'name' => 'Admin User',
    'password' => bcrypt('password')
]);
$admin->syncRoles(['admin']);
echo "User: {$admin->name} (ID: {$admin->id}), Roles: " . implode(',', $admin->getRoleNames()->toArray()) . "\n";

// 2. Setup Counsellor User
$counselor = User::firstOrCreate(['email' => 'counselor@example.com'], [
    'name' => 'Counselor User',
    'password' => bcrypt('password')
]);
$counselor->syncRoles(['counselor']);

// 3. Create Enquiry assigned to Counselor
$enquiry = Enquiry::create([
    'student_name' => 'Test Access',
    'phone_number' => '9999999999',
    'assigned_to_user_id' => $counselor->id
]);
echo "Enquiry ID: {$enquiry->id}, Assigned To: {$counselor->id}\n";

// 4. Permission Check
Auth::login($admin);
echo "User has 'manage admissions'? " . ($admin->can('manage admissions') ? 'YES' : 'NO') . "\n";
echo "User has 'view backend'? " . ($admin->can('view backend') ? 'YES' : 'NO') . "\n";

$controller = new EnquiryController();

try {
    echo "Attempting Access as Admin...\n";
    $controller->show($enquiry);
    echo "Access GRANTED (Success).\n";
} catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
    if ($e->getStatusCode() === 403) {
        echo "Access DENIED (403 Forbidden).\n";
    } else {
        echo "Error: " . $e->getMessage() . "\n";
    }
}

// 5. Test with 'college-admin' just in case
$collegeAdmin = User::firstOrCreate(['email' => 'college@example.com'], [
    'name' => 'College Admin',
    'password' => bcrypt('password')
]);
$collegeAdmin->syncRoles(['college-admin']);

Auth::login($collegeAdmin);
echo "User: {$collegeAdmin->name}, Roles: " . implode(',', $collegeAdmin->getRoleNames()->toArray()) . "\n";

try {
    echo "Attempting Access as College Admin...\n";
    $controller->show($enquiry);
    echo "Access GRANTED (Success).\n";
} catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
    echo "Access DENIED (Expected 403).\n";
}

echo "Check storage/logs/laravel.log for details.\n";
