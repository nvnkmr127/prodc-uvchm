<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class FacultyAttendanceSummaryMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $type;
    public $attendanceData;
    public $dateStr;

    /**
     * Create a new message instance.
     *
     * @param string $type "morning" or "evening"
     * @param array $attendanceData
     * @return void
     */
    public function __construct($type, $attendanceData, $dateStr)
    {
        $this->type = $type;
        $this->attendanceData = $attendanceData;
        $this->dateStr = $dateStr;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        if ($this->type === 'morning') {
            $subject = "Faculty Attendance Summary - {$this->dateStr} (11:00 AM)";
            $view = 'emails.faculty.attendance_summary_morning';
        } else {
            $subject = "Faculty Checkout Summary - {$this->dateStr} (5:10 PM)";
            $view = 'emails.faculty.attendance_summary_evening';
        }

        return $this->subject($subject)
                    ->view($view);
    }
}
