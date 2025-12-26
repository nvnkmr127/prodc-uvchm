<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AcademicYearController extends Controller
{
    public function index() { $years = AcademicYear::orderBy('start_date', 'desc')->get(); return view('admin.academic_years.index', compact('years')); }
    public function create() { return view('admin.academic_years.create'); }
    public function edit(AcademicYear $academicYear) { return view('admin.academic_years.edit', compact('academicYear')); }

  public function store(Request $request)
{
    $validated = $request->validate([
        'name' => 'required|string|unique:academic_years',
        'start_date' => 'required|date',
        'end_date' => 'required|date|after:start_date',
        'is_current' => 'boolean'
    ]);
    
    DB::transaction(function () use ($validated) {
        if (isset($validated['is_current']) && $validated['is_current']) {
            AcademicYear::query()->update(['is_current' => false]);
        }
        
        AcademicYear::create([
            'name' => $validated['name'],
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],
            'is_current' => $validated['is_current'] ?? false
        ]); // ✅ SECURE
    });
    return redirect()->route('admin.academic-years.index')->with('success', 'Academic Year created.');
}
// Add this new method inside the AcademicYearController class
public function switch(Request $request)
{
    $request->validate([
        'academic_year_id' => 'required|exists:academic_years,id'
    ]);

    // Store the selected academic year ID in the session
    session(['selected_academic_year_id' => $request->academic_year_id]);

    return redirect()->back()->with('success', 'Academic year has been switched.');
}

    public function update(Request $request, AcademicYear $academicYear)
    {
        $request->validate(['name' => 'required|string|unique:academic_years,name,'.$academicYear->id, 'start_date' => 'required|date', 'end_date' => 'required|date|after:start_date']);
        DB::transaction(function () use ($request, $academicYear) {
            if ($request->has('is_current')) { AcademicYear::query()->update(['is_current' => false]); }
            $validated = $request->validate([
            'name' => 'required|string|max:255|unique:academic_years,name,' . $academicYear->id,
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'is_current' => 'boolean'
        ]);
        
        $academicYear->update(array_merge($validated, [
            'is_current' => $request->has('is_current')
        ]));
        });
        return redirect()->route('admin.academic-years.index')->with('success', 'Academic Year updated.');
    }

    public function destroy(AcademicYear $academicYear) { $academicYear->delete(); return redirect()->route('admin.academic-years.index')->with('success', 'Academic Year deleted.'); }

    public function setCurrent(Request $request, AcademicYear $academicYear)
    {
        DB::transaction(function () use ($academicYear) {
            AcademicYear::query()->update(['is_current' => false]);
            $academicYear->update(['is_current' => true]);
        });

        return redirect()->route('admin.academic-years.index')
            ->with('success', 'Academic year set as current successfully.');
    }
}