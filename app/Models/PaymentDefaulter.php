<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentDefaulter extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'defaulter_category',
        'total_overdue_amount',
        'overdue_days',
        'total_overdue_invoices',
        'first_overdue_date',
        'overdue_fee_types',
        'current_status',
        'contact_attempts',
        'component_breakdown',
        'affected_categories_count',
        'priority_score',
        'last_contacted_at',
        'notes'
    ];

    protected $casts = [
        'component_breakdown' => 'array',
        'overdue_fee_types' => 'array',
        'first_overdue_date' => 'date',
        'last_contacted_at' => 'datetime',
        'priority_score' => 'decimal:2',
        'total_overdue_amount' => 'decimal:2'
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }
}
