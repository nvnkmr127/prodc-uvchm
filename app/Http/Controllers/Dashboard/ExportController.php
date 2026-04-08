<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Dashboard;
use App\Models\Widget;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class ExportController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('throttle:30,1'); // 30 requests per minute
    }

    /**
     * Export widget data
     */
    public function widget(Request $request, Widget $widget)
    {
        $request->validate([
            'format' => 'required|in:xlsx,csv,pdf',
            'instance_id' => 'nullable|string',
            'date_range' => 'nullable|array',
            'filters' => 'nullable|array',
        ]);

        $user = auth()->user();

        // Check permissions
        if (! $user->hasPermissionTo('export reports')) {
            return response()->json(['error' => 'Insufficient permissions'], 403);
        }

        try {
            $instanceId = $request->instance_id;
            $format = $request->format;
            $filters = $request->filters ?? [];

            // Get widget data
            $dataService = app(\App\Services\DashboardDataService::class);
            $data = $dataService->getWidgetData($user, $widget, $filters);

            // Generate export
            $filename = $this->generateWidgetExport($widget, $data, $format, $instanceId);

            return response()->json([
                'success' => true,
                'download_url' => route('dashboard.export.download', ['file' => $filename]),
                'filename' => $filename,
            ]);

        } catch (\Exception $e) {
            \Log::error('Widget export failed: '.$e->getMessage());

            return response()->json(['error' => 'Export failed'], 500);
        }
    }

    /**
     * Export entire dashboard
     */
    public function dashboard(Request $request, Dashboard $dashboard)
    {
        $request->validate([
            'format' => 'required|in:xlsx,pdf',
            'include_charts' => 'boolean',
            'date_range' => 'nullable|array',
        ]);

        $user = auth()->user();

        // Check permissions
        if (! $user->hasPermissionTo('export reports')) {
            return response()->json(['error' => 'Insufficient permissions'], 403);
        }

        try {
            $format = $request->format;
            $includeCharts = $request->boolean('include_charts', true);

            // Generate dashboard export
            $filename = $this->generateDashboardExport($dashboard, $user, $format, $includeCharts);

            return response()->json([
                'success' => true,
                'download_url' => route('dashboard.export.download', ['file' => $filename]),
                'filename' => $filename,
            ]);

        } catch (\Exception $e) {
            \Log::error('Dashboard export failed: '.$e->getMessage());

            return response()->json(['error' => 'Export failed'], 500);
        }
    }

    /**
     * Generate custom report
     */
    public function report(Request $request)
    {
        $request->validate([
            'report_type' => 'required|string',
            'format' => 'required|in:xlsx,csv,pdf',
            'parameters' => 'nullable|array',
            'date_range' => 'nullable|array',
        ]);

        $user = auth()->user();

        if (! $user->hasPermissionTo('generate reports')) {
            return response()->json(['error' => 'Insufficient permissions'], 403);
        }

        try {
            $reportType = $request->report_type;
            $format = $request->format;
            $parameters = $request->parameters ?? [];

            $filename = $this->generateCustomReport($reportType, $parameters, $format, $user);

            return response()->json([
                'success' => true,
                'download_url' => route('dashboard.export.download', ['file' => $filename]),
                'filename' => $filename,
            ]);

        } catch (\Exception $e) {
            \Log::error('Custom report export failed: '.$e->getMessage());

            return response()->json(['error' => 'Report generation failed'], 500);
        }
    }

    /**
     * Download exported file
     */
    public function download(Request $request, $file)
    {
        $user = auth()->user();

        // Validate filename and user access
        if (! $this->validateFileAccess($user, $file)) {
            abort(404);
        }

        $filePath = storage_path("app/exports/{$file}");

        if (! file_exists($filePath)) {
            abort(404);
        }

        return response()->download($filePath)->deleteFileAfterSend();
    }

    /**
     * Generate widget export file
     */
    private function generateWidgetExport(Widget $widget, array $data, string $format, ?string $instanceId): string
    {
        $timestamp = now()->format('Y-m-d_H-i-s');
        $filename = "widget_{$widget->slug}_{$timestamp}.{$format}";

        $export = new \App\Exports\WidgetDataExport($widget, $data, $instanceId);

        switch ($format) {
            case 'xlsx':
                Excel::store($export, "exports/{$filename}", 'local', \Maatwebsite\Excel\Excel::XLSX);
                break;
            case 'csv':
                Excel::store($export, "exports/{$filename}", 'local', \Maatwebsite\Excel\Excel::CSV);
                break;
            case 'pdf':
                // Generate PDF export
                $this->generateWidgetPDF($widget, $data, $filename);
                break;
        }

        return $filename;
    }

    /**
     * Generate dashboard export file
     */
    private function generateDashboardExport(Dashboard $dashboard, $user, string $format, bool $includeCharts): string
    {
        $timestamp = now()->format('Y-m-d_H-i-s');
        $filename = "dashboard_{$dashboard->slug}_{$timestamp}.{$format}";

        $dashboardService = app(\App\Services\DashboardService::class);
        $dashboardData = $dashboardService->getDashboardData($user);

        $export = new \App\Exports\DashboardExport($dashboard, $dashboardData, $includeCharts);

        switch ($format) {
            case 'xlsx':
                Excel::store($export, "exports/{$filename}", 'local', \Maatwebsite\Excel\Excel::XLSX);
                break;
            case 'pdf':
                $this->generateDashboardPDF($dashboard, $dashboardData, $filename, $includeCharts);
                break;
        }

        return $filename;
    }

    /**
     * Generate custom report
     */
    private function generateCustomReport(string $reportType, array $parameters, string $format, $user): string
    {
        $timestamp = now()->format('Y-m-d_H-i-s');
        $filename = "report_{$reportType}_{$timestamp}.{$format}";

        // Create report based on type
        $reportData = $this->getReportData($reportType, $parameters, $user);

        $export = new \App\Exports\CustomReportExport($reportType, $reportData, $parameters);

        switch ($format) {
            case 'xlsx':
                Excel::store($export, "exports/{$filename}", 'local', \Maatwebsite\Excel\Excel::XLSX);
                break;
            case 'csv':
                Excel::store($export, "exports/{$filename}", 'local', \Maatwebsite\Excel\Excel::CSV);
                break;
            case 'pdf':
                $this->generateReportPDF($reportType, $reportData, $filename);
                break;
        }

        return $filename;
    }

    /**
     * Get report data based on type
     */
    private function getReportData(string $reportType, array $parameters, $user): array
    {
        switch ($reportType) {
            case 'student_performance':
                return $this->getStudentPerformanceData($parameters, $user);
            case 'financial_summary':
                return $this->getFinancialSummaryData($parameters, $user);
            case 'attendance_report':
                return $this->getAttendanceReportData($parameters, $user);
            case 'enrollment_trends':
                return $this->getEnrollmentTrendsData($parameters, $user);
            default:
                throw new \Exception("Unknown report type: {$reportType}");
        }
    }

    /**
     * Validate file access for user
     */
    private function validateFileAccess($user, string $filename): bool
    {
        // Check if file belongs to user's exports
        // You can implement more sophisticated access control here
        return $user->hasPermissionTo('export reports');
    }

    /**
     * Generate widget PDF
     */
    private function generateWidgetPDF(Widget $widget, array $data, string $filename): void
    {
        // Implement PDF generation using dompdf or similar
        $pdf = app('dompdf.wrapper');
        $html = view('exports.widget-pdf', compact('widget', 'data'))->render();
        $pdf->loadHTML($html);
        $pdf->save(storage_path("app/exports/{$filename}"));
    }

    /**
     * Generate dashboard PDF
     */
    private function generateDashboardPDF(Dashboard $dashboard, array $data, string $filename, bool $includeCharts): void
    {
        $pdf = app('dompdf.wrapper');
        $html = view('exports.dashboard-pdf', compact('dashboard', 'data', 'includeCharts'))->render();
        $pdf->loadHTML($html);
        $pdf->save(storage_path("app/exports/{$filename}"));
    }

    /**
     * Generate report PDF
     */
    private function generateReportPDF(string $reportType, array $data, string $filename): void
    {
        $pdf = app('dompdf.wrapper');
        $html = view("exports.reports.{$reportType}-pdf", compact('data'))->render();
        $pdf->loadHTML($html);
        $pdf->save(storage_path("app/exports/{$filename}"));
    }

    /**
     * Get student performance data
     */
    private function getStudentPerformanceData(array $parameters, $user): array
    {
        // Implement student performance data retrieval
        return [
            'students' => [],
            'metrics' => [],
            'summary' => [],
        ];
    }

    /**
     * Get financial summary data
     */
    private function getFinancialSummaryData(array $parameters, $user): array
    {
        // Implement financial summary data retrieval
        return [
            'revenue' => [],
            'expenses' => [],
            'summary' => [],
        ];
    }

    /**
     * Get attendance report data
     */
    private function getAttendanceReportData(array $parameters, $user): array
    {
        // Implement attendance report data retrieval
        return [
            'attendance' => [],
            'statistics' => [],
            'trends' => [],
        ];
    }

    /**
     * Get enrollment trends data
     */
    private function getEnrollmentTrendsData(array $parameters, $user): array
    {
        // Implement enrollment trends data retrieval
        return [
            'enrollments' => [],
            'trends' => [],
            'projections' => [],
        ];
    }
}
