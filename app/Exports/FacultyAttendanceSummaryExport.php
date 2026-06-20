<?php

namespace App\Exports;

use App\Models\Attendance\FacultyAttendance;
use App\Models\Holiday;
use App\Models\User;
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

class FacultyAttendanceSummaryExport implements FromCollection, ShouldAutoSize, WithEvents, WithHeadings, WithStyles
{
    protected $department;

    protected $startDate;

    protected $months;

    protected $isSingleDay;

    public function __construct($department, $startDate, $endDate)
    {
        $this->department = $department;
        $this->startDate = $startDate ? Carbon::parse($startDate) : Carbon::now()->startOfMonth();
        $this->endDate = $endDate ? Carbon::parse($endDate) : Carbon::now();
        
        $this->isSingleDay = $this->startDate->format('Y-m-d') === $this->endDate->format('Y-m-d');

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
        // 1. Fetch Faculty
        $facultyQuery = User::role(['staff', 'faculty'])->where('status', 'active');

        if ($this->department) {
            $facultyQuery->where('department', $this->department);
        }

        $faculties = $facultyQuery->get();

        // 2. Fetch Holidays - Ensure consistent string format
        $holidays = Holiday::whereBetween('date', [$this->startDate->format('Y-m-d'), $this->endDate->format('Y-m-d')])
            ->get()
            ->map(fn ($h) => (is_string($h->date) ? substr($h->date, 0, 10) : $h->date->format('Y-m-d')))
            ->toArray();

        // 3. Fetch Attendance Data
        $attendanceRecords = FacultyAttendance::whereIn('faculty_id', $faculties->pluck('id'))
            ->whereBetween('attendance_date', [$this->startDate->format('Y-m-d'), $this->endDate->format('Y-m-d')])
            ->select('faculty_id', 'attendance_date', 'status', 'check_in_time', 'check_out_time', 'notes')
            ->get()
            ->groupBy('faculty_id');

        // 3a. Fetch first biometric record for all faculty to avoid N+1
        $firstPunches = FacultyAttendance::whereIn('faculty_id', $faculties->pluck('id'))
            ->whereIn('status', ['present', 'late', 'half_day'])
            ->selectRaw('faculty_id, MIN(attendance_date) as first_date')
            ->groupBy('faculty_id')
            ->pluck('first_date', 'faculty_id')
            ->toArray();

        $output = collect();

        foreach ($faculties as $faculty) {
            // Pre-map faculty attendance for this range to avoid repetitive parsing
            $facultyRecordsMap = $attendanceRecords->get($faculty->id, collect())->mapWithKeys(function ($item) {
                $d = is_string($item->attendance_date) ? substr($item->attendance_date, 0, 10) : $item->attendance_date->format('Y-m-d');
                return [$d => $item];
            });

            // Basic Info
            $row = [
                $faculty->name,
                $faculty->biometric_employee_code ?? $faculty->employee_id ?? 'N/A',
                $faculty->department ?? 'General Staff',
            ];

            if ($this->isSingleDay) {
                $dailyRecordStr = $this->startDate->format('Y-m-d');
                $record = $facultyRecordsMap->get($dailyRecordStr);
                $status = $record ? strtolower(trim($record->status)) : 'absent';
                $checkIn = $record && $record->check_in_time ? Carbon::parse($record->check_in_time)->format('h:i A') : '--';
                $checkOut = $record && $record->check_out_time ? Carbon::parse($record->check_out_time)->format('h:i A') : '--';
                $notes = $record->notes ?? '';
                
                $row[] = $checkIn;
                $row[] = $checkOut;
                $row[] = ucfirst(str_replace('_', ' ', $status));
                $row[] = $notes;
                $output->push($row);
                continue;
            }

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
                    $row[] = 0;
                    $row[] = '0%';
                    $row[] = '';
                    continue;
                }

                $stats = $this->calculateStats($monthStart, $monthEnd, $holidays, $facultyRecordsMap, $faculty, $firstPunches);

                $row[] = $stats['working_days'];
                $row[] = $stats['present'];
                $row[] = $stats['late'];
                $row[] = $stats['half_days'];
                $row[] = $stats['absent'];
                $row[] = $stats['percentage'].'%';
                $row[] = ''; // Separator
            }

            // Overall Stats
            $overallStats = $this->calculateStats($this->startDate, $this->endDate, $holidays, $facultyRecordsMap, $faculty, $firstPunches);
            $row[] = $overallStats['working_days'];
            $row[] = $overallStats['present'];
            $row[] = $overallStats['late'];
            $row[] = $overallStats['half_days'];
            $row[] = $overallStats['absent'];
            $row[] = $overallStats['percentage'].'%';

