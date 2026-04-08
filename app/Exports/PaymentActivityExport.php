<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class PaymentActivityExport implements FromCollection, WithHeadings, WithMapping
{
    protected $activities;

    public function __construct(Collection $activities)
    {
        $this->activities = $activities;
    }

    public function collection()
    {
        return $this->activities;
    }

    public function headings(): array
    {
        return [
            'Date',
            'Action',
            'User',
            'Details',
            'IP Address',
        ];
    }

    public function map($activity): array
    {
        return [
            $activity->created_at->format('Y-m-d H:i:s'),
            $activity->description,
            $activity->causer ? $activity->causer->name : 'System',
            $this->formatProperties($activity->properties),
            $activity->ip_address ?? 'N/A',
        ];
    }

    protected function formatProperties($properties)
    {
        if (empty($properties)) {
            return '';
        }

        // Convert to string representation if it's an array/collection
        if (is_array($properties) || $properties instanceof Collection) {
            return json_encode($properties);
        }

        return (string) $properties;
    }
}
