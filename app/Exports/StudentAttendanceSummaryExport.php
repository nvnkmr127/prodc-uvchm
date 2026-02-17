<?php

namespace App\Exports;

use App\Models\Attendance;
use App\Models\Batch;
use App\Models\Holiday;
use App\Models\Student;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class StudentAttendanceSummaryExport implements FromCollection, WithHeadings, ShouldAutoSize, WithStyles, WithEvents
{
    protected $courseId;
    protected $batchId;
    protected $startDate;
    protected $endDate;
    protected $months;

    public function __construct($courseId, $batchId, $startDate, $endDate)
    {
        $this->courseId = $courseId;
        $this->batchId = $batchId;
        $this->startDate = $startDate ? Carbon::parse($startDate) : Carbon::now()->startOfMonth();
        $this->endDate = $endDate ? Carbon::parse($endDate) : Carbon::now();

        // Calculate months in range
        $this->months = [];
        $period = CarbonPeriod::create($this->startDate, '1 month', $this->endDate);
        foreach ($period as $dt) {
            $this->months[] = $dt->copy();
        }
    }

    public function collection()
    {
        // 1. Fetch Students
        $studentsQuery = Student::query();

        // 1a. Exclude Dropouts
        $studentsQuery->where('status', '!=', 'dropout');

        if ($this->courseId) {
            $studentsQuery->whereHas('batch', function ($q) {
                $q->where('course_id', $this->courseId);
            });

            // 1b. Internship Specific Rule
            $course = \App\Models\Course::find($this->courseId);
            if ($course && stripos($course->name, 'Internship') !== false) {
                $studentsQuery->where('status', 'active');
            }
        }

        if ($this->batchId) {
            $studentsQuery->where('batch_id', $this->batchId);

            if (!$this->courseId) {
                $batch = \App\Models\Batch::with('course')->find($this->batchId);
                if ($batch && $batch->course && stripos($batch->course->name, 'Internship') !== false) {
                    $studentsQuery->where('status', 'active');
                }
            }
        }

        $students = $studentsQuery->with(['batch'])->get();

        // 2. Fetch Holidays
        $holidays = Holiday::whereBetween('date', [$this->startDate->format('Y-m-d'), $this->endDate->format('Y-m-d')])
            ->pluck('date')
            ->map(fn($date) => $date->format('Y-m-d'))
            ->toArray();

        // 3. Fetch Attendance Data
        $attendanceRecords = Attendance::whereIn('student_id', $students->pluck('id'))
            ->whereBetween('attendance_date', [$this->startDate->format('Y-m-d'), $this->endDate->format('Y-m-d')])
            ->select('student_id', 'attendance_date', 'status')
            ->get()
            ->groupBy('student_id');

        $output = collect();

        foreach ($students as $student) {
            $studentAttendance = $attendanceRecords->get($student->id, collect());

            // Basic Info
            $row = [
                $student->name,
                $student->enrollment_number,
                $student->batch->name ?? 'N/A',
            ];

            // Monthly Stats
            foreach ($this->months as $month) {
                $monthStart = $month->copy()->startOfMonth();
                $monthEnd = $month->copy()->endOfMonth();

                // Clip to selected range
                if ($monthStart->lt($this->startDate))
                    $monthStart = $this->startDate->copy();
                if ($monthEnd->gt($this->endDate))
                    $monthEnd = $this->endDate->copy();

                if ($monthStart->gt($monthEnd)) {
                    // Should not happen with CarbonPeriod logic usually, but safe guard
                    $row[] = 0;
                    $row[] = 0;
                    $row[] = 0;
                    $row[] = '0%';
                    $row[] = '';
                    continue;
                }

                $stats = $this->calculateStats($monthStart, $monthEnd, $holidays, $studentAttendance);

                $row[] = $stats['working_days'];
                $row[] = $stats['present'];
                $row[] = $stats['absent'];
                $row[] = $stats['percentage'] . '%';
                $row[] = ''; // Separator
            }

            // Overall Stats
            $overallStats = $this->calculateStats($this->startDate, $this->endDate, $holidays, $studentAttendance);
            $row[] = $overallStats['working_days'];
            $row[] = $overallStats['present'];
            $row[] = $overallStats['absent'];
            $row[] = $overallStats['percentage'] . '%';

            $output->push($row);
        }

        return $output;
    }

    private function calculateStats($start, $end, $allHolidays, $studentRecords)
    {
        $current = $start->copy();
        $workingDays = 0;

        while ($current->lte($end)) {
            if (!$current->isSunday() && !in_array($current->format('Y-m-d'), $allHolidays)) {
                $workingDays++;
            }
            $current->addDay();
        }

        $present = $studentRecords->whereBetween('attendance_date', [$start->format('Y-m-d'), $end->format('Y-m-d')])
            ->where('status', 'present')->count();

        $absent = $studentRecords->whereBetween('attendance_date', [$start->format('Y-m-d'), $end->format('Y-m-d')])
            ->where('status', 'absent')->count();

        $percentage = ($workingDays > 0) ? round(($present / $workingDays) * 100, 1) : 0;
        if ($percentage > 100)
            $percentage = 100;

        return [
            'working_days' => $workingDays,
            'present' => $present,
            'absent' => $absent,
            'percentage' => $percentage
        ];
    }

    public function headings(): array
    {
        $row1 = ['Student Name', 'Enrollment Number', 'Batch'];
        $row2 = ['', '', ''];

        foreach ($this->months as $month) {
            // Header 1: Month Name spanning 4 columns + 1 separator
            $row1[] = $month->format('F Y');
            $row1[] = ''; // spanned
            $row1[] = ''; // spanned
            $row1[] = ''; // spanned
            $row1[] = ''; // separator

            // Header 2: Columns
            $row2[] = 'Working Days';
            $row2[] = 'Present';
            $row2[] = 'Absent';
            $row2[] = 'Attendance %';
            $row2[] = ''; // separator
        }

        // Overall
        $row1[] = 'Overall';
        $row1[] = ''; // spanned
        $row1[] = ''; // spanned
        $row1[] = ''; // spanned

        $row2[] = 'Working Days';
        $row2[] = 'Present';
        $row2[] = 'Absent';
        $row2[] = 'Attendance %';

        return [$row1, $row2];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true, 'size' => 12]],
            2 => ['font' => ['bold' => true]],
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet;

                // Merge Vertical for first 3 columns
                $sheet->mergeCells('A1:A2');
                $sheet->mergeCells('B1:B2');
                $sheet->mergeCells('C1:C2');

                // Center align vertical columns
                $sheet->getStyle('A1:C2')->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);

                // Start Column Index for Data (0-based index: A=1, D=4)
                $col = 4;

                foreach ($this->months as $month) {
                    // Merge Month Header (4 columns)
                    $start = Coordinate::stringFromColumnIndex($col);
                    $end = Coordinate::stringFromColumnIndex($col + 3);
                    $sheet->mergeCells("{$start}1:{$end}1");

                    // Center Month Header
                    $sheet->getStyle("{$start}1:{$end}1")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                    // Move to next block (4 columns + 1 separator)
                    $col += 5;
                }

                // Merge Overall Header (4 columns)
                $start = Coordinate::stringFromColumnIndex($col);
                $end = Coordinate::stringFromColumnIndex($col + 3);
                $sheet->mergeCells("{$start}1:{$end}1");
                $sheet->getStyle("{$start}1:{$end}1")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            },
        ];
    }
}
