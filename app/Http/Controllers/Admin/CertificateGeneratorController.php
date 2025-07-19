<?php
namespace App\Http\Controllers\Admin;
use Illuminate\Support\Str;
use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\CertificateTemplate;
use Illuminate\Http\Request;
use PDF; // Make sure to use the PDF facade

class CertificateGeneratorController extends Controller
{
    // Show the generator form
    public function showForm()
    {
        $students = Student::orderBy('name')->get();
        $templates = CertificateTemplate::orderBy('name')->get();
        return view('admin.certificate_generator.show', compact('students', 'templates'));
    }

    // Generate the PDF
    public function generate(Request $request)
    {
        $request->validate([
            'student_id' => 'required|exists:students,id',
            'template_id' => 'required|exists:certificate_templates,id',
        ]);

        $student = Student::with('batch.course')->findOrFail($request->student_id);
        $template = CertificateTemplate::findOrFail($request->template_id);

        // This is the core logic: replacing placeholders with real data
        $content = self::renderCertificate($student, $template->body);

        // Load the final HTML into the PDF generator
        $pdf = PDF::loadHtml($content);

        // Set paper size to A4 portrait
        $pdf->setPaper('a4', 'portrait');

        // Offer the PDF as a download
        $fileName = Str::slug($student->name . '-' . $template->name) . '.pdf';
        return $pdf->stream($fileName);
        // 🔥 Fire the webhook event
            event(new \App\Events\CertificateGenerated(
                $student, 
                $request->certificate_type, 
                $certificateNumber
            ));
    }

    // A helper function to replace all placeholders
    public static function renderCertificate(Student $student, $content)
    {
        $replacements = [
            '[student_name]' => e($student->name),
            '[enrollment_number]' => e($student->enrollment_number),
            '[course_name]' => e($student->batch->course->name ?? 'N/A'),
            '[batch_name]' => e($student->batch->name ?? 'N/A'),
            '[issue_date]' => now()->format('F j, Y'),
            '[college_name]' => e(setting('college_name', 'My College')),
            '[college_logo_url]' => setting('college_logo') ? asset('storage/' . setting('college_logo')) : '',
        ];

        return str_replace(array_keys($replacements), array_values($replacements), $content);
    }
}