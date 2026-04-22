<?php

namespace App\Exports;

use App\Models\Attendance;
use App\Models\Holiday;
use App\Models\Student;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class StudentAttendanceSummaryExport implements FromCollection, ShouldAutoSize, WithEvents, WithHeadings, WithStyles
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

            if (! $this->courseId) {
                $batch = \App\Models\Batch::withoutGlobalScope('academic_year')->with('course')->find($this->batchId);
                if ($batch && $batch->course && stripos($batch->course->name, 'Internship') !== false) {
                    $studentsQuery->where('status', 'active');
                }
            }
        }

        $students = $studentsQuery->with([
            'batch' => function ($q) {
                $q->withoutGlobalScope('academic_year');
            },
        ])->get();

        // 2. Fetch Holidays - Ensure consistent string format
        $holidays = Holiday::whereBetween('date', [$this->startDate->format('Y-m-d'), $this->endDate->format('Y-m-d')])
            ->get()
            ->map(fn ($h) => (is_string($h->date) ? substr($h->date, 0, 10) : $h->date->format('Y-m-d')))
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

        // 3c. Fetch first biometric record for all students to avoid N+1
        $firstPunches = Attendance::withoutGlobalScope('academic_year')
            ->whereIn('student_id', $students->pluck('id'))
            ->whereIn('status', ['present', 'late'])
            ->selectRaw('student_id, MIN(attendance_date) as first_date')
            ->groupBy('student_id')
            ->pluck('first_date', 'student_id')
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
                if ($monthStart->lt($this->startDate)) {
                    $monthStart = $this->startDate->copy();
                }
                if ($monthEnd->gt($this->endDate)) {
                    $monthEnd = $this->endDate->copy();
                }

                if ($monthStart->gt($monthEnd)) {
                    $row[] = 0;
                    $row[] = 0;
                    $row[] = 0;
                    $row[] = 0;
                    $row[] = '0%';
                    $row[] = '';

                    continue;
                }

                $stats = $this->calculateStats($monthStart, $monthEnd, $holidays, $studentRecordsMap, $student, $dailyCounts, $firstPunches);

                $row[] = $stats['working_days'];
                $row[] = $stats['present'];
                $row[] = $stats['absent'];
                $row[] = $stats['excused'];
                $row[] = $stats['percentage'].'%';
                $row[] = ''; // Separator
            }

            // Overall Stats
            $overallStats = $this->calculateStats($this->startDate, $this->endDate, $holidays, $studentRecordsMap, $student, $dailyCounts, $firstPunches);
            $row[] = $overallStats['working_days'];
            $row[] = $overallStats['present'];
            $row[] = $overallStats['absent'];
            $row[] = $overallStats['excused'];
            $row[] = $overallStats['percentage'].'%';

            $output->push($row);
        }

        return $output;
    }

    private function calculateStats($start, $end, $allHolidays, $recordMap, $student, $dailyCounts = [], $firstPunches = [])
    {
        $admissionDate = $student->admission_date ? Carbon::parse($student->admission_date)->startOfDay() : null;

        // Use pre-fetched first punch date or query if missing (fallback)
        $firstBiometricUse = $firstPunches[$student->id] ?? null;

        $todayStr = Carbon::now()->format('Y-m-d');
        $isOnInternship = $student->batch && $student->batch->is_on_internship;
        $internshipStartDate = $isOnInternship ? $student->batch->internship_start_date : null;

        $presentCount = 0;
        $absentCount = 0;
        $internshipCount = 0;
        $excusedCount = 0;
        $holidaysCount = 0;

        $current = $start->copy();
        while ($current->lte($end)) {
            $dateStr = $current->format('Y-m-d');
            $isSunday = $current->isSunday();
            $isExplicitHoliday = in_array($dateStr, $allHolidays);
            $isFuture = $dateStr > $todayStr;

            $isLowAttendanceHoliday = false;
            if (! $isFuture && ! $isSunday && ! $isExplicitHoliday) {
                $dayPunchCount = $dailyCounts[$dateStr] ?? 0;
                if ($dayPunchCount < 10) {
                    $isLowAttendanceHoliday = true;
                }
            }

            $isHoliday = $isSunday || $isExplicitHoliday || $isLowAttendanceHoliday;

            if ($isHoliday) {
                $holidaysCount++;
            } else {
                // Working Day Criterion: Admission Date is priority
                $hasStarted = $admissionDate
                    ? $current->gte($admissionDate)
                    : ($firstBiometricUse ? $current->gte(Carbon::parse($firstBiometricUse)->startOfDay()) : false);

                if ($hasStarted && ! $isFuture) {
                    if (isset($recordMap[$dateStr])) {
                        $status = strtolower(trim($recordMap[$dateStr]->status));
                        if (in_array($status, ['present', 'late'])) {
                            $presentCount++;
                        } elseif ($status === 'absent') {
                            $absentCount++;
                        } elseif ($status === 'internship') {
                            $internshipCount++;
                        } elseif ($status === 'excused') {
                            $excusedCount++;
                        } else {
                            $absentCount++; // Unknown status treated as absent
                        }
                    } else {
                        $isInternshipDay = false;
                        if ($isOnInternship && $internshipStartDate) {
                            $isInternshipDay = $current->gte(Carbon::parse($internshipStartDate)->startOfDay());
                        }

                        if ($isInternshipDay) {
                            $internshipCount++;
                        } else {
                            $absentCount++;
                        }
                    }
                }
            }
            $current->addDay();
        }

        // Logic from Student Profile: percentage = ((present + late + internship) / totalCalculatedDays)
        $totalWorkingDays = $presentCount + $absentCount + $internshipCount + $excusedCount;
        $totalAttended = $presentCount + $internshipCount;

        $percentage = ($totalWorkingDays > 0) ? round(($totalAttended / $totalWorkingDays) * 100, 1) : 0;
        if ($percentage > 100) {
            $percentage = 100;
        }

        return [
            'working_days' => $totalWorkingDays,
            'present' => $presentCount,
            'absent' => $absentCount,
            'excused' => $excusedCount,
            'percentage' => $percentage,
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
            $row2[] = 'Excused';
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
        $row2[] = 'Excused';
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
