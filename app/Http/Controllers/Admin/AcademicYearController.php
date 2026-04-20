<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AcademicYearController extends Controller
{
    public function index()
    {
        $years = AcademicYear::withCount(['batches', 'students'])
            ->orderBy('start_date', 'desc')
            ->get();

        return view('admin.academic_years.index', compact('years'));
    }

    public function create()
    {
        return view('admin.academic_years.create');
    }

    public function edit(AcademicYear $academicYear)
    {
        return view('admin.academic_years.edit', compact('academicYear'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|unique:academic_years',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'is_current' => 'boolean',
            'auto_switch_enabled' => 'boolean',
        ]);

        if (AcademicYear::checkOverlap($validated['start_date'], $validated['end_date'])) {
            return redirect()->back()->withInput()->with('error', 'Dates overlap with an existing Academic Year.');
        }

        DB::transaction(function () use ($validated) {
            if (isset($validated['is_current']) && $validated['is_current']) {
                AcademicYear::query()->update(['is_current' => false]);
            }

            AcademicYear::create([
                'name' => $validated['name'],
                'start_date' => $validated['start_date'],
                'end_date' => $validated['end_date'],
                'is_current' => $validated['is_current'] ?? false,
                'auto_switch_enabled' => $validated['auto_switch_enabled'] ?? false,
            ]);
        });

        return redirect()->route('admin.academic-years.index')->with('success', 'Academic Year created successfully.');
    }

    public function switch(Request $request)
    {
        $request->validate([
            'academic_year_id' => 'required|exists:academic_years,id',
        ]);

        session(['selected_academic_year_id' => $request->academic_year_id]);

        return redirect()->back()->with('success', 'Viewing academic year switched successfully.');
    }

    public function update(Request $request, AcademicYear $academicYear)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:academic_years,name,'.$academicYear->id,
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'is_current' => 'boolean',
            'auto_switch_enabled' => 'boolean',
        ]);

        if (AcademicYear::checkOverlap($validated['start_date'], $validated['end_date'], $academicYear->id)) {
            return redirect()->back()->withInput()->with('error', 'Dates overlap with an existing Academic Year.');
        }

        DB::transaction(function () use ($request, $validated, $academicYear) {
            $isCurrent = $request->has('is_current');

            if ($isCurrent) {
                AcademicYear::query()->update(['is_current' => false]);
            }

            $academicYear->update(array_merge($validated, [
                'is_current' => $isCurrent,
                'auto_switch_enabled' => $request->has('auto_switch_enabled'),
            ]));
        });

        return redirect()->route('admin.academic-years.index')->with('success', 'Academic Year updated successfully.');
    }

    public function destroy(AcademicYear $academicYear)
    {
        // Check for related data before deletion
        $hasBatches = \App\Models\Batch::where('academic_year_id', $academicYear->id)->exists();
        $hasStudents = \App\Models\Student::where('academic_year_id', $academicYear->id)->exists();

        if ($hasBatches || $hasStudents) {
            return redirect()->back()->with('error', 'Cannot delete this academic year as it has related records (batches or students).');
        }

        if ($academicYear->is_current) {
            return redirect()->back()->with('error', 'The current active academic year cannot be deleted.');
        }

        $academicYear->delete();

        return redirect()->route('admin.academic-years.index')->with('success', 'Academic Year removed successfully.');
    }

    public function setCurrent(Request $request, AcademicYear $academicYear)
    {
        DB::transaction(function () use ($academicYear) {
            AcademicYear::query()->update(['is_current' => false]);
            $academicYear->update(['is_current' => true]);
        });

        // Sync session when setting as current system-wide
        session(['selected_academic_year_id' => $academicYear->id]);

        return redirect()->route('admin.academic-years.index')
            ->with('success', 'Academic year ['.$academicYear->name.'] set as active system-wide.');
    }
}
