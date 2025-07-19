<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Holiday;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class HolidayController extends Controller
{
    public function index()
    {
        $holidays = Holiday::orderBy('date')->get();
        return view('admin.holidays.index', compact('holidays'));
    }

    public function create()
    {
        return view('admin.holidays.create');
    }

    public function store(Request $request)
    {
        // FIXED: Single validation with all necessary rules
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'date' => 'required|date|unique:holidays,date',
            'description' => 'nullable|string|max:500',
        ]);

        Holiday::create($validated);
        return redirect()->route('admin.holidays.index')->with('success', 'Holiday created successfully.');
    }

    public function edit(Holiday $holiday)
    {
        return view('admin.holidays.edit', compact('holiday'));
    }

    public function update(Request $request, Holiday $holiday)
    {
        // FIXED: Single validation with proper unique rule ignoring current record
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'date' => ['required', 'date', Rule::unique('holidays')->ignore($holiday->id)],
            'description' => 'nullable|string|max:500',
        ]);

        $holiday->update($validated);
        return redirect()->route('admin.holidays.index')->with('success', 'Holiday updated successfully.');
    }

    public function destroy(Holiday $holiday)
    {
        $holiday->delete();
        return redirect()->route('admin.holidays.index')->with('success', 'Holiday deleted successfully.');
    }
}