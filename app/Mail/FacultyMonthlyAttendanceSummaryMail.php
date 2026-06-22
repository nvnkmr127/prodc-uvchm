<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class FacultyMonthlyAttendanceSummaryMail extends Mailable
{
    use Queueable, SerializesModels;

    public $attendanceData;
    public $monthName;

    /**
     * Create a new message instance.
     */
    public function __construct(array $attendanceData, string $monthName)
    {
        $this->attendanceData = $attendanceData;
        $this->monthName = $monthName;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "UVCHM Faculty Monthly Attendance Summary - {$this->monthName}",
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.faculty.attendance_summary_monthly',
            with: [
                'attendanceData' => $this->attendanceData,
                'monthName' => $this->monthName,
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
