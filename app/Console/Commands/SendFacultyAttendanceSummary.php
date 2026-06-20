<?php

namespace App\Console\Commands;

use App\Mail\FacultyAttendanceSummaryMail;
use App\Models\Attendance\FacultyAttendance;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendFacultyAttendanceSummary extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'attendance:faculty-summary {type : "morning" or "evening"}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send morning or evening faculty attendance summary emails';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $type = $this->argument('type');
        
        if (!in_array($type, ['morning', 'evening'])) {
            $this->error('Type must be "morning" or "evening"');
            return 1;
        }

        $date = Carbon::now();
        $dateStr = $date->format('Y-m-d');
        $displayDateStr = $date->format('l, d F Y');

        $this->info("Preparing {$type} faculty attendance summary for {$displayDateStr}...");

        // 1. Get all active faculty
        $faculties = User::whereHas('roles', function($q) {
            $q->whereIn('name', ['staff', 'faculty']);
        })
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        // 2. Get today's attendance records
        $records = FacultyAttendance::whereIn('faculty_id', $faculties->pluck('id'))
            ->where('attendance_date', $dateStr)
            ->get()
            ->keyBy('faculty_id');

        $attendanceData = [
            'total_faculty' => $faculties->count(),
            'present_count' => 0,
            'late_count' => 0,
            'half_day_count' => 0,
            'absent_count' => 0,
            'records' => [],
        ];

        foreach ($faculties as $faculty) {
            $record = $records->get($faculty->id);
            $status = 'absent';
            $checkIn = '--';
            $checkOut = '--';
            $actualHours = '--';

            if ($record) {
                $status = strtolower(trim($record->status));
                if ($record->check_in_time) {
                    $checkIn = Carbon::parse($record->check_in_time)->format('h:i A');
                }
                if ($record->check_out_time) {
                    $checkOut = Carbon::parse($record->check_out_time)->format('h:i A');
                }
                
                if ($record->check_in_time && $record->check_out_time) {
                    $cIn = Carbon::parse($record->check_in_time);
                    $cOut = Carbon::parse($record->check_out_time);
                    if ($cOut->greaterThan($cIn)) {
                        $actualHours = round(abs($cOut->diffInMinutes($cIn)) / 60, 2);
                    }
                }
            }

            if ($status === 'present') {
                $attendanceData['present_count']++;
            } elseif ($status === 'late') {
                $attendanceData['late_count']++;
            } elseif ($status === 'half_day') {
                $attendanceData['half_day_count']++;
            } else {
                $attendanceData['absent_count']++;
            }

            $attendanceData['records'][] = [
                'faculty_name' => $faculty->name,
                'biometric_code' => $faculty->biometric_employee_code ?? $faculty->employee_id ?? 'N/A',
                'department' => $faculty->department ?? 'General Staff',
                'status' => $status,
                'check_in' => $checkIn,
                'check_out' => $checkOut,
                'actual_hours' => $actualHours,
            ];
        }

        // 3. Identify recipients (Super Admins)
        $recipients = User::whereHas('roles', function($q) {
            $q->where('name', 'super-admin');
        })->pluck('email')->filter()->toArray();

        // Allow adding extra specific emails via the .env file
        $extraEmails = env('FACULTY_REPORT_EMAILS');
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
            Log::warning("Faculty Attendance {$type} summary: No recipients found.");
            return 1;
        }

        // 4. Send Email
        try {
            $this->info('Dispatching email to: ' . implode(', ', $recipients));
            Log::info("Faculty Attendance {$type} summary is preparing to send.", [
                'type' => $type,
                'recipients' => $recipients,
                'total_faculty_processed' => $attendanceData['total_faculty']
            ]);
            
            Mail::to($recipients)->send(new FacultyAttendanceSummaryMail($type, $attendanceData, $displayDateStr));
            
            $this->info('Email dispatched successfully.');
            Log::info("Faculty Attendance {$type} summary dispatched successfully.");
        } catch (\Exception $e) {
            $this->error('Failed to send email: ' . $e->getMessage());
            Log::error("Failed to send Faculty Attendance {$type} summary: " . $e->getMessage());
            return 1;
        }

        return 0;
    }
}
