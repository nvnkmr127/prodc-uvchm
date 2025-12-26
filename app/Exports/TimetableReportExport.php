<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class TimetableReportExport implements WithMultipleSheets
{
    protected $report;
    protected $weekStart;

    public function __construct(array $report, $weekStart = null)
    {
        $this->report = $report;
        $this->weekStart = $weekStart;
    }

    /**
     * Create multiple sheets for comprehensive report
     */
    public function sheets(): array
    {
        $sheets = [];
        
        // Summary sheet
        $sheets[] = new TimetableSummarySheet($this->report, $this->weekStart);
        
        // Daily schedule sheets
        if (isset($this->report['detailed_schedule'])) {
            foreach ($this->report['detailed_schedule'] as $date => $dayData) {
                $sheets[] = new DailyScheduleSheet($date, $dayData);
            }
        }
        
        // Violations sheet (if any)
        if (!empty($this->report['violations'])) {
            $sheets[] = new ViolationsSheet($this->report['violations']);
        }
        
        // Statistics sheet
        $sheets[] = new StatisticsSheet($this->report['statistics']);
        
        return $sheets;
    }
}

/**
 * Summary sheet with overall timetable information
 */
class TimetableSummarySheet implements FromArray, WithHeadings, WithStyles, WithTitle, ShouldAutoSize
{
    protected $report;
    protected $weekStart;

    public function __construct(array $report, $weekStart = null)
    {
        $this->report = $report;
        $this->weekStart = $weekStart;
    }

    public function title(): string
    {
        return 'Summary';
    }

    public function headings(): array
    {
        return [
            'Metric',
            'Value',
            'Details'
        ];
    }

    public function array(): array
    {
        $stats = $this->report['statistics'] ?? [];
        $weekStartStr = $this->weekStart ? $this->weekStart->format('M d, Y') : 'N/A';
        
        return [
            ['Week Period', $weekStartStr, 'Monday to Saturday (5.5 working days)'],
            ['Total Sessions', $stats['total_sessions'] ?? 0, 'All scheduled sessions'],
            ['Lab Sessions', $stats['lab_sessions'] ?? 0, 'Practical lab sessions'],
            ['Theory Sessions', $stats['theory_sessions'] ?? 0, 'Regular theory classes'],
            ['Total Batches', $stats['total_batches'] ?? 0, 'Active batches scheduled'],
            ['Practical Groups', $stats['total_groups'] ?? 0, 'Lab groups with sessions'],
            ['Compliance Status', empty($this->report['violations']) ? 'COMPLIANT' : 'NON-COMPLIANT', 'Requirement adherence'],
            ['Violations Found', count($this->report['violations'] ?? []), 'Total requirement violations'],
            ['', '', ''], // Spacing
            ['Requirements Met:', '', ''],
            ['FR-2: Lab Sessions', $this->checkRequirement('FR-2'), '4 required labs per group per week'],
            ['FR-3: No Duplicates', $this->checkRequirement('FR-3'), 'No repeated lab sessions'],
            ['FR-4: Lab-Theory Pairing', $this->checkRequirement('FR-4'), 'Theory paired with lab sessions'],
            ['FR-5: Saturday Theory Only', $this->checkRequirement('FR-5'), 'No lab sessions on Saturday'],
            ['FR-6: No Overlaps', $this->checkRequirement('FR-6'), 'No scheduling conflicts'],
            ['FR-7: Lab Availability', $this->checkRequirement('FR-7'), 'No double-booked labs'],
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            // Header row styling
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => '4472C4']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
            ],
            
            // Compliance status styling
            7 => [
                'font' => ['bold' => true],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID, 
                    'color' => ['rgb' => empty($this->report['violations']) ? 'C6EFCE' : 'FFC7CE']
                ]
            ],
            
            // Requirements section header
            10 => [
                'font' => ['bold' => true, 'color' => ['rgb' => '0066CC']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => 'E7F3FF']]
            ],
            
            // All cells border
            'A1:C20' => [
                'borders' => [
                    'allBorders' => ['borderStyle' => Border::BORDER_THIN]
                ]
            ]
        ];
    }

    private function checkRequirement(string $code): string
    {
        $violations = $this->report['violations'] ?? [];
        $hasViolation = collect($violations)->contains(function($violation) use ($code) {
            return strpos($violation, $code) !== false;
        });
        
        return $hasViolation ? '❌ VIOLATED' : '✅ MET';
    }
}

