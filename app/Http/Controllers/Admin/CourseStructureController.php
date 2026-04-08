<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\CourseTerm;
use Illuminate\Http\Request;

class CourseStructureController extends Controller
{
    public function show(Course $course)
    {
        return view('admin.courses.structure', compact('course'));
    }

    public function store(Request $request, Course $course)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:Academic,Training',
            'sequence' => 'required|integer|min:1',
        ]);

        // Check if sequence already exists for this course
        $existingTerm = $course->terms()->where('sequence', $validated['sequence'])->first();
        if ($existingTerm) {
            return redirect()->back()
                ->withErrors(['sequence' => 'A term with sequence '.$validated['sequence'].' already exists.'])
                ->withInput();
        }

        // Create the term
        $course->terms()->create($validated);

        return redirect()->back()->with('success', 'Term "'.$validated['name'].'" added to course structure successfully.');
    }

    public function destroy(CourseTerm $term)
    {
        $term->delete();

        return redirect()->back()->with('success', 'Term removed from course structure.');
    }

    public function getTermsForDropdown(Course $course)
    {
        // Return only the 'Academic' type terms as a JSON response
        $terms = $course->terms()->where('type', 'Academic')->get();

        return response()->json($terms);
    }
}
