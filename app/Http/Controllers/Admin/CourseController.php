<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class CourseController extends Controller
{
    public function index()
    {
        // Get courses with batches and students - global scopes will filter automatically
        $courses = \App\Models\Course::with(['batches.students'])
            ->withCount('batches') // Automatically filtered by HasAcademicYear trait on Batch
            ->withSum('feeStructures', 'total_amount')
            ->orderBy('created_at', 'desc')
            ->get();

        // Calculate total students from loaded batches (already filtered by global scope)
        foreach ($courses as $course) {
            $course->students_count = $course->batches->sum(function ($batch) {
                return $batch->students->count();
            });
        }

        return view('admin.courses.index', compact('courses'));
    }

    public function create()
    {
        return view('admin.courses.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:courses,name',
            'enrollment_prefix' => 'nullable|string|max:10',
            'code' => 'nullable|string|max:50|unique:courses,code',
            'duration_in_years' => 'required|numeric|min:0.5|max:10',
            'duration_months' => 'required|integer|min:1|max:120',
            'max_batch_size' => 'required|integer|min:1|max:200',
            'description' => 'nullable|string|max:1000',
        ]);

        Course::create($validated);

        return redirect()->route('admin.courses.index')->with('success', 'Course created successfully.');
    }

    public function edit(Course $course)
    {
        return view('admin.courses.edit', compact('course'));
    }

    public function update(Request $request, Course $course)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('courses')->ignore($course->id)],
            'enrollment_prefix' => 'nullable|string|max:10',
            'code' => ['nullable', 'string', 'max:50', Rule::unique('courses')->ignore($course->id)],
            'duration_in_years' => 'required|numeric|min:0.5|max:10',
            'duration_months' => 'required|integer|min:1|max:120',
            'max_batch_size' => 'required|integer|min:1|max:200',
            'description' => 'nullable|string|max:1000',
        ]);

        $course->update($validated);

        return redirect()->route('admin.courses.index')->with('success', 'Course updated successfully.');
    }

    public function destroy(Course $course)
    {
        // Check if course has related data before deletion
        if ($course->batches()->count() > 0) {
            return redirect()->route('admin.courses.index')
                ->with('error', 'Cannot delete course. It has associated batches.');
        }

        $course->delete();

        return redirect()->route('admin.courses.index')->with('success', 'Course deleted successfully.');
    }
}
