<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CertificateTemplate;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
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

        $pdf = self::generatePdfInstance($student, $template);

        // Offer the PDF as a download
        $fileName = Str::slug($student->name.'-'.$template->name).'.pdf';

        return $pdf->stream($fileName);
    }

    /**
     * Show Bulk Generation Form
     */
    public function showBulkForm()
    {
        $batches = \App\Models\Batch::with('course')->where('status', 'active')->get();
        $templates = CertificateTemplate::orderBy('name')->get();

        return view('admin.certificate_generator.bulk', compact('batches', 'templates'));
    }

    /**
     * Handle Bulk Generation
     */
    public function bulkGenerate(Request $request)
    {
        $request->validate([
            'batch_id' => 'required|exists:batches,id',
            'template_id' => 'required|exists:certificate_templates,id',
        ]);

        $batch = \App\Models\Batch::with('students')->findOrFail($request->batch_id);
        $template = CertificateTemplate::findOrFail($request->template_id);
        $students = $batch->students;

        if ($students->isEmpty()) {
            return back()->with('error', 'No students found in this batch.');
        }

        // Create a temporary ZIP file
        $zipFileName = 'certificates-'.Str::slug($batch->name).'-'.time().'.zip';
        $zipFilePath = storage_path('app/public/'.$zipFileName);

        $zip = new \ZipArchive;
        if ($zip->open($zipFilePath, \ZipArchive::CREATE) !== true) {
            return back()->with('error', 'Could not create ZIP file.');
        }

        foreach ($students as $student) {
            // Generate PDF content
            $pdf = self::generatePdfInstance($student, $template);
            $content = $pdf->output();

            // Determine filename
            // Default: [student_name]-[template_name]
            $format = $template->filename_format ?? '[student_name]-[template_name]';
            $filename = self::renderCertificate($student, $format); // Reuse render logic for filename
            $filename = Str::slug($filename).'.pdf';

            // Add to ZIP
            $zip->addFromString($filename, $content);
        }

        $zip->close();

        // return response()->download($zipFilePath)->deleteFileAfterSend(true);
        // Note: deleteFileAfterSend doesn't always work reliably with all server configs, but it's standard Laravel.
        return response()->download($zipFilePath)->deleteFileAfterSend(true);
    }

    /**
     * Helper to create PDF instance with all settings applied
     */
    private static function generatePdfInstance(Student $student, CertificateTemplate $template)
    {
        // 1. Render Content
        $htmlContent = self::renderCertificate($student, $template->body);

        // 2. Wrap HTML with specific styling for backgrounds and margins
        // We handle margins via CSS @page or body padding if DOMPDF's setPaper doesn't handle custom margins well enough.
        // However, DOMPDF allows set_option or rendering with styles.
        // Easiest: Use a wrapper view or inline styles.

        $bgStyle = '';
        if ($template->content_type === 'full' && $template->background_image) {
            $bgPath = public_path('storage/'.$template->background_image);
            // Ensure file exists
            if (file_exists($bgPath)) {
                // Convert to base64 to ensure it loads in PDF
                $type = pathinfo($bgPath, PATHINFO_EXTENSION);
                $data = file_get_contents($bgPath);
                $base64 = 'data:image/'.$type.';base64,'.base64_encode($data);

                $bgStyle = "
                    background-image: url('{$base64}');
                    background-size: cover;
                    background-repeat: no-repeat;
                    background-position: center;
                ";
            }
        }

        $fullHtml = "
        <html>
        <head>
            <style>
                @page {
                    margin: {$template->margin_top}mm {$template->margin_right}mm {$template->margin_bottom}mm {$template->margin_left}mm;
                }
                body {
                    font-family: 'DejaVu Sans', sans-serif; /* Good for unicode */
                    {$bgStyle}
                }
                .certificate-content {
                    width: 100%;
                    height: 100%;
                }
            </style>
        </head>
        <body>
            <div class='certificate-content'>
                {$htmlContent}
            </div>
        </body>
        </html>
        ";

        // 3. Load PDF
        $pdf = PDF::loadHtml($fullHtml);

        // 4. Set Paper Size & Orientation
        $paperSize = $template->paper_size === 'custom' ? [0, 0, 595.28, 841.89] : $template->paper_size; // Default A4 if custom (logic for custom dims not in DB yet)
        $pdf->setPaper($paperSize, $template->orientation);

        return $pdf;
    }

    // A helper function to replace all placeholders
    public static function renderCertificate(Student $student, $content)
    {
        // Calculate Attendance Percentage (Simple Logic)
        $attendance = 'N/A';
        // If you have a method $student->getAttendancePercentage(), use it.
        // implementation: $attendance = $student->getAttendancePercentage() . '%';

        $replacements = [
            '[student_name]' => $student->name,
            '[enrollment_number]' => $student->enrollment_number,
            '[course_name]' => $student->batch->course->name ?? 'N/A',
            '[batch_name]' => $student->batch->name ?? 'N/A',
            '[father_name]' => $student->father_name ?? '',
            '[dob]' => $student->dob ? $student->dob->format('d-m-Y') : '',
            '[issue_date]' => now()->format('F j, Y'),
            '[college_name]' => setting('college_name', 'My College'),
            '[college_logo_url]' => setting('college_logo') ? asset('storage/'.setting('college_logo')) : '',

            // New dynamic fields
            '[attendance_percentage]' => $attendance,
            '[grade]' => 'A', // Placeholder logic
        ];

        return str_replace(array_keys($replacements), array_values($replacements), $content);
    }
}