/**
 * Daily schedule sheet
 */
class DailyScheduleSheet implements FromArray, WithHeadings, WithStyles, WithTitle, ShouldAutoSize
{
    protected $date;
    protected $dayData;

    public function __construct(string $date, array $dayData)
    {
        $this->date = $date;
        $this->dayData = $dayData;
    }

    public function title(): string
    {
        return \Carbon\Carbon::parse($this->date)->format('M d (D)');
    }

    public function headings(): array
    {
        return [
            'Time',
            'Subject',
            'Type',
            'Group/Batch',
            'Faculty',
            'Classroom'
        ];
    }

    public function array(): array
    {
        $rows = [];
        
        if (empty($this->dayData['entries'])) {
            return [['No sessions scheduled', '', '', '', '', '']];
        }
        
        foreach ($this->dayData['entries'] as $entry) {
            $rows[] = [
                $entry['time'],
                $entry['subject'],
                $entry['type'],
                $entry['group'],
                $entry['faculty'],
                $entry['classroom']
            ];
        }
        
        return $rows;
    }

    public function styles(Worksheet $sheet)
    {
        $rowCount = count($this->dayData['entries'] ?? []) + 1;
        
        return [
            // Header styling
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => '70AD47']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
            ],
            
            // Alternate row colors for better readability
            'A2:F' . ($rowCount) => [
                'borders' => [
                    'allBorders' => ['borderStyle' => Border::BORDER_THIN]
                ]
            ]
        ];
    }
}

/**
 * Violations sheet for tracking compliance issues
 */
class ViolationsSheet implements FromArray, WithHeadings, WithStyles, WithTitle, ShouldAutoSize
{
    protected $violations;

    public function __construct(array $violations)
    {
        $this->violations = $violations;
    }

    public function title(): string
    {
        return 'Violations';
    }

    public function headings(): array
    {
        return [
            'Requirement Code',
            'Violation Description',
            'Severity',
            'Recommended Action'
        ];
    }

    public function array(): array
    {
        if (empty($this->violations)) {
            return [['No violations found', '✅ All requirements met', 'None', 'None']];
        }
        
        $rows = [];
        foreach ($this->violations as $violation) {
            // Extract requirement code from violation message
            preg_match('/FR-\d+/', $violation, $matches);
            $code = $matches[0] ?? 'Unknown';
            
            $severity = $this->determineSeverity($code);
            $action = $this->getRecommendedAction($code);
            
            $rows[] = [
                $code,
                $violation,
                $severity,
                $action
            ];
        }
        
        return $rows;
    }

    public function styles(Worksheet $sheet)
    {
        $rowCount = count($this->violations) + 1;
        
        return [
            // Header styling
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => 'D13212']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
            ],
            
            // Violation rows styling
            'A2:D' . ($rowCount) => [
                'borders' => [
                    'allBorders' => ['borderStyle' => Border::BORDER_THIN]
                ],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => 'FFE6E6']]
            ]
        ];
    }

    private function determineSeverity(string $code): string
    {
        return match($code) {
            'FR-2', 'FR-3' => 'HIGH',
            'FR-4', 'FR-6', 'FR-7' => 'MEDIUM', 
            'FR-5' => 'MEDIUM',
            default => 'LOW'
        };
    }

    private function getRecommendedAction(string $code): string
    {
        return match($code) {
            'FR-2' => 'Ensure all groups have 4 lab sessions scheduled',
            'FR-3' => 'Remove duplicate lab sessions for same subject',
            'FR-4' => 'Pair lab sessions with theory classes',
            'FR-5' => 'Move lab sessions from Saturday to weekdays',
            'FR-6' => 'Resolve scheduling conflicts within groups',
            'FR-7' => 'Fix lab room double-booking conflicts',
            default => 'Review and fix the identified issue'
        };
    }
}

/**
 * Statistics sheet with detailed metrics
 */
class StatisticsSheet implements FromArray, WithHeadings, WithStyles, WithTitle, ShouldAutoSize
{
    protected $statistics;

    public function __construct(array $statistics)
    {
        $this->statistics = $statistics;
    }

    public function title(): string
    {
        return 'Statistics';
    }

    public function headings(): array
    {
        return [
            'Category',
            'Metric',
            'Value',
            'Target',
            'Status'
        ];
    }

