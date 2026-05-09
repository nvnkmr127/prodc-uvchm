<?php

namespace App\Exports;

use App\Models\Enquiry;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class EnquiriesExport implements FromCollection, WithHeadings, WithMapping
{
    protected $enquiries;

    public function __construct($enquiries)
    {
        $this->enquiries = $enquiries;
    }

    public function collection()
    {
        return $this->enquiries;
    }

    public function headings(): array
    {
        return [
            'ID',
            'Student Name',
            'Phone Number',
            'Email',
            'Gender',
            'Course',
            'Source',
            'Referral',
            'Counselor',
            'They attended exam',
            'Final Agreed Fee (₹)',
            'Kit Assignments',
            'Status',
            'Created At',
            'Next Follow-up',
            'Address',
            'Notes',
        ];
    }

    /**
     * @var Enquiry
     */
    public function map($enquiry): array
    {
        return [
            $enquiry->id,
            $enquiry->student_name,
            $enquiry->phone_number,
            $enquiry->email,
            $enquiry->gender,
            $enquiry->course->name ?? 'N/A',
            $enquiry->source,
            $enquiry->referral_name,
            $enquiry->assignedTo->name ?? 'Unassigned',
            $enquiry->test_attended ? 'Yes' : 'No',
            $enquiry->agreed_fee,
            ($enquiry->include_uniform ? 'Uniform' : '') . ($enquiry->include_uniform && $enquiry->include_books ? ', ' : '') . ($enquiry->include_books ? 'Books' : ($enquiry->include_uniform ? '' : 'None')),
            $enquiry->status,
            $enquiry->created_at->format('Y-m-d H:i'),
            $enquiry->next_follow_up_date ? $enquiry->next_follow_up_date->format('Y-m-d') : 'N/A',
            $enquiry->address,
            $enquiry->notes,
        ];
    }
}
