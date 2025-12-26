<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Spatie\Activitylog\Models\Activity;
use App\Models\User;

class ActivityLogController extends Controller
{
    public function index(Request $request)
    {
        $query = Activity::with(['causer', 'subject']);

        // Search functionality
        if ($request->filled('search')) {
            $searchTerm = $request->search;
            $query->where('description', 'LIKE', '%' . $searchTerm . '%')
                  ->orWhere('log_name', 'LIKE', '%' . $searchTerm . '%');
        }

        // Filter by user
        if ($request->filled('causer_id')) {
            $query->where('causer_id', $request->causer_id);
        }

        // Filter by log name (activity type)
        if ($request->filled('log_name')) {
            $query->where('log_name', $request->log_name);
        }

        // Filter by subject type
        if ($request->filled('subject_type')) {
            $query->where('subject_type', $request->subject_type);
        }

        // Filter by date range
        if ($request->filled('from_date')) {
            $query->whereDate('created_at', '>=', $request->from_date);
        }
        if ($request->filled('to_date')) {
            $query->whereDate('created_at', '<=', $request->to_date);
        }

        // Get paginated results
        $activities = $query->latest()->paginate(50)->withQueryString();

        // Get filter options
        $users = User::orderBy('name')->get(['id', 'name']);
        $logNames = Activity::distinct()->pluck('log_name')->filter();
        $subjectTypes = Activity::distinct()
            ->pluck('subject_type')
            ->filter()
            ->map(function($type) {
                return class_basename($type);
            });

        return view('admin.activity_log.index', compact(
            'activities',
            'users',
            'logNames',
            'subjectTypes'
        ));
    }

    public function destroy(Request $request)
    {
        $days = $request->input('days', 30);

        $deleted = Activity::where('created_at', '<', now()->subDays($days))->delete();

        return redirect()->back()->with('success', "Deleted {$deleted} old activity log entries.");
    }
}