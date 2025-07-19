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

class EnquiryController extends Controller
{
    public function index(Request $request)
    {
        // --- Data for Stat Cards ---
        $newEnquiriesCount = Enquiry::where('status', 'New')->count();
        $todaysFollowUpsCount = Enquiry::whereDate('next_follow_up_date', Carbon::today())->count();
        $recentlyAdmittedCount = Enquiry::where('status', 'Admitted')->where('updated_at', '>=', Carbon::now()->subDays(7))->count();

        // --- Data for the main table ---
        $query = Enquiry::with('course', 'assignedTo');

        // Apply filters
        if ($request->filled('search')) {
            $query->where(function($q) use ($request) {
                $q->where('student_name', 'LIKE', '%' . $request->search . '%')
                  ->orWhere('phone_number', 'LIKE', '%' . $request->search . '%');
            });
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('course_id')) {
            $query->where('course_id', $request->course_id);
        }

        $enquiries = $query->latest()->get();
        $courses = Course::orderBy('name')->get();
        
        // Get counselors for assignment dropdown (if needed in view)
        $counselors = User::whereHas('roles', function($q) {
            $q->where('name', 'counselor');
        })->orderBy('name')->get();

        return view('admin.enquiries.index', compact(
            'enquiries',
            'courses',
            'counselors',
            'newEnquiriesCount',
            'todaysFollowUpsCount',
            'recentlyAdmittedCount'
        ));
    }

    public function create()
    {
        $courses = Course::orderBy('name')->get();
        
        // Get counselors for assignment dropdown
        $counselors = User::whereHas('roles', function($q) {
            $q->where('name', 'counselor');
        })->orderBy('name')->get();
        
        return view('admin.enquiries.create', compact('courses', 'counselors'));
    }

    public function store(Request $request, LeadDistributionService $leadDistribution)
    {
        $validated = $request->validate([
            'student_name' => 'required|string|max:255',
            'phone_number' => 'required|string|max:20',
            'address' => 'nullable|string',
            'course_id' => 'nullable|exists:courses,id',
            'source' => 'nullable|string|max:255',
            'referral_name' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'assigned_to_user_id' => 'nullable|exists:users,id', // Allow manual assignment
        ]);

        // Get the next counselor ID from the service if not manually assigned
        $assignedTo = $validated['assigned_to_user_id'] ?? $leadDistribution->getNextCounselorId() ?? Auth::id();

        // Remove assigned_to_user_id from validated data to avoid duplication
        unset($validated['assigned_to_user_id']);

        $enquiry = Enquiry::create($validated + [
            'assigned_to_user_id' => $assignedTo
            
            
        ]);
        // 🔥 Fire the webhook event
        event(new \App\Events\EnquiryCreated($enquiry));
        
        // Redirect directly to the manage page for the new enquiry
        return redirect()->route('admin.enquiries.edit', $enquiry)->with('success', 'Enquiry logged successfully and assigned.');
    }

    public function edit(Enquiry $enquiry)
    {
        // Eager load the follow-ups and the users who made them
        $enquiry->load('followUps.user');
        
        // Get courses for editing
        $courses = Course::orderBy('name')->get();
        
        // Get counselors for reassignment
        $counselors = User::whereHas('roles', function($q) {
            $q->where('name', 'counselor');
        })->orderBy('name')->get();
    
        // Get all activity related to this enquiry from the activity log
        $activities = Activity::where(function($query) use ($enquiry) {
                // Find activities where the subject is the enquiry record itself
                $query->where('subject_type', Enquiry::class)
                      ->where('subject_id', $enquiry->id);
            })
            ->orWhere(function($query) use ($enquiry) {
                // Also find activities where the subject is one of this enquiry's follow-up notes
                $query->where('subject_type', \App\Models\FollowUp::class)
                      ->whereIn('subject_id', $enquiry->followUps->pluck('id'));
            })
            ->get();
    
        // Merge the manually added follow-up notes and the automatic system activities into a single collection
        $timeline = collect($enquiry->followUps)->concat($activities)->sortByDesc('created_at');
    
        return view('admin.enquiries.edit', compact('enquiry', 'timeline', 'courses', 'counselors'));
    }

    public function update(Request $request, Enquiry $enquiry)
    {
        $validated = $request->validate([
            'student_name' => 'sometimes|required|string|max:255',
            'phone_number' => 'sometimes|required|string|max:20',
            'address' => 'nullable|string',
            'course_id' => 'nullable|exists:courses,id',
            'source' => 'nullable|string|max:255',
            'referral_name' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'status' => 'required|in:New,Contacted,Interested,Not Interested,Admitted',
            'next_follow_up_date' => 'nullable|date',
            'assigned_to_user_id' => 'nullable|exists:users,id',
        ]);

        $enquiry->update($validated);
        return redirect()->back()->with('success', 'Enquiry updated successfully.');
    }

    public function addFollowUp(Request $request, Enquiry $enquiry)
    {
        $request->validate(['notes' => 'required|string']);

        $enquiry->followUps()->create([
            'notes' => $request->notes,
            'user_id' => Auth::id(),
        ]);

        return redirect()->back()->with('success', 'Follow-up note added successfully.');
    }

    public function convertToAdmission(Enquiry $enquiry)
    {
        // Safety check to prevent converting an already admitted enquiry
        if ($enquiry->status === 'Admitted' || $enquiry->admission()->exists()) {
            return redirect()->back()->with('error', 'This enquiry has already been converted to an admission.');
        }
    
        // Redirect to the new admission finalization form, passing the enquiry ID
        return redirect()->route('admin.admissions.create', ['enquiry' => $enquiry->id]);
    }
    private function getAvailableCounselors()
{
    try {
        return User::role('counselor')->get();
    } catch (\Exception $e) {
        return User::all(); // Fallback
    }
}

    public function show(Enquiry $enquiry)
    {
        // Load related data
        $enquiry->load('course', 'assignedTo', 'followUps.user');
        
        // Get timeline data similar to edit method
        $activities = Activity::where(function($query) use ($enquiry) {
                $query->where('subject_type', Enquiry::class)
                      ->where('subject_id', $enquiry->id);
            })
            ->orWhere(function($query) use ($enquiry) {
                $query->where('subject_type', \App\Models\FollowUp::class)
                      ->whereIn('subject_id', $enquiry->followUps->pluck('id'));
            })
            ->get();
    
        $timeline = collect($enquiry->followUps)->concat($activities)->sortByDesc('created_at');
        
        return view('admin.enquiries.show', compact('enquiry', 'timeline'));
    }

    public function destroy(Enquiry $enquiry)
    {
        $enquiry->delete();
        return redirect()->route('admin.enquiries.index')->with('success', 'Enquiry deleted successfully.');
    }
}