<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FacultySubjectController extends Controller
{
    /**
     * Show the form for editing faculty subjects
     */
    public function edit(User $user)
    {
        // Use the Spatie method to check the role
        if (! $user->hasRole('staff')) {
            abort(404, 'Faculty member not found');
        }

        try {
            // Get all active subjects ordered by name
            $allSubjects = Subject::where('is_active', true)
                ->orderBy('name')
                ->get();

            // Load the faculty's current subjects to avoid N+1 queries
            $user->load('subjects');

            // Check if subjects relationship exists
            if (! method_exists($user, 'subjects')) {
                Log::error('User model missing subjects relationship', [
                    'user_id' => $user->id,
                ]);

                return redirect()->route('admin.faculty.index')
                    ->with('error', 'User model is missing subjects relationship. Please contact administrator.');
            }

            return view('admin.faculty.manage_subjects', [
                'faculty' => $user,
                'allSubjects' => $allSubjects,
            ]);

        } catch (\Exception $e) {
            Log::error('Error loading faculty subjects edit page', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->route('admin.faculty.index')
                ->with('error', 'Unable to load subjects management page. '.$e->getMessage());
        }
    }

    /**
     * Update faculty subject assignments
     */
    public function update(Request $request, User $user)
    {
        // Use the Spatie method to check the role
        if (! $user->hasRole('staff')) {
            abort(404, 'Faculty member not found');
        }

        // Validate the incoming request
        $request->validate([
            'subjects' => 'nullable|array',
            'subjects.*' => 'exists:subjects,id',
        ]);

        DB::beginTransaction();

        try {
            // Get the subjects array (empty array if none selected)
            $subjectIds = $request->input('subjects', []);

            // Validate that all subjects exist and are active
            if (! empty($subjectIds)) {
                $validSubjects = Subject::whereIn('id', $subjectIds)
                    ->where('is_active', true)
                    ->pluck('id')
                    ->toArray();

                if (count($validSubjects) !== count($subjectIds)) {
                    throw new \Exception('Some selected subjects are invalid or inactive.');
                }

                $subjectIds = $validSubjects;
            }

            // Get old subjects for logging
            $oldSubjects = $user->subjects()->pluck('subjects.id')->toArray();

            // Log the update for audit purposes
            Log::info('Updating faculty subject assignments', [
                'faculty_id' => $user->id,
                'faculty_name' => $user->name,
                'old_subjects' => $oldSubjects,
                'new_subjects' => $subjectIds,
                'updated_by' => auth()->id(),
            ]);

            // Sync the subjects (this will add new ones and remove unchecked ones)
            $user->subjects()->sync($subjectIds);

            DB::commit();

            // Reload the relationship to get fresh data
            $user->load('subjects');

            $assignedCount = count($subjectIds);
            $message = $assignedCount > 0
                ? "Successfully assigned {$assignedCount} subject(s) to {$user->name}."
                : "Removed all subject assignments from {$user->name}.";

            return redirect()->route('admin.faculty.index')
                ->with('success', $message);

        } catch (\Exception $e) {
            DB::rollback();

            Log::error('Error updating faculty subject assignments', [
                'user_id' => $user->id,
                'subjects' => $request->input('subjects', []),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->back()
                ->with('error', 'Failed to update subject assignments: '.$e->getMessage())
                ->withInput();
        }
    }

    /**
     * Show faculty subject assignments (optional - for viewing only)
     */
    public function show(User $user)
    {
        if (! $user->hasRole('staff')) {
            abort(404, 'Faculty member not found');
        }

        try {
            $user->load(['subjects' => function ($query) {
                $query->orderBy('name');
            }]);

            return view('admin.faculty.subjects_show', [
                'faculty' => $user,
            ]);

        } catch (\Exception $e) {
            Log::error('Error loading faculty subjects show page', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return redirect()->route('admin.faculty.index')
                ->with('error', 'Unable to load faculty subjects.');
        }
    }
}
