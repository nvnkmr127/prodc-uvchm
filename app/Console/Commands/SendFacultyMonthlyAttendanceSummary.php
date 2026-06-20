<?php

namespace App\Console\Commands;

use App\Mail\FacultyMonthlyAttendanceSummaryMail;
use App\Models\Attendance\FacultyAttendance;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendFacultyMonthlyAttendanceSummary extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'attendance:faculty-summary-monthly';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send monthly faculty attendance summary email';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $now = Carbon::now();
        $monthName = $now->format('F Y');
        
        $this->info("Preparing monthly faculty attendance summary for {$monthName}...");

        // 1. Get all active faculty
        $faculties = User::whereHas('roles', function($q) {
            $q->whereIn('name', ['staff', 'faculty']);
        })
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        $startOfMonth = now()->startOfMonth()->toDateString();
        $endOfMonth = now()->endOfMonth()->toDateString();

        $records = FacultyAttendance::whereIn('faculty_id', $faculties->pluck('id'))
            ->whereBetween('attendance_date', [$startOfMonth, $endOfMonth])
            ->get()
            ->groupBy('faculty_id');

        $holidays = \App\Models\Holiday::whereBetween('date', [$startOfMonth, $endOfMonth])
            ->get()
            ->map(fn ($h) => (is_string($h->date) ? substr($h->date, 0, 10) : $h->date->format('Y-m-d')))
            ->toArray();

        $attendanceData = [
            'total_faculty' => $faculties->count(),
            'records' => [],
        ];

        foreach ($faculties as $faculty) {
            $facultyRecords = ($records->get($faculty->id) ?? collect())->keyBy('attendance_date');
            
            $presentCount = 0;
            $lateCount = 0;
            $halfDayCount = 0;
            $absentCount = 0;
            $trackedHours = 0;
            $actualHours = 0;
            $totalDaysTracked = 0;

            $current = Carbon::parse($startOfMonth);
            $end = Carbon::parse($endOfMonth);
            $todayStr = now()->toDateString();

            // We only track up to today, or the end of the month, whichever is earlier
            $trackingEnd = $end->isFuture() ? Carbon::parse($todayStr) : $end;

            while ($current->lte($trackingEnd)) {
                $dateStr = $current->format('Y-m-d');
                $isSunday = $current->isSunday();
                $isHoliday = in_array($dateStr, $holidays);

                if (!$isSunday && !$isHoliday) {
                    $totalDaysTracked++;

                    if ($facultyRecords->has($dateStr)) {
                        $record = $facultyRecords->get($dateStr);
                        $status = strtolower(trim($record->status));

                        if ($status === 'present') {
                            $presentCount++;
                        } elseif ($status === 'late') {
                            $lateCount++;
                        } elseif ($status === 'half_day') {
                            $halfDayCount++;
                        } elseif ($status === 'absent') {
                            $absentCount++;
                        } else {
                            $absentCount++; // Default any weird status to absent
                        }

                        // Add tracked hours (from DB)
                        $trackedHours += (float) $record->working_hours;

                        // Calculate actual hours (checkout - checkin)
                        if ($record->check_in_time && $record->check_out_time) {
                            $checkIn = Carbon::parse($record->check_in_time);
                            $checkOut = Carbon::parse($record->check_out_time);
                            if ($checkOut->greaterThan($checkIn)) {
                                $actualHours += abs($checkOut->diffInMinutes($checkIn)) / 60;
                            }
                        }
                    } else {
                        // Missing record on a working day = absent
                        $absentCount++;
                    }
                }
                $current->addDay();
            }

            $attendanceData['records'][] = [
                'faculty_name' => $faculty->name,
                'biometric_code' => $faculty->biometric_employee_code ?? $faculty->employee_id ?? 'N/A',
                'department' => $faculty->department ?? 'General Staff',
                'present_count' => $presentCount,
                'late_count' => $lateCount,
                'half_day_count' => $halfDayCount,
                'absent_count' => $absentCount,
                'total_days_tracked' => $totalDaysTracked,
                'tracked_hours' => round($trackedHours, 2),
                'actual_hours' => round($actualHours, 2),
            ];
        }

        // 3. Identify recipients (Super Admins)
        $recipients = User::whereHas('roles', function($q) {
            $q->where('name', 'super-admin');
        })->pluck('email')->filter()->toArray();

        // Allow adding extra specific emails via the .env file
        $extraEmails = config('mail.faculty_report_emails');
        if ($extraEmails) {
            $emailsList = array_map('trim', explode(',', $extraEmails));
            $recipients = array_filter(array_unique(array_merge($recipients, $emailsList)));
        }

        // Fallback if no super admin emails
        if (empty($recipients)) {
            $defaultEmail = config('mail.from.address');
            if ($defaultEmail) {
                $recipients[] = $defaultEmail;
            }
        }

        if (empty($recipients)) {
            $this->warn('No recipients found to send the email.');
            Log::warning("Faculty Attendance Monthly summary: No recipients found.");
            return 1;
        }

        // 4. Send Email
        try {
            $this->info('Dispatching monthly email to: ' . implode(', ', $recipients));
            Log::info("Faculty Attendance Monthly summary is preparing to send.", [
                'month' => $monthName,
                'recipients' => $recipients,
                'total_faculty_processed' => $attendanceData['total_faculty']
            ]);
            
            Mail::to($recipients)->send(new FacultyMonthlyAttendanceSummaryMail($attendanceData, $monthName));
            
            $this->info('Monthly email dispatched successfully.');
            Log::info("Faculty Attendance Monthly summary dispatched successfully.");
        } catch (\Exception $e) {
            $this->error('Failed to send monthly email: ' . $e->getMessage());
            Log::error("Failed to send Faculty Attendance Monthly summary: " . $e->getMessage());
            return 1;
        }

        return 0;
    }
}
