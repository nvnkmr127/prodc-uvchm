<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Schema;

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
            $course->students_count = $course->batches->sum(function($batch) {
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
        // ✅ Get available columns from database
        $availableColumns = Schema::getColumnListing('courses');
        
        // ✅ Base validation rules
        $validationRules = [
            'name' => 'required|string|max:255|unique:courses,name',
            'description' => 'nullable|string|max:1000',
        ];
        
        // ✅ Add conditional validation rules based on available columns
        if (in_array('enrollment_prefix', $availableColumns)) {
            $validationRules['enrollment_prefix'] = 'nullable|string|max:10';
        }
        
        if (in_array('code', $availableColumns)) {
            $validationRules['code'] = 'nullable|string|max:50|unique:courses,code';
        }
        
        if (in_array('duration_in_years', $availableColumns)) {
            $validationRules['duration_in_years'] = 'required|numeric|min:0.5|max:10';
        }
        
        if (in_array('duration_months', $availableColumns)) {
            $validationRules['duration_months'] = 'required|integer|min:1|max:120';
        }
        
        if (in_array('max_batch_size', $availableColumns)) {
            $validationRules['max_batch_size'] = 'required|integer|min:1|max:200';
        }

        $validated = $request->validate($validationRules);
        
        // ✅ Filter validated data to only include existing columns
        $dataToSave = array_intersect_key($validated, array_flip($availableColumns));
        
        Course::create($dataToSave);
        return redirect()->route('admin.courses.index')->with('success', 'Course created successfully.');
    }
    
    public function edit(Course $course)
    {
        return view('admin.courses.edit', compact('course'));
    }

    public function update(Request $request, Course $course)
    {
        // ✅ Get available columns from database
        $availableColumns = Schema::getColumnListing('courses');
        
        // ✅ Base validation rules
        $validationRules = [
            'name' => ['required', 'string', 'max:255', Rule::unique('courses')->ignore($course->id)],
            'description' => 'nullable|string|max:1000',
        ];
        
        // ✅ Add conditional validation rules based on available columns
        if (in_array('enrollment_prefix', $availableColumns)) {
            $validationRules['enrollment_prefix'] = 'nullable|string|max:10';
        }
        
        if (in_array('code', $availableColumns)) {
            $validationRules['code'] = ['nullable', 'string', 'max:50', Rule::unique('courses')->ignore($course->id)];
        }
        
        if (in_array('duration_in_years', $availableColumns)) {
            $validationRules['duration_in_years'] = 'required|numeric|min:0.5|max:10';
        }
        
        if (in_array('duration_months', $availableColumns)) {
            $validationRules['duration_months'] = 'required|integer|min:1|max:120';
        }
        
        if (in_array('max_batch_size', $availableColumns)) {
            $validationRules['max_batch_size'] = 'required|integer|min:1|max:200';
        }

        $validated = $request->validate($validationRules);
        
        // ✅ Filter validated data to only include existing columns
        $dataToUpdate = array_intersect_key($validated, array_flip($availableColumns));
        
        $course->update($dataToUpdate);
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