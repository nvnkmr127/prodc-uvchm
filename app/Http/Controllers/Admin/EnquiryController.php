<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Enquiry;
use App\Models\Course;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Spatie\Activitylog\Models\Activity;
use App\Models\Admission;
use Carbon\Carbon;
use App\Services\LeadDistributionService;
use Illuminate\Support\Facades\Cache;
// Add these imports
use App\Imports\EnquiriesImport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Log;


class EnquiryController extends Controller
{
    private function getStats(array $filters = [])
    {
        $user = Auth::user();
        $isAdmin = $user->hasAnyRole(['admin', 'super-admin', 'Admin', 'Super-admin']);

        // We disable caching temporarily to resolve "not changing" issues with complex filters
        // $cacheKey = 'enquiry_stats_' . md5(serialize($filters) . $user->id);
        
        $statsQuery = Enquiry::selectRaw('status, count(*) as count');

        // 1. Apply Visibility (Match index exactly)
        if (!$isAdmin) {
            $statsQuery->where('assigned_to_user_id', $user->id);
        } elseif (!empty($filters['assigned_to_user_id'])) {
            $assignedTo = (array)$filters['assigned_to_user_id'];
            $statsQuery->whereIn('assigned_to_user_id', $assignedTo);
        }

        // 2. Apply Dynamic Filters (Match index exactly)
        if (!empty($filters['course_id'])) {
            $courseIds = (array)$filters['course_id'];
            $statsQuery->whereIn('course_id', $courseIds);
        }

        if (!empty($filters['source'])) {
            $sources = (array)$filters['source'];
            $statsQuery->whereIn('source', $sources);
        }

        if (!empty($filters['start_date'])) {
            $statsQuery->where('created_at', '>=', $filters['start_date'] . ' 00:00:00');
        }
        if (!empty($filters['end_date'])) {
            $statsQuery->where('created_at', '<=', $filters['end_date'] . ' 23:59:59');
        }

        if (!empty($filters['search'])) {
            $term = $filters['search'];
            $statsQuery->where(function ($q) use ($term) {
                $q->where('student_name', 'LIKE', '%' . $term . '%')
                    ->orWhere('phone_number', 'LIKE', '%' . $term . '%');
            });
        }

        // 3. Execution & Aggregation
        $stats = $statsQuery->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        // Calculate Total from the resulting distribution counts
        $total = array_sum($stats);

        return [
            'New' => $stats['New'] ?? 0,
            'Contacted' => $stats['Contacted'] ?? 0,
            'Interested' => $stats['Interested'] ?? 0,
            'Next Year' => $stats['Interested Next Year'] ?? 0,
            'Not Interested' => $stats['Not Interested'] ?? 0,
            'Admitted' => $stats['Admitted'] ?? 0,
            'Follow-up' => $stats['Follow-up'] ?? 0,
            'Total' => $total
        ];
    }

