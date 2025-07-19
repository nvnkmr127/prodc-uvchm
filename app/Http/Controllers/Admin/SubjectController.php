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
    // withCount efficiently counts the number of related courses and faculty for each subject
    $subjects = Subject::withCount(['courses', 'faculty'])->latest()->get();

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