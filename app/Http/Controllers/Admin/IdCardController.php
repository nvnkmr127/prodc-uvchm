<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Batch;
use App\Models\Student;
use App\Models\IdCardTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class IdCardController extends Controller
{
    /**
     * Show the ID Card Generator page.
     */
    public function show(Request $request)
    {
        $batches = Batch::with('course')->get();
        $templates = IdCardTemplate::all(); // Get all available templates
        $students = null;
        $selectedTemplate = null;

        if ($request->filled('batch_id') && $request->filled('template_id')) {
            // Get all students for the selected batch
            $students = Student::where('batch_id', $request->batch_id)
                                ->with(['batch.course'])
                                ->get();
            
            // Get the selected template's content
            $selectedTemplate = IdCardTemplate::findOrFail($request->template_id);
        }

        return view('admin.id_cards.show', compact('batches', 'templates', 'students', 'selectedTemplate'));
    }

    /**
     * A helper function to replace placeholders in the template with real data.
     */
    public static function renderCard(Student $student, $templateContent)
    {
        // Define all possible replacements
        $replacements = [
            '[student_name]' => e($student->name),
            '[student_photo_url]' => $student->photo ? Storage::url($student->photo) : 'https://ui-avatars.com/api/?name=' . urlencode($student->name) . '&size=150',
            '[enrollment_number]' => e($student->enrollment_number),
            '[course_name]' => e($student->batch->course->name ?? 'N/A'),
            '[batch_name]' => e($student->batch->name ?? 'N/A'),
            '[batch_end_date]' => $student->batch ? \Carbon\Carbon::parse($student->batch->end_date)->format('M Y') : 'N/A',
            '[college_name]' => e(setting('college_name', 'My College')),
            '[college_address]' => e(setting('college_address', 'College Address')),
            '[college_logo_url]' => setting('college_logo') ? Storage::url(setting('college_logo')) : '',
        ];

        // Find all placeholders like [some_key] and replace them
        return str_replace(array_keys($replacements), array_values($replacements), $templateContent);
    }
}