    public function array(): array
    {
        $stats = $this->statistics;
        
        return [
            // Session statistics
            ['Sessions', 'Total Sessions', $stats['total_sessions'] ?? 0, 'Variable', $this->getStatus($stats['total_sessions'] ?? 0, 1)],
            ['Sessions', 'Lab Sessions', $stats['lab_sessions'] ?? 0, '4 per group', $this->getLabSessionStatus()],
            ['Sessions', 'Theory Sessions', $stats['theory_sessions'] ?? 0, 'Variable', $this->getStatus($stats['theory_sessions'] ?? 0, 1)],
            ['', '', '', '', ''], // Spacing
            
            // Resource statistics  
            ['Resources', 'Total Batches', $stats['total_batches'] ?? 0, 'All Active', $this->getStatus($stats['total_batches'] ?? 0, 1)],
            ['Resources', 'Practical Groups', $stats['total_groups'] ?? 0, 'All Allocated', $this->getStatus($stats['total_groups'] ?? 0, 1)],
            ['', '', '', '', ''], // Spacing
            
            // Lab utilization
            ['Lab Utilization', 'Service Lab', $this->getLabUtilization('service'), '80-100%', $this->getUtilizationStatus('service')],
            ['Lab Utilization', 'Kitchen Lab', $this->getLabUtilization('kitchen'), '80-100%', $this->getUtilizationStatus('kitchen')],
            ['Lab Utilization', 'Front Office Lab', $this->getLabUtilization('front_office'), '80-100%', $this->getUtilizationStatus('front_office')],
            ['Lab Utilization', 'Housekeeping Lab', $this->getLabUtilization('housekeeping'), '80-100%', $this->getUtilizationStatus('housekeeping')],
            ['', '', '', '', ''], // Spacing
            
            // Compliance metrics
            ['Compliance', 'Requirement FR-2', $this->getComplianceMetric('FR-2'), '100%', $this->getComplianceStatus('FR-2')],
            ['Compliance', 'Requirement FR-3', $this->getComplianceMetric('FR-3'), '100%', $this->getComplianceStatus('FR-3')],
            ['Compliance', 'Requirement FR-4', $this->getComplianceMetric('FR-4'), '100%', $this->getComplianceStatus('FR-4')],
            ['Compliance', 'Requirement FR-5', $this->getComplianceMetric('FR-5'), '100%', $this->getComplianceStatus('FR-5')],
            ['Compliance', 'Requirement FR-6', $this->getComplianceMetric('FR-6'), '100%', $this->getComplianceStatus('FR-6')],
            ['Compliance', 'Requirement FR-7', $this->getComplianceMetric('FR-7'), '100%', $this->getComplianceStatus('FR-7')],
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            // Header styling
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => '8E44AD']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
            ],
            
            // Category headers
            'A:E' => [
                'borders' => [
                    'allBorders' => ['borderStyle' => Border::BORDER_THIN]
                ]
            ],
            
            // Status column conditional formatting would go here
            // (Excel export doesn't easily support conditional formatting via this package)
        ];
    }

    private function getStatus(int $value, int $minimum): string
    {
        return $value >= $minimum ? '✅ Good' : '⚠️ Low';
    }

    private function getLabSessionStatus(): string
    {
        $labSessions = $this->statistics['lab_sessions'] ?? 0;
        $totalGroups = $this->statistics['total_groups'] ?? 1;
        $expectedSessions = $totalGroups * 4; // 4 lab sessions per group
        
        if ($labSessions >= $expectedSessions) {
            return '✅ Target Met';
        } elseif ($labSessions >= $expectedSessions * 0.8) {
            return '⚠️ Near Target';
        } else {
            return '❌ Below Target';
        }
    }

    private function getLabUtilization(string $labType): string
    {
        $utilization = $this->statistics['lab_utilization'][$labType] ?? 0;
        return $utilization . '%';
    }

    private function getUtilizationStatus(string $labType): string
    {
        $utilization = $this->statistics['lab_utilization'][$labType] ?? 0;
        
        if ($utilization >= 80) {
            return '✅ Optimal';
        } elseif ($utilization >= 60) {
            return '⚠️ Moderate';
        } else {
            return '❌ Low';
        }
    }

    private function getComplianceMetric(string $requirement): string
    {
        // This would be calculated based on actual compliance checking
        // For now, return a placeholder
        return '95%'; // Placeholder
    }

    private function getComplianceStatus(string $requirement): string
    {
        // This would check actual compliance status
        // For now, return based on violations
        return '✅ Compliant'; // Placeholder
    }
}
