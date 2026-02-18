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

        // Calculate months in range robustly
        $this->months = [];
        $current = $this->startDate->copy()->startOfMonth();
        $final = $this->endDate->copy()->startOfMonth();

        while ($current->lte($final)) {
            $this->months[] = $current->copy();
            $current->addMonth();
        }
    }

    public function collection()
    {
        // 1. Fetch Students - Bypass Global Scope
        $studentsQuery = Student::withoutGlobalScope('academic_year');

        // 1a. Exclude Dropouts
        $studentsQuery->where('status', '!=', 'dropout');

        if ($this->courseId) {
            $studentsQuery->whereHas('batch', function ($q) {
                $q->withoutGlobalScope('academic_year')->where('course_id', $this->courseId);
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
                $batch = \App\Models\Batch::withoutGlobalScope('academic_year')->with('course')->find($this->batchId);
                if ($batch && $batch->course && stripos($batch->course->name, 'Internship') !== false) {
                    $studentsQuery->where('status', 'active');
                }
            }
        }

        $students = $studentsQuery->with([
            'batch' => function ($q) {
                $q->withoutGlobalScope('academic_year');
            }
        ])->get();

        // 2. Fetch Holidays - Ensure consistent string format
        $holidays = Holiday::whereBetween('date', [$this->startDate->format('Y-m-d'), $this->endDate->format('Y-m-d')])
            ->get()
            ->map(fn($h) => (is_string($h->date) ? substr($h->date, 0, 10) : $h->date->format('Y-m-d')))
            ->toArray();

        // 3. Fetch Attendance Data - Bypass Global Scope
        $attendanceRecords = Attendance::withoutGlobalScope('academic_year')
            ->whereIn('student_id', $students->pluck('id'))
            ->whereBetween('attendance_date', [$this->startDate->format('Y-m-d'), $this->endDate->format('Y-m-d')])
            ->select('student_id', 'attendance_date', 'status')
            ->get()
            ->groupBy('student_id');

        // 3b. Fetch Daily Punch Counts for "Low Attendance" holiday check (Global) - Bypass Global Scope
        $dailyCounts = Attendance::withoutGlobalScope('academic_year')
            ->whereBetween('attendance_date', [$this->startDate->format('Y-m-d'), $this->endDate->format('Y-m-d')])
            ->selectRaw('DATE(attendance_date) as date, count(distinct student_id) as count')
            ->groupBy('date')
            ->get()
            ->mapWithKeys(function ($item) {
                // Handle different potential date formats from DB
                $dateStr = is_string($item->date) ? substr($item->date, 0, 10) : Carbon::parse($item->date)->format('Y-m-d');
                return [$dateStr => $item->count];
            })
            ->toArray();

        $output = collect();

        foreach ($students as $student) {
            // Pre-map student attendance for this range to avoid repetitive parsing
            // This is indexed by 'Y-m-d' date string
            $studentRecordsMap = $attendanceRecords->get($student->id, collect())->mapWithKeys(function ($item) {
                $d = is_string($item->attendance_date) ? substr($item->attendance_date, 0, 10) : $item->attendance_date->format('Y-m-d');
                return [$d => $item];
            });

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
                    $row[] = 0;
                    $row[] = 0;
                    $row[] = 0;
                    $row[] = 0;
                    $row[] = '0%';
                    $row[] = '';
                    continue;
                }

                $stats = $this->calculateStats($monthStart, $monthEnd, $holidays, $studentRecordsMap, $student, $dailyCounts);

                $row[] = $stats['working_days'];
                $row[] = $stats['present'];
                $row[] = $stats['absent'];
                $row[] = $stats['holidays'];
                $row[] = $stats['percentage'] . '%';
                $row[] = ''; // Separator
            }

            // Overall Stats
            $overallStats = $this->calculateStats($this->startDate, $this->endDate, $holidays, $studentRecordsMap, $student, $dailyCounts);
            $row[] = $overallStats['working_days'];
            $row[] = $overallStats['present'];
            $row[] = $overallStats['absent'];
            $row[] = $overallStats['holidays'];
            $row[] = $overallStats['percentage'] . '%';

            $output->push($row);
        }

        return $output;
    }

    private function calculateStats($start, $end, $allHolidays, $recordMap, $student, $dailyCounts = [])
    {
        // Determine effective start date for this student
        $effectiveStartDate = $student->admission_date
            ? Carbon::parse($student->admission_date)->startOfDay()
            : $student->created_at->copy()->startOfDay();

        $current = $start->copy();
        $workingDays = 0;
        $holidaysCount = 0;
        $today = Carbon::now()->startOfDay();

        // Loop through every day in the period
        while ($current->lte($end)) {
            $dateStr = $current->format('Y-m-d');
            $isSunday = $current->isSunday();
            $isExplicitHoliday = in_array($dateStr, $allHolidays);

            // Low Attendance Holiday Logic
            $isLowAttendanceHoliday = false;
            // Only past/today can be low attendance holidays based on actual data
            if ($current->lte($today) && !$isSunday && !$isExplicitHoliday) {
                $dayPunchCount = $dailyCounts[$dateStr] ?? 0;
                if ($dayPunchCount < 10) {
                    $isLowAttendanceHoliday = true;
                }
            }

            $isHoliday = $isSunday || $isExplicitHoliday || $isLowAttendanceHoliday;

            if ($isHoliday) {
                $holidaysCount++;
            } else {
                // 2. Working Day Logic
                // Only count as working day if student has started
                if ($current->gte($effectiveStartDate)) {
                    $workingDays++;
                }
            }
            $current->addDay();
        }

        // Calculate Present and Absent
        // We need to loop again or use the counters from above?
        // Let's loop again properly for Present/Absent strictly based on working days

        $present = 0;
        $absent = 0;

        $current = $start->copy();
        while ($current->lte($end)) {
            $dateStr = $current->format('Y-m-d');
            $isSunday = $current->isSunday();
            $isExplicitHoliday = in_array($dateStr, $allHolidays);

            $isLowAttendanceHoliday = false;
            if ($current->lte($today) && !$isSunday && !$isExplicitHoliday) {
                $dayPunchCount = $dailyCounts[$dateStr] ?? 0;
                if ($dayPunchCount < 10) {
                    $isLowAttendanceHoliday = true;
                }
            }

            $isHoliday = $isSunday || $isExplicitHoliday || $isLowAttendanceHoliday;

            // We only care about attendance on "Working Days" for this student
            if (!$isHoliday && $current->gte($effectiveStartDate)) {

                // If the day is in the future, ignore it for attendance stats
                if ($current->gt($today)) {
                    $current->addDay();
                    continue;
                }

                if (isset($recordMap[$dateStr])) {
                    $status = strtolower(trim($recordMap[$dateStr]->status));
                    if (in_array($status, ['present', 'late'])) {
                        $present++;
                    } elseif ($status === 'absent') {
                        $absent++;
                    }
                } else {
                    // No Record Found.
                    // If it's a past working day and student had joined, count as Absent (Implicit)
                    $absent++;
                }
            }
            $current->addDay();
        }

        $percentage = ($workingDays > 0) ? round(($present / $workingDays) * 100, 1) : 0;
        if ($percentage > 100)
            $percentage = 100;

        return [
            'working_days' => $workingDays,
            'present' => $present,
            'absent' => $absent,
            'holidays' => $holidaysCount,
            'percentage' => $percentage
        ];
    }

    public function headings(): array
    {
        $row1 = ['Student Name', 'Enrollment Number', 'Batch'];
        $row2 = ['', '', ''];

        foreach ($this->months as $month) {
            // Header 1: Month Name spanning 5 columns + 1 separator
            $row1[] = $month->format('F Y');
            $row1[] = ''; // spanned
            $row1[] = ''; // spanned
            $row1[] = ''; // spanned
            $row1[] = ''; // spanned
            $row1[] = ''; // separator

            // Header 2: Columns
            $row2[] = 'Working Days';
            $row2[] = 'Present';
            $row2[] = 'Absent';
            $row2[] = 'Holidays';
            $row2[] = 'Attendance %';
            $row2[] = ''; // separator
        }

        // Overall
        $row1[] = 'Overall';
        $row1[] = ''; // spanned
        $row1[] = ''; // spanned
        $row1[] = ''; // spanned
        $row1[] = ''; // spanned

        $row2[] = 'Working Days';
        $row2[] = 'Present';
        $row2[] = 'Absent';
        $row2[] = 'Holidays';
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
                    // Merge Month Header (5 columns)
                    $start = Coordinate::stringFromColumnIndex($col);
                    $end = Coordinate::stringFromColumnIndex($col + 4);
                    $sheet->mergeCells("{$start}1:{$end}1");

                    // Center Month Header
                    $sheet->getStyle("{$start}1:{$end}1")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                    // Move to next block (5 columns + 1 separator)
                    $col += 6;
                }

                // Merge Overall Header (5 columns)
                $start = Coordinate::stringFromColumnIndex($col);
                $end = Coordinate::stringFromColumnIndex($col + 4);
                $sheet->mergeCells("{$start}1:{$end}1");
                $sheet->getStyle("{$start}1:{$end}1")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            },
        ];
    }
}
