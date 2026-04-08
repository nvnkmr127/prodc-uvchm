<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Subject;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SubjectController extends Controller
{
    public function index()
    {
        // Updated to include users relationship for faculty count
        $subjects = Subject::with(['courses', 'users'])
            ->withCount(['courses', 'users as faculty_count'])
            ->latest()
            ->get();

        return view('admin.subjects.index', compact('subjects'));
    }

    public function create()
    {
        return view('admin.subjects.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:255|unique:subjects',
            'requires_lab' => 'nullable|boolean', // Validate it's a boolean
        ]);

        // If the checkbox is not checked, it won't be in the request. So we default to false (0).
        $validated['requires_lab'] = $request->has('requires_lab');

        Subject::create($validated);

        return redirect()->route('admin.subjects.index')->with('success', 'Subject created successfully.');
    }

    public function show(Subject $subject)
    {
        // Not used in this simple CRUD, but can be used for a details page
    }

    public function edit(Subject $subject)
    {
        return view('admin.subjects.edit', compact('subject'));
    }

    /**
     * Get faculty data for AJAX requests
     */
    public function getFacultyData(Subject $subject)
    {
        try {
            $assigned = $subject->users;
            $allStaff = \App\Models\User::role('staff')->get();

            return response()->json([
                'success' => true,
                'assigned' => $assigned,
                'available' => $allStaff,
                'message' => 'Simple version working',
            ]);

        } catch (\Exception $e) {
            \Log::error('getFacultyData error: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Assign faculty to subject
     */
    public function assignFaculty(Request $request, Subject $subject)
    {
        $request->validate([
            'faculty_id' => 'required|exists:users,id',
        ]);

        try {
            $faculty = \App\Models\User::findOrFail($request->faculty_id);

            // Check if faculty has staff role
            if (! $faculty->hasRole('staff')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Selected user is not a faculty member',
                ], 400);
            }

            // Check if already assigned
            if ($subject->users()->where('user_id', $faculty->id)->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Faculty is already assigned to this subject',
                ], 400);
            }

            // Assign faculty to subject
            $subject->users()->attach($faculty->id);

            return response()->json([
                'success' => true,
                'message' => "Faculty {$faculty->name} assigned to {$subject->name} successfully",
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error assigning faculty: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove faculty from subject
     */
    public function removeFaculty(Request $request, Subject $subject)
    {
        $request->validate([
            'faculty_id' => 'required|exists:users,id',
        ]);

        try {
            $faculty = \App\Models\User::findOrFail($request->faculty_id);

            // Check if faculty is assigned to this subject
            if (! $subject->users()->where('user_id', $faculty->id)->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Faculty is not assigned to this subject',
                ], 400);
            }

            // Remove faculty from subject
            $subject->users()->detach($faculty->id);

            return response()->json([
                'success' => true,
                'message' => "Faculty {$faculty->name} removed from {$subject->name} successfully",
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error removing faculty: '.$e->getMessage(),
            ], 500);
        }
    }

    public function update(Request $request, Subject $subject)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => ['nullable', 'string', 'max:255', Rule::unique('subjects')->ignore($subject->id)],
            'requires_lab' => 'nullable|boolean',
        ]);

        // If the checkbox is not checked, it won't be in the request. So we default to false (0).
        $validated['requires_lab'] = $request->has('requires_lab');

        $subject->update($validated);

        return redirect()->route('admin.subjects.index')->with('success', 'Subject updated successfully.');
    }

    public function destroy(Subject $subject)
    {
        $subject->delete();

        return redirect()->route('admin.subjects.index')->with('success', 'Subject deleted successfully.');
    }
}