            $output->push($row);
        }

        return $output;
    }

    private function calculateStats($start, $end, $allHolidays, $recordMap, $faculty, $firstPunches = [])
    {
        $profileStartDate = $faculty->created_at->startOfDay();
        $firstBiometricUse = $firstPunches[$faculty->id] ?? null;

        $todayStr = Carbon::now()->format('Y-m-d');

        $presentCount = 0;
        $lateCount = 0;
        $halfDayCount = 0;
        $absentCount = 0;
        $excusedCount = 0;
        $holidaysCount = 0;

        $current = $start->copy();
        while ($current->lte($end)) {
            $dateStr = $current->format('Y-m-d');
            $isSunday = $current->isSunday();
            $isExplicitHoliday = in_array($dateStr, $allHolidays);
            $isFuture = $dateStr > $todayStr;

            $isHoliday = $isSunday || $isExplicitHoliday;

            if ($isHoliday) {
                $holidaysCount++;
            } else {
                $shouldIgnore = $current->lt($profileStartDate) || is_null($firstBiometricUse);

                if (! $isFuture && ! $shouldIgnore) {
                    if (isset($recordMap[$dateStr])) {
                        $status = strtolower(trim($recordMap[$dateStr]->status));
                        if ($status === 'present') {
                            $presentCount++;
                        } elseif ($status === 'late') {
                            $lateCount++;
                        } elseif ($status === 'half_day') {
                            $halfDayCount++;
                        } elseif ($status === 'absent') {
                            $absentCount++;
                        } elseif ($status === 'excused') {
                            $excusedCount++;
                        } else {
                            $absentCount++;
                        }
                    } else {
                        $absentCount++;
                    }
                }
            }
            $current->addDay();
        }

        $totalCalculatedDays = $presentCount + $lateCount + $halfDayCount + $absentCount + $excusedCount;
        $percentage = $totalCalculatedDays > 0 ? round((($presentCount + $lateCount + ($halfDayCount * 0.5)) / $totalCalculatedDays) * 100, 1) : 0;

        return [
            'working_days' => $totalCalculatedDays,
            'present' => $presentCount,
            'late' => $lateCount,
            'half_days' => $halfDayCount,
            'absent' => $absentCount,
            'excused' => $excusedCount,
            'percentage' => $percentage,
        ];
    }

    public function headings(): array
    {
        if ($this->isSingleDay) {
            return [
                ['Faculty Name', 'Biometric Code/ID', 'Department', 'Check In', 'Check Out', 'Status', 'Notes']
            ];
        }

        $row1 = ['Faculty Name', 'Biometric Code/ID', 'Department'];
        $row2 = ['', '', ''];

        foreach ($this->months as $month) {
            $row1[] = $month->format('F Y');
            $row1[] = ''; // spanned
            $row1[] = ''; // spanned
            $row1[] = ''; // spanned
            $row1[] = ''; // spanned
            $row1[] = ''; // spanned
            $row1[] = ''; // separator

            $row2[] = 'Working Days';
            $row2[] = 'Present';
            $row2[] = 'Late';
            $row2[] = 'Half Day';
            $row2[] = 'Absent';
            $row2[] = 'Attendance %';
            $row2[] = ''; // separator
        }

        $row1[] = 'Overall';
        $row1[] = ''; 
        $row1[] = ''; 
        $row1[] = ''; 
        $row1[] = ''; 
        $row1[] = ''; 

        $row2[] = 'Working Days';
        $row2[] = 'Present';
        $row2[] = 'Late';
        $row2[] = 'Half Day';
        $row2[] = 'Absent';
        $row2[] = 'Attendance %';

        return [$row1, $row2];
    }

    public function styles(Worksheet $sheet)
    {
        if ($this->isSingleDay) {
            return [
                1 => ['font' => ['bold' => true, 'size' => 12]],
            ];
        }
        return [
            1 => ['font' => ['bold' => true, 'size' => 12]],
            2 => ['font' => ['bold' => true]],
        ];
    }

    public function registerEvents(): array
    {
        if ($this->isSingleDay) {
            return [];
        }

        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet;
                $sheet->mergeCells('A1:A2');
                $sheet->mergeCells('B1:B2');
                $sheet->mergeCells('C1:C2');
                $sheet->getStyle('A1:C2')->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
                $col = 4;
                foreach ($this->months as $month) {
                    $start = Coordinate::stringFromColumnIndex($col);
                    $end = Coordinate::stringFromColumnIndex($col + 5);
                    $sheet->mergeCells("{$start}1:{$end}1");
                    $sheet->getStyle("{$start}1:{$end}1")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $col += 7;
                }
                $start = Coordinate::stringFromColumnIndex($col);
                $end = Coordinate::stringFromColumnIndex($col + 5);
                $sheet->mergeCells("{$start}1:{$end}1");
                $sheet->getStyle("{$start}1:{$end}1")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            },
        ];
    }
}
