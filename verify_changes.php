<?php

use App\Models\User;
use App\Models\Enquiry;
use App\Models\Course;
use App\Services\LeadDistributionService;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Ensure roles exist
// Cleanup existing test data (Prevent Duplicates if previous run failed)
DB::statement('SET FOREIGN_KEY_CHECKS=0;');
DB::table('model_has_roles')->truncate();
DB::table('users')->whereIn('email', ['c1@test.com', 'c2@test.com', 'admin@test.com', 'staff@test.com', 'ca@test.com', 'stats@test.com', 'other@test.com'])->delete();
DB::table('roles')->whereIn('name', ['counselor', 'admin', 'staff', 'college-admin'])->delete();
DB::table('courses')->where('name', 'Test Course')->delete();
DB::table('admission_follow_ups')->truncate();
DB::table('admissions')->truncate();
DB::table('follow_ups')->truncate();
DB::table('enquiries')->truncate();
DB::statement('SET FOREIGN_KEY_CHECKS=1;');

// Ensure roles exist
if (!Role::where('name', 'counselor')->exists())
    Role::create(['name' => 'counselor']);
if (!Role::where('name', 'admin')->exists())
    Role::create(['name' => 'admin']);

// Create Dummy Users
$counselor1 = User::create(['name' => 'Counselor One', 'email' => 'c1@test.com', 'password' => bcrypt('password'), 'status' => 'active']);
$counselor1->assignRole('counselor');

$counselor2 = User::create(['name' => 'Counselor Two', 'email' => 'c2@test.com', 'password' => bcrypt('password'), 'status' => 'active']);
$counselor2->assignRole('counselor');

$admin = User::create(['name' => 'Admin User', 'email' => 'admin@test.com', 'password' => bcrypt('password'), 'status' => 'active']);
$admin->assignRole('admin');

// Create Dummy Course
$course = Course::create(['name' => 'Test Course', 'duration_in_years' => 3]);

// 2. Create Enquiries
echo "Creating Enquiries...\n";
$enquiry1 = Enquiry::create([
    'student_name' => 'Student One',
    'phone_number' => '1234567890',
    'email' => 'student1@example.com',
    'assigned_to_user_id' => $counselor1->id,
    'status' => 'New',
    'created_at' => now()->subDays(2), // 2 days ago
]);

$enquiry2 = Enquiry::create([
    'student_name' => 'నవీన్ (Naveen)', // Telugu Unicode Test
    'phone_number' => '0987654321',
    'email' => 'naveen@example.com',
    'assigned_to_user_id' => $admin->id,
    'status' => 'Interested',
    'created_at' => now(), // Today
]);

echo "--- Testing Visibility ---\n";

// Test Counselor 1 Visibility
Auth::login($counselor1);
$query = Enquiry::with('course', 'assignedTo')->select('enquiries.*');
$user = Auth::user();
$isAdmin = $user->hasAnyRole(['admin', 'super-admin', 'college-admin', 'College-admin', 'Admin', 'Super-admin']);
if (!$isAdmin) {
    $query->where('assigned_to_user_id', $user->id);
}
echo "Counselor 1 sees: " . $query->count() . " enquiries (Expected: 1)\n";

// Test Admin Visibility
Auth::login($admin);
$query = Enquiry::with('course', 'assignedTo')->select('enquiries.*');
$user = Auth::user();
$isAdmin = $user->hasAnyRole(['admin', 'super-admin', 'college-admin', 'College-admin', 'Admin', 'Super-admin']);
if (!$isAdmin) {
    $query->where('assigned_to_user_id', $user->id);
}
echo "Admin sees: " . $query->count() . " enquiries (Expected: 2)\n";

// Test Staff
if (!Role::where('name', 'staff')->exists())
    Role::create(['name' => 'staff']);
