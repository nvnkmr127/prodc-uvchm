<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Subject;
use Illuminate\Http\Request;

class CourseSubjectController extends Controller
{
    public function edit(Course $course)
    {
        // Get all subjects available in the system
        $allSubjects = Subject::orderBy('name')->get();

        // Pass the specific course and the list of all subjects to the view
        return view('admin.courses.manage_subjects', compact('course', 'allSubjects'));
    }

    public function update(Request $request, Course $course)
    {
        // The sync() method is a powerful Laravel helper.
        // It updates the bridge table based on the array of checkbox IDs sent from the form.
        // It will add any new ones and remove any that were unchecked.
        $course->subjects()->sync($request->input('subjects', []));

        return redirect()->route('admin.courses.index')
                         ->with('success', 'Subjects for ' . $course->name . ' updated successfully.');
    }
}