    public function index(Request $request)
    {
        // VISIBILITY FIX: Restrict Non-Admins to see only their assigned enquiries
        $user = Auth::user();
        $isAdmin = $user->hasAnyRole(['admin', 'super-admin', 'Admin', 'Super-admin']);

        // --- 1. Calculate Stats (Universal Filter Application) ---
        // Pass all request inputs to get filtered stats
        $counts = $this->getStats($request->all());

        // --- 2. Build Query ---
        // Select only enquiry columns to avoid overwriting data (like id) from joined tables
        $query = Enquiry::with('course', 'assignedTo')
            ->select('enquiries.*');

        // Apply Visibility for List
        if (!$isAdmin) {
            $query->where('assigned_to_user_id', $user->id);
        }

        // Search Logic
        if ($request->filled('search')) {
            $searchTerm = $request->search;
            $query->where(function ($q) use ($searchTerm) {
                $q->where('enquiries.student_name', 'LIKE', '%' . $searchTerm . '%')
                    ->orWhere('enquiries.phone_number', 'LIKE', '%' . $searchTerm . '%');
            });
        }

        // Status Filter
        if ($request->filled('status')) {
            $status = (array)$request->status;
            $query->whereIn('enquiries.status', $status);
        } else {
            // Default: Hide 'Not Interested' unless searching
            if (!$request->filled('search')) {
                $query->where('enquiries.status', '!=', 'Not Interested');
            }
        }

        // Other Filters
        if ($request->filled('course_id')) {
            $courseIds = (array)$request->course_id;
            $query->whereIn('enquiries.course_id', $courseIds);
        }
        if ($request->filled('assigned_to_user_id')) {
            $assignedTo = (array)$request->assigned_to_user_id;
            $query->whereIn('enquiries.assigned_to_user_id', $assignedTo);
        }
        if ($request->filled('source')) {
            $sources = (array)$request->source;
            $query->whereIn('enquiries.source', $sources);
        }

        // Date Filters (Optimized for indexes)
        if ($request->filled('start_date')) {
            $query->where('enquiries.created_at', '>=', $request->start_date . ' 00:00:00');
        }
        if ($request->filled('end_date')) {
            $query->where('enquiries.created_at', '<=', $request->end_date . ' 23:59:59');
        }

        // --- 3. Sorting Logic ---
        $sortField = $request->get('sort', 'next_follow_up_date');
        $sortDirection = $request->get('direction', 'asc');

        if ($sortField === 'course_name') {
            $query->leftJoin('courses', 'enquiries.course_id', '=', 'courses.id')
                ->orderBy('courses.name', $sortDirection);
        } elseif ($sortField === 'counselor_name') {
            // This JOIN caused the ambiguity error
            $query->leftJoin('users', 'enquiries.assigned_to_user_id', '=', 'users.id')
                ->orderBy('users.name', $sortDirection);
        } else {
            // Ensure standard sort uses table alias if needed (optional but safe)
            $query->orderBy('enquiries.' . $sortField, $sortDirection);
        }

        $perPage = $request->get('per_page', 25);
        $enquiries = $query->paginate($perPage)->withQueryString();
        $courses = Cache::remember('courses_list', now()->addMinutes(10), function () {
            return Course::orderBy('name')->pluck('name', 'id');
        });

        $counselors = Cache::remember('active_counselors', now()->addMinutes(10), function () {
            return User::whereHas('roles', function ($q) {
                $q->whereIn('name', ['admin', 'super-admin', 'college-admin', 'counselor']);
            })->where('status', 'active')->orderBy('name')->get(['id', 'name']);
        });


        // AJAX Response
        if ($request->ajax()) {
            // Use specific partial views if available, or just render sections
            // But since we are doing full page reload replacement for now, we'll return fragments
            // We need to create a partial for the table body to keep this clean, or just render inline

            // Let's assume we will move the table body to a partial or render it here.
            // For now, let's return HTML of the table loop directly using loop

            $tableHtml = view('admin.enquiries._table_body', compact('enquiries', 'counselors'))->render();
            $paginationHtml = $enquiries->links()->toHtml();

            return response()->json([
                'html' => $tableHtml,
                'pagination' => $paginationHtml,
                'stats' => $counts
            ]);
        }

        // 3. Prepare View Data
        // Combine static sources from model with any custom sources found in database
        $sources = Cache::remember('enquiry_sources', now()->addMinutes(10), function () {
            $staticSources = \App\Models\Enquiry::SOURCES;
            $dbSources = Enquiry::select('source')->whereNotNull('source')->distinct()->pluck('source', 'source')->toArray();
            return array_merge($staticSources, $dbSources);
        });


        $isFacebookView = session('is_facebook_view', false) || $request->input('source') === 'Social Media';

        return view('admin.enquiries.index', compact(
            'enquiries',
            'courses',
            'counselors',
            'counts',
            'sources',
            'isFacebookView'
        ));
    }

    public function facebookLeads(Request $request)
    {
        // Force the source to Social Media (Facebook)
        $request->merge(['source' => 'Social Media']);
        
        // Let the index method handle the rest of the logic (stats, query, pagination)
        // We set a flag so the view can show "Facebook Leads" instead of "Enquiries"
        session()->flash('is_facebook_view', true);
        
        return $this->index($request);
    }