$staffUser = User::create(['name' => 'Staff User', 'email' => 'staff@test.com', 'password' => bcrypt('password'), 'status' => 'active']);
$staffUser->assignRole('staff');
Auth::login($staffUser);
$query = Enquiry::with('course', 'assignedTo')->select('enquiries.*');
$user = Auth::user();
$isAdmin = $user->hasAnyRole(['admin', 'super-admin', 'college-admin', 'College-admin', 'Admin', 'Super-admin']);
if (!$isAdmin) {
    $query->where('assigned_to_user_id', $user->id);
}
echo "Staff sees: " . $query->count() . " enquiries (Expected: 0)\n";

// Test College Admin
if (!Role::where('name', 'college-admin')->exists())
    Role::create(['name' => 'college-admin']);
$caUser = User::create(['name' => 'CA User', 'email' => 'ca@test.com', 'password' => bcrypt('password'), 'status' => 'active']);
$caUser->assignRole('college-admin');
Auth::login($caUser);
$query = Enquiry::with('course', 'assignedTo')->select('enquiries.*');
$user = Auth::user();
$isAdmin = $user->hasAnyRole(['admin', 'super-admin', 'Admin', 'Super-admin']); // Excludes college-admin
if (!$isAdmin) {
    $query->where('assigned_to_user_id', $user->id);
}
echo "College Admin sees: " . $query->count() . " enquiries (Expected: 0)\n";


echo "\n--- Testing Stats & Updates ---\n";
// Use Staff User for restricted stats test
$eStats = Enquiry::create(['student_name' => 'Stats Student', 'phone_number' => '5555555555', 'status' => 'New', 'assigned_to_user_id' => $staffUser->id]);
Enquiry::create(['student_name' => 'Other Student', 'phone_number' => '6666666666', 'status' => 'New', 'assigned_to_user_id' => $counselor1->id]);

Auth::login($staffUser);
// Use Controller directly
$controller = new \App\Http\Controllers\Admin\EnquiryController();
$request = \Illuminate\Http\Request::create('/admin/enquiries', 'GET');
$view = $controller->index($request);
$data = $view->getData();
$stats = $data['counts'];
echo "Staff Stats 'New': " . $stats['New'] . " (Expected: 1)\n"; // 1 assigned to staff, 1 other hidden


// AJAX Update Test (Staff - Own Stats)
$reqAjax = \Illuminate\Http\Request::create("/admin/enquiries/{$eStats->id}/quick-update", 'POST', ['field' => 'status', 'value' => 'Contacted']);
$reqAjax->headers->set('X-Requested-With', 'XMLHttpRequest');
$resp = $controller->quickUpdate($reqAjax, $eStats);
$json = $resp->getData(true);
echo "AJAX Staff Stats 'Contacted': " . ($json['stats']['Contacted'] ?? 'N/A') . " (Expected: 1)\n";

// AJAX Update Test (Admin - Filtered Stats)
Auth::login($admin);
$enqAdminCheck = Enquiry::create(['student_name' => 'Admin Check', 'phone_number' => '7777777777', 'status' => 'New', 'assigned_to_user_id' => $counselor1->id]);
// Ensure another enquiry exists for Counselor 2 (Global Count should be > 1)
Enquiry::create(['student_name' => 'Global Check', 'phone_number' => '8888888888', 'status' => 'New', 'assigned_to_user_id' => $counselor2->id]);

// Admin updates Counselor 1's lead, while filtering by Counselor 1
$reqFilter = \Illuminate\Http\Request::create("/admin/enquiries/{$enqAdminCheck->id}/quick-update", 'POST', [
    'field' => 'status',
    'value' => 'Interested',
    'filter_assigned_to' => $counselor1->id // Filter is active
]);
$reqFilter->headers->set('X-Requested-With', 'XMLHttpRequest');
$respFilter = $controller->quickUpdate($reqFilter, $enqAdminCheck);
$jsonFilter = $respFilter->getData(true);

