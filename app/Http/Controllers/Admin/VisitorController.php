<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\Visitor;
use Illuminate\Http\Request;

class VisitorController extends Controller
{
    public function index()
    {
        $visitors = Visitor::latest()->paginate(25);
        return view('admin.visitors.index', compact('visitors'));
    }

    public function create()
    {
        return view('admin.visitors.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'visitor_name' => 'required|string|max:255',
            'phone_number' => 'required|string|max:20',
            'purpose_of_visit' => 'required|string|max:255',
            'check_in_time' => 'required|date',
            'notes' => 'nullable|string',
        ]);
        $validated = $request->validate([
            'visitor_name' => 'required|string|max:255',
            'phone_number' => 'nullable|string|max:255',
            'purpose_of_visit' => 'nullable|string|max:255',
            'check_in_time' => 'nullable|string|max:255',
            'check_out_time' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:255',
        ]);
        Visitor::create($validated);
        return redirect()->route('admin.visitors.index')->with('success', 'Visitor entry created.');
    }

    public function edit(Visitor $visitor)
    {
        return view('admin.visitors.edit', compact('visitor'));
    }

    public function update(Request $request, Visitor $visitor)
    {
        $request->validate([
            'visitor_name' => 'required|string|max:255',
            'phone_number' => 'required|string|max:20',
            'purpose_of_visit' => 'required|string|max:255',
            'check_in_time' => 'required|date',
            'check_out_time' => 'nullable|date|after_or_equal:check_in_time',
            'notes' => 'nullable|string',
        ]);
        $validated = $request->validate([
            'visitor_name' => 'required|string|max:255',
            'phone_number' => 'nullable|string|max:255',
            'purpose_of_visit' => 'nullable|string|max:255',
            'check_in_time' => 'nullable|string|max:255',
            'check_out_time' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:255',
        ]);
        $visitor->update($validated);
        return redirect()->route('admin.visitors.index')->with('success', 'Visitor entry updated.');
    }

    public function destroy(Visitor $visitor)
    {
        $visitor->delete();
        return redirect()->route('admin.visitors.index')->with('success', 'Visitor entry deleted.');
    }
}