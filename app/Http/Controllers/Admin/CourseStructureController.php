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
        $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:Academic,Training',
            'sequence' => 'required|integer',
        ]);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date'
        ]);
        $course->terms()->create($validated);
        return redirect()->back()->with('success', 'Term added to course structure.');
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