echo "AJAX Admin Filtered 'Interested': " . ($jsonFilter['stats']['Interested'] ?? 0) . " (Expected: 1)\n";
echo "AJAX Admin Filtered 'Total': " . ($jsonFilter['stats']['Total'] ?? 0) . " (Expected: ~2 - existing + new)\n";
// Note: Counselor 1 has: 1 (New->Interested from this test) + 1 (Existing 'Student 1' from line 49) + 1 ('Other Student' line 106) = 3 total. 
// Actually line 49: 'New', line 106: 'New'. 
// So for Counselor 1: 
// - Student 1 (New)
// - Other Student (New)
// - Admin Check (New -> Interested)
// Total for C1 = 3.
// Total for Global = C1(3) + C2(1: Student 2) + Staff(1: Stats Student) + C2(1: Global Check) = 6.
echo "AJAX Admin Filtered Total Check: " . $jsonFilter['stats']['Total'] . " (Expected: 3)\n";



echo "\n--- Testing Round Robin ---\n";
// Reset last assigned
DB::table('settings')->updateOrInsert(['key' => 'last_assigned_counselor_id'], ['value' => null]);

$service = new LeadDistributionService();

echo "\n--- Testing Full AJAX & Filters ---\n";
Auth::login($admin);
// Prepare Data
$courseA = Course::create(['name' => 'Data Science', 'duration_in_years' => 2]);
$courseB = Course::create(['name' => 'AI Engineering', 'duration_in_years' => 2]);

$studentA = Enquiry::create(['student_name' => 'Alice Data', 'phone_number' => '1112223333', 'course_id' => $courseA->id, 'status' => 'New', 'assigned_to_user_id' => $counselor1->id]);
$studentA->created_at = now()->subDays(10);
$studentA->updated_at = now()->subDays(10);
$studentA->save();

$studentB = Enquiry::create(['student_name' => 'Bob AI', 'phone_number' => '4445556666', 'course_id' => $courseB->id, 'status' => 'New', 'assigned_to_user_id' => $counselor1->id]);
$studentB->created_at = now()->subDays(2);
$studentB->updated_at = now()->subDays(2);
$studentB->save();

// 1. Test Course Filter via AJAX
$reqCourse = \Illuminate\Http\Request::create('/admin/enquiries', 'GET', ['course_id' => $courseA->id]);
$reqCourse->headers->set('X-Requested-With', 'XMLHttpRequest');
$respCourse = $controller->index($reqCourse);
$jsonCourse = $respCourse->getData(true);

echo "AJAX Filter (Course A) Count: " . substr_count($jsonCourse['html'], 'Alice Data') . " (Expected: 1)\n";
echo "AJAX Filter (Course A) Excludes Bob: " . (strpos($jsonCourse['html'], 'Bob AI') === false ? 'Yes' : 'No') . " (Expected: Yes)\n";
echo "AJAX Filter (Course A) 'New' Stat: " . $jsonCourse['stats']['New'] . " (Expected: 1)\n"; // Only Alice is New in Course A

// 2. Test Date Filter via AJAX
$reqDate = \Illuminate\Http\Request::create('/admin/enquiries', 'GET', [
    'start_date' => now()->subDays(5)->format('Y-m-d'), // Should exclude Alice (10 days ago)
    'end_date' => now()->format('Y-m-d')
]);
$reqDate->headers->set('X-Requested-With', 'XMLHttpRequest');
$respDate = $controller->index($reqDate);
$jsonDate = $respDate->getData(true);

echo "AJAX Filter (Date -5 days) Count: " . substr_count($jsonDate['html'], 'Bob AI') . " (Expected: 1)\n";
echo "AJAX Filter (Date -5 days) Excludes Alice: " . (strpos($jsonDate['html'], 'Alice Data') === false ? 'Yes' : 'No') . " (Expected: Yes)\n";

// 3. Test Search Filter via AJAX (Drill-down stats)
$reqSearch = \Illuminate\Http\Request::create('/admin/enquiries', 'GET', ['search' => 'Bob']);
$reqSearch->headers->set('X-Requested-With', 'XMLHttpRequest');
$respSearch = $controller->index($reqSearch);
$jsonSearch = $respSearch->getData(true);

echo "AJAX Search ('Bob') Count: " . substr_count($jsonSearch['html'], 'Bob AI') . " (Expected: 1)\n";
echo "AJAX Search ('Bob') 'New' Stat: " . $jsonSearch['stats']['New'] . " (Expected: 1)\n"; // Only Bob

echo "Verification Complete.\n";