    public function create()
    {
        $courses = Course::orderBy('name')->get();

        // FETCH FIX: Get Admins, Staff, and Counselors
        $counselors = User::whereHas('roles', function ($q) {
            $q->whereIn('name', ['counselor', 'College-admin', 'admin', 'super-admin', 'staff']);
        })->where('status', 'active')->orderBy('name')->get();

        return view('admin.enquiries.create', compact('courses', 'counselors'));
    }

    public function store(Request $request, LeadDistributionService $leadDistribution)
    {
        $validated = $request->validate([
            'student_name' => 'required|string|max:255',
            'phone_number' => 'required|string|min:10',
            'address' => 'nullable|string',
            'course_id' => 'nullable|exists:courses,id',
            'source' => 'nullable|string|max:255',
            'referral_name' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'assigned_to_user_id' => 'nullable|exists:users,id',
        ]);

        // Smart Duplicate Check before create
        $cleanPhone = preg_replace('/[^0-9]/', '', $validated['phone_number']);
        $searchSuffix = strlen($cleanPhone) >= 10 ? substr($cleanPhone, -10) : $cleanPhone;

        if (!empty($searchSuffix)) {
            $duplicateEnquiry = Enquiry::where('phone_number', 'LIKE', "%{$searchSuffix}")->first();
            if ($duplicateEnquiry) {
                return response()->json([
                    'success' => false,
                    'message' => "❌ Duplicate Record: A lead already exists for this number ({$duplicateEnquiry->student_name})."
                ], 422);
            }

            $duplicateStudent = \App\Models\Student::where('student_mobile', 'LIKE', "%{$searchSuffix}")
                ->orWhere('father_mobile', 'LIKE', "%{$searchSuffix}")
                ->first();
            if ($duplicateStudent) {
                return response()->json([
                    'success' => false,
                    'message' => "❌ Registered Student: This number is already registered to student '{$duplicateStudent->name}'."
                ], 422);
            }
        }

        // Get the next counselor ID from the service if not manually assigned
        $assignedTo = $validated['assigned_to_user_id'] ?? $leadDistribution->getNextCounselorId() ?? Auth::id();

        // Remove assigned_to_user_id from validated data to avoid duplication
        unset($validated['assigned_to_user_id']);

        $enquiry = Enquiry::create($validated + [
            'assigned_to_user_id' => $assignedTo,
            'status' => 'New'
        ]);


        // Handle AJAX request
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Enquiry logged successfully and assigned.',
                'redirect' => route('admin.enquiries.edit', $enquiry)
            ]);
        }

        return redirect()->route('admin.enquiries.edit', $enquiry)->with('success', 'Enquiry logged successfully and assigned.');
    }

    // Ensure this exists in EnquiryController.php
    public function quickUpdate(Request $request, Enquiry $enquiry)
    {
        $validated = $request->validate([
            'field' => 'required|in:assigned_to_user_id,next_follow_up_date,status,source',
            'value' => 'nullable',
            'filter_assigned_to' => 'nullable|exists:users,id' // Helper for stats
        ]);

        $field = $validated['field'];
        $value = $validated['value'];

        if ($field === 'next_follow_up_date') {
            $value = $value ? \Carbon\Carbon::parse($value)->format('Y-m-d') : null;
            // Auto status update
            if ($value && $enquiry->status === 'New') {
                $enquiry->status = 'Contacted';
            }
        }

        $enquiry->$field = $value;
        $enquiry->save();

        // Get updated stats for frontend with filter
        $counts = $this->getStats(['assigned_to_user_id' => $request->filter_assigned_to]);

        return response()->json(['success' => true, 'message' => 'Updated successfully', 'stats' => $counts]);
    }

    public function edit(Enquiry $enquiry)
    {
        // VISIBILITY FIX
        $user = Auth::user();
        $isAdmin = $user->hasAnyRole(['admin', 'super-admin', 'Admin', 'Super-admin']);

        // Default Deny
        if (!$isAdmin && $enquiry->assigned_to_user_id != $user->id) {
            abort(403, 'Unauthorized access to this enquiry.');
        }

        $enquiry->load('followUps.user');
        $courses = Course::orderBy('name')->get();

        // FETCH FIX: Get relevant users + include the current assignee even if they lost the role
        $counselors = User::whereHas('roles', function ($q) {
            $q->whereIn('name', ['admin', 'super-admin', 'college-admin']);
        })
            ->orWhere('id', $enquiry->assigned_to_user_id) // Always include current assignee
            ->orderBy('name')
            ->get()
            ->unique('id'); // Remove duplicates

        // Activity Log Logic
        $activities = Activity::where(function ($query) use ($enquiry) {
            $query->where('subject_type', Enquiry::class)
                ->where('subject_id', $enquiry->id);
        })
            ->orWhere(function ($query) use ($enquiry) {
                $query->where('subject_type', \App\Models\FollowUp::class)
                    ->whereIn('subject_id', $enquiry->followUps->pluck('id'));
            })
            ->get();

        $timeline = collect($enquiry->followUps)->concat($activities)->sortByDesc('created_at');

        return view('admin.enquiries.edit', compact('enquiry', 'timeline', 'courses', 'counselors'));
    }

    public function update(Request $request, Enquiry $enquiry)
    {
        $validated = $request->validate([
            'student_name' => 'required|string|max:255',
            'phone_number' => 'required|string|max:20', // Changed from strict regex to string
            'email' => 'nullable|email|max:255', // Added email
            'address' => 'nullable|string',
            'course_id' => 'nullable|exists:courses,id',
            'source' => 'nullable|string|max:255',
            'referral_name' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'status' => 'required|in:New,Contacted,Interested,Not Interested,Follow-up,Admitted,Interested Next Year',
            'next_follow_up_date' => 'nullable|date',
            'assigned_to_user_id' => 'nullable|exists:users,id',
            'gender' => 'nullable|in:Male,Female,Other',
            'date_of_birth' => 'nullable|date',
            'education_qualification' => 'nullable|string|max:255',
        ]);

        $enquiry->update($validated);
        return redirect()->back()->with('success', 'Enquiry updated successfully.');
    }

    public function ajaxSearch(Request $request)
    {
        $query = $request->get('query');

        if (empty($query)) {
            return response()->json([]);
        }

        $resultsQuery = Enquiry::with('course')
            ->where(function ($q) use ($query) {
                $q->where('student_name', 'LIKE', "%{$query}%")
                    ->orWhere('phone_number', 'LIKE', "%{$query}%");
            });

        $user = Auth::user();
        $isAdmin = $user->hasAnyRole(['admin', 'super-admin', 'Admin', 'Super-admin']);

        // Default Deny: If not an admin, restrict to self
        if (!$isAdmin) {
            $resultsQuery->where('assigned_to_user_id', $user->id);
        }

        $results = $resultsQuery->limit(10)
            ->get()
            ->map(function ($enquiry) {
                return [
                    'id' => $enquiry->id,
                    'name' => $enquiry->student_name,
                    'phone' => $enquiry->phone_number,
                    'course' => $enquiry->course->name ?? 'N/A',
                    'status' => $enquiry->status,
                    'avatar' => strtoupper(substr($enquiry->student_name, 0, 1))
                ];
            });

        return response()->json($results);
    }


    public function addFollowUp(Request $request, Enquiry $enquiry)
    {
        $request->validate([
            'notes' => 'required|string',
            'outcome' => 'nullable|string|max:255',
            'next_follow_up_date' => 'nullable|date|after_or_equal:today',
        ]);

        // Create the note
        $followUp = $enquiry->followUps()->create([
            'notes' => $request->notes,
            'outcome' => $request->outcome,
            'user_id' => Auth::id(),
        ]);

        // Update the next follow-up date if provided
        if ($request->filled('next_follow_up_date')) {
            // Only advance to 'Contacted' if status is still 'New' — do not degrade more advanced statuses
            $advancedStatuses = ['Interested', 'Follow-up', 'Interested Next Year', 'Admitted'];
            $newStatus = in_array($enquiry->status, $advancedStatuses) ? $enquiry->status : 'Contacted';
            $enquiry->update([
                'next_follow_up_date' => $request->next_follow_up_date,
                'status' => $newStatus
            ]);
        }

        // [NEW] Handle AJAX Request for "No Reload" functionality
        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Follow-up added successfully',
                'data' => [
                    'user_name' => Auth::user()->name,
                    'created_at' => $followUp->created_at->format('d M, h:i A'),
                    'notes' => nl2br(e($followUp->notes)),
                    'outcome' => $followUp->outcome,
                    'date_formatted' => $request->filled('next_follow_up_date') ? Carbon::parse($request->next_follow_up_date)->format('d M Y') : null
                ]
            ]);
        }

        return redirect()->back()->with('success', 'Follow-up note added and schedule updated.');
    }

    public function convertToAdmission(Enquiry $enquiry)
    {
        if ($enquiry->status === 'Admitted') {
            return redirect()->back()->with('error', 'This enquiry has already been converted to an admission.');
        }
        return redirect()->route('admin.admissions.create', ['enquiry' => $enquiry->id]);
    }

    // In EnquiryController.php

    public function checkMobile(Request $request)
    {
        $phone = $request->query('phone');
        $currentId = $request->query('id');

        if (!$phone)
            return response()->json(['status' => 'success']);

        // Normalize the phone number for smarter checking (get last 10 digits)
        $cleanPhone = preg_replace('/[^0-9]/', '', $phone);
        $searchSuffix = strlen($cleanPhone) >= 10 ? substr($cleanPhone, -10) : $cleanPhone;

        if (empty($searchSuffix)) {
             return response()->json(['status' => 'success']);
        }

        // 1. Check Students (Student Mobile OR Father Mobile)
        $student = \App\Models\Student::where(function ($q) use ($searchSuffix) {
            $q->where('student_mobile', 'LIKE', "%{$searchSuffix}")
                ->orWhere('father_mobile', 'LIKE', "%{$searchSuffix}");
        })->first();

        if ($student) {
            $matchingField = (str_ends_with($student->student_mobile, $searchSuffix)) ? 'Student' : 'Father';
            return response()->json([
                'status' => 'error',
                'message' => "❌ Found in Students ({$matchingField}): {$student->name} (Batch: " . ($student->batch->name ?? 'N/A') . ")"
            ]);
        }

        // 2. Check Enquiries
        $query = Enquiry::where('phone_number', 'LIKE', "%{$searchSuffix}");
        if ($currentId) {
            $query->where('id', '!=', $currentId);
        }
        $existing = $query->first();

        if ($existing) {
            $counselor = $existing->assignedTo->name ?? 'Unassigned';
            return response()->json([
                'status' => 'error',
                'message' => "❌ Duplicate Enquiry: {$existing->student_name} (Assigned to: {$counselor})"
            ]);
        }


        // 3. Check Staff (Existing Logic)
        $staff = User::where('email', $phone)->orWhere('name', $phone)->first();
        if ($staff) {
            return response()->json([
                'status' => 'error',
                'message' => "❌ Number belongs to Staff: {$staff->name}"
            ]);
        }

        return response()->json(['status' => 'success']);
    }

    public function show(Enquiry $enquiry)
    {
        // VISIBILITY FIX
        $user = Auth::user();
        $isAdmin = $user->hasAnyRole(['admin', 'super-admin', 'Admin', 'Super-admin']);

        // Default Deny
        if (!$isAdmin && $enquiry->assigned_to_user_id != $user->id) {
            abort(403, 'Unauthorized access to this enquiry.');
        }

        $enquiry->load('followUps.user');
        $courses = Course::orderBy('name')->get();

        // FETCH FIX: Get relevant users + include the current assignee even if they lost the role
        $counselors = User::whereHas('roles', function ($q) {
            $q->whereIn('name', ['admin', 'super-admin', 'college-admin']);
        })
            ->orWhere('id', $enquiry->assigned_to_user_id) // Always include current assignee
            ->orderBy('name')
            ->get()
            ->unique('id'); // Remove duplicates

        // 3. Build Timeline (Existing Logic)
        $activities = Activity::where(function ($query) use ($enquiry) {
            $query->where('subject_type', Enquiry::class)
                ->where('subject_id', $enquiry->id);
        })
            ->orWhere(function ($query) use ($enquiry) {
                $query->where('subject_type', \App\Models\FollowUp::class)
                    ->whereIn('subject_id', $enquiry->followUps->pluck('id'));
            })
            ->get();

        $timeline = collect($enquiry->followUps)->concat($activities)->sortByDesc('created_at');

        // 4. Pass 'counselors' and 'courses' to the view
        return view('admin.enquiries.modal_show', compact('enquiry', 'timeline', 'courses', 'counselors'));
    }
    // ADD THIS NEW METHOD
    public function bulkDelete(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:enquiries,id',
        ]);

        try {
            Enquiry::whereIn('id', $request->ids)->delete();
            return response()->json(['success' => true, 'message' => 'Selected enquiries deleted successfully.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Error deleting items.']);
        }
    }

    public function bulkAssign(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:enquiries,id',
            'assigned_to_user_id' => 'required|exists:users,id',
            'filter_assigned_to' => 'nullable|exists:users,id' // Helper for stats
        ]);

        Enquiry::whereIn('id', $request->ids)->update([
            'assigned_to_user_id' => $request->assigned_to_user_id,
            'updated_at' => now() // Update timestamp
        ]);

        // Get updated stats for frontend
        $counts = $this->getStats(['assigned_to_user_id' => $request->filter_assigned_to]);

        return response()->json(['success' => true, 'message' => 'Counselor assigned successfully.', 'stats' => $counts]);
    }

    // Ensure your destroy method looks like this (it likely already does)
    public function destroy(Enquiry $enquiry)
    {
        $user = Auth::user();
        $isAdmin = $user->hasAnyRole(['admin', 'super-admin', 'Admin', 'Super-admin']);

        if (!$isAdmin && $enquiry->assigned_to_user_id != $user->id) {
            abort(403, 'Unauthorized access to this enquiry.');
        }

        $enquiry->delete();
        return redirect()->route('admin.enquiries.index')->with('success', 'Enquiry deleted successfully.');
    }
    public function import(Request $request, LeadDistributionService $leadDistribution)
    {
        $request->validate([
            'file' => 'required|mimes:csv,xlsx,xls|max:5120', // 5 MB limit
            'assigned_to_user_id' => 'nullable|exists:users,id',
            'default_source' => 'nullable|string|max:255'
        ]);

        try {
            // 1. Create the instance explicitly (Pass service)
            $import = new EnquiriesImport(
                $request->assigned_to_user_id,
                $leadDistribution,
                $request->input('default_source')
            );

            // 2. Run the import
            Excel::import($import, $request->file('file'));

            // 3. Check results
            if ($import->importedCount === 0) {
                return back()->with('error', "Import finished but 0 records were added. Skipped: {$import->skippedCount}. Check your CSV headers (must be: name OR student_name, and mobile_number).");
            }

            return back()->with('success', "Success! Imported: {$import->importedCount}, Skipped: {$import->skippedCount} (Duplicates/Invalid).");

        } catch (\Maatwebsite\Excel\Validators\ValidationException $e) {
            $failures = $e->failures();
            $errorMsg = "Row " . $failures[0]->row() . ": " . $failures[0]->errors()[0];
            return back()->with('error', "Validation Error: " . $errorMsg);
        } catch (\Exception $e) {
            return back()->with('error', 'System Error: ' . $e->getMessage());
        }
    }

    public function downloadSample()
    {
        $csvData = "name,mobile_number,address,email,course,source,notes\nJohn Doe,9876543210,123 Main St Mumbai,john@example.com,B.Tech,Walk-in,Urgent follow up\nJane Smith,9988776655,456 Park Ave Delhi,,MBA,,";

        return response($csvData)
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', 'attachment; filename="enquiry_import_sample.csv"');
    }
}